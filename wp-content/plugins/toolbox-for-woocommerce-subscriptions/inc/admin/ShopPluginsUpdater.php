<?php
/**
 *
 */
namespace ShopPlugins\Updater;

function updater() {
	if ( ! class_exists( '\JeroenSormani\WP_Updater\WPUpdater' ) ) {
		require JGTB_PATH . 'inc/wp-updater/wp-updater.php';
	}
	new \JeroenSormani\WP_Updater\WPUpdater( array(
		'file'    => JGTB_FILE,
		'name'    => 'Toolbox for WooCommerce Subscriptions',
		'version' => JGTB_VERSION,
		'api_url' => 'https://shopplugins.com',
	) );
}

add_action( 'admin_init', '\ShopPlugins\Updater\updater' );
