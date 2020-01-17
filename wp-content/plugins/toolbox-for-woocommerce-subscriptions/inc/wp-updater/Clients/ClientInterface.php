<?php
namespace JeroenSormani\WP_Updater\Clients;

use JeroenSormani\WP_Updater\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

interface ClientInterface {

//	protected $api_url;


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API url.
	 * @param Plugin $plugin Plugin object.
	 * @param array  $args List of arguments for the plugin.
	 */
	public function __construct( $api_url, Plugin $plugin, $args );


	/**
	 * Try to activate license.
	 *
	 * Try to activate the license with the current license key
	 *
	 * @since 1.0.0
	 *
	 * @return bool|\WP_Error True when successful, WP_Error otherwise.
	 */
	public function activate();

	/**
	 * Deactivate license.
	 *
	 * Deactivate the license for the current plugin/site.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when successful, false otherwise.
	 */
	public function deactivate();

	/**
	 * Get status.
	 *
	 * Get the current license status from the server.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current plugin status.
	 */
	public function get_status();

	/**
	 * Get changelog.
	 *
	 * Get the changelog from the server.
	 *
	 * @since 1.0.0
	 *
	 * @return string A string containing the changelog.
	 */
	public function get_changelog();

	/**
	 * Get latest version number.
	 *
	 * Get the latest version number available of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string The latest version number of the plugin available.
	 */
	public function get_version();

	/**
	 * Return if update is available.
	 *
	 * @return bool True when a new update is available, false otherwise.
	 */
	public function is_update_available();

}
