<?php
namespace JeroenSormani\WP_Updater\Clients;

use JeroenSormani\WP_Updater\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class EDD implements ClientInterface {


	/**
	 * API url.
	 *
	 * @var string API url used to connect to the update server.
	 */
	protected $api_url;


	/**
	 * @var Plugin Plugin object.
	 */
	protected $plugin;


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API url.
	 * @param Plugin $plugin Plugin object.
	 * @param array  $args List of arguments for the plugin.
	 */
	public function __construct( $api_url, Plugin $plugin, $args ) {

		$this->api_url = esc_url_raw( $api_url );
		$this->plugin = $plugin;

	}


	/**
	 * Perform API request.
	 *
	 * @since 1.0.0
	 *
	 * @param array            $params List of arguments to pass.
	 * @return array|\WP_Error         Response of the API request.
	 */
	public function api_request( $params ) {

		$params = wp_parse_args( $params, array(
			'license'    => $this->plugin->get_license_key(),
			'item_name'  => urlencode( $this->plugin->get_name() ),
			'url'        => home_url(),
		) );

		$url = esc_url_raw( add_query_arg( $params, $this->api_url ) );
		$response = wp_remote_get( $url );

		return $response;

	}


	/**
	 * Try to activate license.
	 *
	 * Try to activate the license with the current license key
	 *
	 * @since 1.0.0
	 *
	 * @return bool|\WP_Error True when successful, WP_Error otherwise.
	 */
	public function activate() {

		$api_params = array(
			'edd_action' => 'activate_license',
		);

		// Call the custom API.
		$response = $this->api_request( $api_params );

		// Check for WP_Error
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check for valid API request
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
		}

		if ( ! $body = json_decode( $response['body'] ) ) {
			return new \WP_Error( 'empty', __( 'Empty body response' ) );
		}

		// All went well
		if ( $body->success === true ) {
			return true;
		}

		// Something went wrong
		$code = 'invalid';
		switch ( $body->error ) {

			case 'expired' :
				$message = __( 'Your license key has expired, please renew it through our system' );
				$code = 'expired';
				break;
			case 'revoked' :
				$message = __( 'Your license has been revoked' );
				break;
			case 'missing' :
				$message = __( 'Invalid license key, please verify your license and try again' );
				break;
			case 'site_inactive' :
			case 'invalid' :
				$message = __( 'Your license is not active for this URL' );
				break;
			case 'item_name_mismatch' :
				$message = sprintf( __( 'This license is not valid for %s' ), $this->plugin->get_name() );
				break;
			case 'no_activations_left' :
				$message = __( 'Your license has reached its activation limit' );
				break;
			default :
				$message = __( 'Something went wrong, please verify everything and try again' );
				break;
		}

		return new \WP_Error( $code, $message );

	}


	/**
	 * Deactivate license.
	 *
	 * Deactivate the license for the current plugin/site.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|\WP_Error True when successful, WP_Error otherwise.
	 */
	public function deactivate() {

		$api_params = array(
			'edd_action' => 'deactivate_license',
		);

		// Call the custom API.
		$response = $this->api_request( $api_params );

		// Check for WP_Error
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check for valid API request
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
		}

		if ( ! $body = json_decode( $response['body'] ) ) {
			return new \WP_Error( 'empty', __( 'Empty body response' ) );
		}

		// All went well
		if ( $body->success === true ) {
			return true;
		}

		// Something went wrong
		return new \WP_Error( 'error', __( 'Something went wrong, unable to deactivate your license' ) );

	}


	/**
	 * Get status.
	 *
	 * Get the current license status from the server.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current plugin status.
	 */
	public function get_status() {

		$dirname       = dirname( $this->plugin->plugin_basename );
		$transient_key = 'wpu_' . $dirname . '_status';
		if ( $cache = get_transient( $transient_key ) ) {
			return $cache;
		}

		$api_params = array(
			'edd_action' => 'check_license',
		);

		// Call the custom API.
		$response = $this->api_request( $api_params );

		// Check for WP_Error
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check for valid API request
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
		}

		if ( ! $body = json_decode( $response['body'] ) ) {
			return new \WP_Error( 'empty', __( 'Empty body response' ) );
		}

		// License status
		switch ( $body->license ) {

			default :
			case 'expired' :
			case 'site_inactive' :
			case 'disabled' :
			case 'valid' :
			case 'invalid' :
			case 'item_name_mismatch' :
			case 'invalid_item_id' :
				$status = $body->license;
				break;
		}

		set_transient( $transient_key, $status, ( HOUR_IN_SECONDS ) ); // Cache for 1 hour

		return $status;

	}


	/**
	 * Get changelog.
	 *
	 * Get the changelog from the server.
	 *
	 * @since 1.0.0
	 *
	 * @return string A string containing the changelog.
	 */
	public function get_changelog() {

		$data = $this->get_plugin_update_info();

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$sections = maybe_unserialize( $data->sections );
		$changelog = esc_attr( $sections['changelog'] );

		return $changelog;

	}


	/**
	 * Plugin version info.
	 *
	 * Get plugin version info that is returned by the EDD API.
	 *
	 * @since 1.0.0
	 *
	 * @return object|\WP_Error WP_Error is something went wrong, object with data otherwise.
	 */
	public function get_plugin_update_info() {

		$dirname       = dirname( $this->plugin->plugin_basename );
		$transient_key = 'wpu_' . $dirname . '_info';
		if ( $cache = get_transient( $transient_key ) ) {
			return $cache;
		}

		$api_params = array(
			'edd_action' => 'get_version',
		);

		// Call the custom API.
		$response = $this->api_request( $api_params );

		// Check for WP_Error
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check for valid API request
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
		}

		if ( ! $body = json_decode( $response['body'] ) ) {
			return new \WP_Error( 'empty', __( 'Empty body response' ) );
		}

		set_transient( $transient_key, $body, ( HOUR_IN_SECONDS * 12 ) ); // Cache for 12 hours

		return $body;

	}


	/**
	 * Get latest version number.
	 *
	 * Get the latest version number available of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string|\WP_Error Latest plugin error that's available, WP_Error when something goes wrong.
	 */
	public function get_version() {

		$data = $this->get_plugin_update_info();

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$version = esc_attr( $data->new_version );

		return $version;

	}


	/**
	 * Return if update is available.
	 *
	 * Return the value to determine if a update is available.
	 *
	 * @return bool True when a new update is available, false otherwise.
	 */
	public function is_update_available() {

		if ( is_wp_error( $this->get_version() ) ) {
			return false;
		}

		if ( version_compare( $this->plugin->get_version(), $this->get_version(), '<' ) ) {
			return true;
		}

		return false;

	}


}