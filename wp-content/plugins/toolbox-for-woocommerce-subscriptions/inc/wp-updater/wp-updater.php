<?php
namespace JeroenSormani\WP_Updater;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPUpdater {


	/**
	 * WP Updater version.
	 *
	 * @var string
	 */
	protected $version = '1.0.0';


	/**
	 * Plugin class.
	 *
	 * @var Plugin
	 */
	protected $plugin;


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args List of arguments.
	 */
	public function __construct( $args = array() ) {

		$this->args = wp_parse_args( $args, array(
			'file'                => '', // Main plugin file
			'license_option_name' => '', // License option name to get from DB
			'type'                => 'edd', // Type of license server
			'api_url'             => '', // API URL to talk with
		) );

		$this->plugin = new Plugin( $args );

		// Show license field
		add_action( 'after_plugin_row_' . $this->plugin->plugin_basename, array( $this, 'license_field' ), 20, 3 );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// AJAX - Update license
		add_action( 'wp_ajax_updater_update_license', array( $this, 'update_license' ) );
		// AJAX - deactivate license
		add_action( 'wp_ajax_updater_deactivate_license', array( $this, 'deactivate_license' ) );

		// Add plugin data to transient
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'add_plugin_update_transient_data' ), 20 );

		// Possibly remove update row
		add_action( 'after_plugin_row_' . $this->plugin->plugin_basename, array( $this, 'maybe_remove_update_row' ), 9 );

		// Show pop-up (thickbox) details
		add_filter( 'plugins_api', array( $this, 'replace_plugin_thickbox' ), 10, 3 );

	}


	/**
	 * Add plugin update data.
	 *
	 * Add the plugin update data to the cached list used by WordPress. This is added
	 * the same time when WordPress is getting / setting the plugin update data for other plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data List of data already present in WordPress
	 * @return mixed
	 */
	public function add_plugin_update_transient_data( $data ) {

		// Check the time of the last update.
		// This is added particularly to prevent too many requests being send when other code is triggering the hooked filter.
		if (
			isset( $data->response ) && isset( $data->response[ $this->plugin->plugin_basename ] ) &&
			isset( $data->last_checked ) && $data->last_checked > strtotime( '-12 hours' )
		) {
			return $data;
		}

		// Check plugin status
		$this->plugin->check_and_update_license_status();
		$plugin_data = $this->plugin->client->get_plugin_update_info();

		if ( ! is_wp_error( $plugin_data ) && $this->plugin->client->is_update_available() ) {
			$data->response[ $this->plugin->plugin_basename ] = (object) $plugin_data;
			$data->checked[ $this->plugin->plugin_basename ] = $this->plugin->get_version();
		}

		return $data;

	}


	/**
	 * Show license field.
	 *
	 * Show the license field on the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param  array  $plugin_data An array of plugin data.
	 * @param  string $status      Status of the plugin. Defaults are 'All', 'Active',
	 *                            'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
	 *                            'Drop-ins', 'Search'.
	 * @return bool False          when the field shouldn't be showing.
	 */
	public function license_field( $plugin_file, $plugin_data, $status ) {

		$plugin = $this->plugin;
		if ( $plugin_file !== $plugin->plugin_basename ) {
			return false;
		}

		if ( $plugin->get_license_status() !== 'valid' ) {
			require 'Views/html-license-field.php';
		} else {
			require 'Views/html-valid-license.php';
		}

	}


	/**
	 * Enqueue scripts.
	 *
	 * Add javascripts and stylesheets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current page ID.
	 */
	public function admin_enqueue_scripts( $hook ) {

		// Styles
		wp_register_style( 'wp-updater', plugins_url( 'assets/css/wp-updater.min.css', __FILE__ ), array( 'dashicons' ), $this->version );

		// Javascript
		wp_register_script( 'wp-updater', plugins_url( 'assets/js/wp-updater.min.js', __FILE__ ), array( 'jquery' ), $this->version );
		wp_localize_script( 'wp-updater', 'wpu', array(
			'nonce' => wp_create_nonce( 'wpu-nonce' ),
		) );

		if ( $hook == 'plugins.php' ) {
			wp_enqueue_style( 'wp-updater' );
			wp_enqueue_script( 'wp-updater' );
		}

	}


	/**
	 * Save the license key.
	 *
	 * Save the license key to the database.
	 *
	 * @since 1.0.0
	 */
	public function update_license() {

		check_ajax_referer( 'wpu-nonce', 'nonce' );

		$plugin  = sanitize_text_field( $_POST['plugin'] );
		$license = sanitize_text_field( $_POST['license'] );

		// Check if AJAX action is meant for this plugin. When multiple plugins using the WP Updater
		// it may clash, this check ensures it works nicely.
		if ( $this->plugin->plugin_basename !== $plugin ) {
			return;
		}

		$this->plugin->set_license_key( $license );

		$plugin   = $this->plugin;
		$response = $plugin->client->activate();

		if ( $response && ! is_wp_error( $response) ) {
			$plugin->set_license_status( 'valid' );
			$message      = 'Successfully activated';
			$message_type = 'success';
		} else {
			$plugin->set_license_status( $response->get_error_code() );
			$message      = $response->get_error_message();
			$message_type = 'error';
		}

		ob_start();
			$this->license_field( $this->plugin->plugin_basename, array(), null );
		$html = ob_get_clean();

		wp_send_json( array(
			'message'      => $message,
			'message_type' => $message_type,
			'html'         => $html,
		) );
	}


	/**
	 * Deactivate license.
	 *
	 * Deactivate the license on the server.
	 *
	 * @since 1.0.0
	 */
	public function deactivate_license() {

		check_ajax_referer( 'wpu-nonce', 'nonce' );

		$plugin = sanitize_text_field( $_POST['plugin'] );

		// Check if AJAX action is meant for this plugin. When multiple plugins using the WP Updater
		// it may clash, this check ensures it works nicely.
		if ( $this->plugin->plugin_basename !== $plugin ) {
			return;
		}

		$plugin   = $this->plugin;
		$response = $plugin->client->deactivate();

		if ( $response && ! is_wp_error( $response) ) {
			$plugin->set_license_status( null );
			$message      = 'Successfully deactivated';
			$message_type = 'success';
		} else {
			$plugin->set_license_status( 'invalid' );
			$message      = $response->get_error_message();
			$message_type = 'error';
		}

		ob_start();
			$this->license_field( $this->plugin->plugin_basename, array(), null );
		$html = ob_get_clean();

		wp_send_json( array(
			'message'      => $message,
			'message_type' => $message_type,
			'html'         => $html,
		) );

	}


	/**
	 * Remove update row.
	 *
	 * Possible remove the update row when there is a update, but the license is set to 'expired'.
	 *
	 * @since 1.0.0
	 */
	public function maybe_remove_update_row() {

		if ( $this->plugin->get_license_status() != 'valid' ) {
			remove_action( 'after_plugin_row_' . $this->plugin->plugin_basename, 'wp_plugin_update_row', 10 );
		}

	}


	/**
	 * Replace plugin thickbox data.
	 *
	 * Replace the data for the plugin version 'view details' button on the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param  object|bool $data   False by default, when returning a object WP knows to use that instead.
	 * @param  string      $action The type of information being requested from the Plugin Install API.
	 * @param  object      $args   Plugin API arguments.
	 * @return object|bool         Returns object with plugin info, false otherwise.
	 */
	public function replace_plugin_thickbox( $data, $action, $args ) {

		$plugin_slug = $this->plugin->get_slug();
		if ( 'plugin_information' != $action || $args->slug != $plugin_slug ) {
			return $data;
		}

		$plugin_data = $this->plugin->client->get_plugin_update_info();
		$plugin_sections = isset( $plugin_data->sections ) ? maybe_unserialize( $plugin_data->sections ) : array();

		$data = (object) array(
			'name' => $this->plugin->get_name(),
			'slug' => $plugin_data->slug,
			'version' => $this->plugin->get_version(),
			'author' => '',
			'author_profile' => '',
			'contributors' => array(),
			'requires' => '',
			'tested' => '',
			'compatibility' => array(),
			'rating' => '',
			'num_ratings' => '',
			'ratings' => array(),
			'active_installs' => '',
			'last_updated' => $plugin_data->last_updated,
			'added' => '',
			'homepage' => $plugin_data->homepage,
			'sections' => array(
				'description' => isset( $plugin_sections['description'] ) ? $plugin_sections['description'] : null,
				'changelog' => isset( $plugin_sections['changelog'] ) ? $plugin_sections['changelog'] : null,
			),
			'banners' => '',
		);

		return $data;

	}


}

require 'Plugin.php';
require 'Clients/ClientInterface.php';
require 'Clients/EDD.php';
require 'Clients/WooCommerce.php';
