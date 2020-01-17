<?php
/**
 * Contains functionality relating to the edit subscriptions template
 * and associated handlers.
 *
 * @package Javorszky\Toolbox
 */

namespace Javorszky\Toolbox;
use WC_Subscriptions;

add_filter( 'wc_get_template', __NAMESPACE__ . '\\add_edit_subscription_template', 10, 5 );
add_action( 'woocommerce_account_' . JGTB_EDIT_SUB_ENDPOINT . '_endpoint', __NAMESPACE__ . '\\get_edit_subscription_template' );
add_filter( 'wcs_view_subscription_actions', __NAMESPACE__ . '\\add_link_to_edit_page', 11, 2 );
add_filter( 'the_title', __NAMESPACE__ . '\\change_endpoint_title', 11, 1 );
add_action( 'wp', __NAMESPACE__ . '\\maybe_remove_wcs_nonce' );
add_action( 'wp_loaded', __NAMESPACE__ . '\\handle_edit_details' );

// we need to run the change frequency processing code first.
add_action( 'wp_loaded', __NAMESPACE__ . '\\handle_change_frequency', 30 );
add_action( 'wp_loaded', __NAMESPACE__ . '\\redirect_to_view', 900 );

/**
 * Subscriptions adds a nonce / http referrer after the edit address forms for their own purposes. Because we don't
 * need that functionality, let's remove them.
 */
function maybe_remove_wcs_nonce() {
	global $wp;

	if ( isset( $wp->query_vars[ JGTB_EDIT_SUB_ENDPOINT ] ) ) {
		remove_action( 'woocommerce_after_edit_address_form_billing', 'WC_Subscriptions_Addresses::maybe_add_edit_address_checkbox', 10 );
		remove_action( 'woocommerce_after_edit_address_form_shipping', 'WC_Subscriptions_Addresses::maybe_add_edit_address_checkbox', 10 );
	}
}

/**
 * Adds an Edit Details button to the view subscriptions page.
 *
 * @param array           $actions      array of actions already on the subscription.
 * @param \WC_Subscription $subscription the subscription we're adding the action to.
 * @return array
 */
function add_link_to_edit_page( $actions, $subscription ) {
	$actions[ JGTB_EDIT_SUB_ENDPOINT ] = array(
		'url'  => wc_get_endpoint_url( JGTB_EDIT_SUB_ENDPOINT, $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ),
		'name' => Utilities\replace_key_dates( Utilities\get_button_text( 'edit_subs_button_text', 'Edit details' ), $subscription ),
	);
	return $actions;
}

/**
 * Changes page title on view subscription page
 *
 * @param  string $title original title.
 * @return string        changed title.
 */
function change_endpoint_title( $title ) {
	global $wp;

	if ( is_main_query() && is_page() && isset( $wp->query_vars[ JGTB_EDIT_SUB_ENDPOINT ] ) && in_the_loop() && is_account_page() ) {
		$title = get_endpoint_title( JGTB_EDIT_SUB_ENDPOINT );
	}
	return $title;
}


/**
 * Set the subscription page title when viewing a subscription.
 *
 * @since 2.0
 * @param  string $endpoint Endpoint for the path we're currently on.
 * @return string           Title belonging to that endpoint.
 */
function get_endpoint_title( $endpoint ) {
	global $wp;

	switch ( $endpoint ) {
		case JGTB_EDIT_SUB_ENDPOINT:
			$subscription = wcs_get_subscription( $wp->query_vars[ JGTB_EDIT_SUB_ENDPOINT ] );

			// translators: placeholder is the order number for the subscription.
			$title = ( $subscription ) ? sprintf( esc_html_x( 'Edit details for Subscription #%s', 'Used in page\'s <h1> attribute', 'jg-toolbox' ), $subscription->get_order_number() ) : '';
			break;
		default:
			$title = '';
			break;
	}

	return $title;
}

/**
 * Fetches the address on a subscription in such a form that the edit address form can parse the details and
 * display the correct form elements.
 *
 * @param  string          $load_address the type of address to load.
 * @param  \WC_Subscription $subscription instance of subscription we're loading the addresses for.
 * @return array                         address fields with value, default, type, label.
 */
function get_address( $load_address = 'billing', $subscription ) {

	$address_fields       = WC()->countries->get_address_fields( '', $load_address . '_' );
	$subscription_address = $subscription->get_address( $load_address );

	foreach ( $address_fields as $key => $field ) {
		$sub_key                         = str_replace( $load_address . '_', '', $key );
		$address_fields[ $key ]['value'] = isset( $subscription_address[ $sub_key ] ) ? $subscription_address[ $sub_key ] : '';
	}

	return $address_fields;
}


/**
 * Changes the date of the subscription if it's been requested and we made sure all data is in order.
 */
function handle_edit_details() {
	if ( isset( $_REQUEST['jgtb_edit_subscription_details'] ) && isset( $_REQUEST[ 'jgtb_edit_details_of_' . absint( $_REQUEST['jgtb_edit_subscription_details'] ) ] ) ) {
		$user_id        = get_current_user_id();
		$subscription   = wcs_get_subscription( $_REQUEST['jgtb_edit_subscription_details'] );
		$nonce          = $_REQUEST[ 'jgtb_edit_details_of_' . $subscription->get_id() ];
		$allow_edit_qty = apply_filters( 'jgtb_allow_edit_qty_for_subscription', get_option( JGTB_OPTION_PREFIX . 'qty_change_edit_sub_details', 'yes' ), $subscription );

		if ( ! wcs_is_subscription( $subscription ) ) {
			// there's nothing to do.
			return;
		}

		if ( Utilities\Process\process_change_shipping_address( $user_id, $subscription, $nonce ) ) {
			wc_add_notice( esc_html__( 'Shipping address updated', 'jg-toolbox' ), 'success' );
		}

		if ( Utilities\Process\process_change_billing_address( $user_id, $subscription, $nonce ) ) {
			wc_add_notice( esc_html__( 'Billing address updated', 'jg-toolbox' ), 'success' );
		}

		if ( 'no' !== $allow_edit_qty && Utilities\Process\process_change_product_quantities( $user_id, $subscription, $nonce ) ) {
			wc_add_notice( esc_html__( 'Product quantities updated.', 'jg-toolbox' ) );
		}
	}
}

/**
 * Code that handles change frequency request after making the necessary checks.
 *
 * Hooked into `wp_loaded` @ 30.
 */
function handle_change_frequency() {
	if ( isset( $_REQUEST['new_period'] ) && isset( $_REQUEST['new_interval'] ) && isset( $_REQUEST['edit_subscription_id'] ) && isset( $_REQUEST['jgtb_change_frequency_nonce'] ) ) {
		$user_id                = get_current_user_id();
		$subscription           = wcs_get_subscription( $_REQUEST['edit_subscription_id'] );
		$new_interval           = $_REQUEST['new_interval'];
		$new_period             = $_REQUEST['new_period'];
		$old_interval           = $subscription->get_billing_interval();
		$old_period             = $subscription->get_billing_period();
		$allow_frequency_change = apply_filters( 'jgtb_allow_edit_freq_for_subscription', get_option( JGTB_OPTION_PREFIX . 'freq_change_edit_sub_details', 'yes' ), $subscription );

		if (
			! wcs_is_subscription( $subscription ) ||
			! in_array( absint( $new_interval ), apply_filters( 'jgtb_permitted_intervals', array( 1, 2, 3, 4, 5, 6 ) ), true ) ||
			! in_array( $new_period, apply_filters( 'jgtb_permitted_periods', array( 'day', 'week', 'month', 'year' ) ), true ) ||
			// because nothing changed.
			( absint( $new_interval ) === absint( $old_interval ) && $new_period === $old_period ) ||
			// because it's not allowed.
			'no' === $allow_frequency_change
		) {
			// there's nothing to do.
			return;
		}

		Utilities\Process\process_change_frequency( $user_id, $subscription, $_REQUEST['jgtb_change_frequency_nonce'], $new_interval, $new_period );
	}
}

/**
 * Show the subscription template when view a subscription instead of loading the default order template.
 *
 * @since 2.0
 *
 * @param string $located       Location of the found template file going in.
 * @param string $template_name Name of the template we're looking for.
 * @param array  $args          Args passed.
 * @param string $template_path Path for the found template.
 * @param string $default_path  Default path.
 * @return string               Path of the template we want to use.
 */
function add_edit_subscription_template( $located, $template_name, $args, $template_path, $default_path ) {
	global $wp;

	if ( 'myaccount/my-account.php' === $template_name && ! empty( $wp->query_vars[ JGTB_EDIT_SUB_ENDPOINT ] ) && WC_Subscriptions::is_woocommerce_pre( '2.6' ) ) {
		$located = wc_locate_template( 'myaccount/edit-subscription.php', $template_path, JGTB_PATH . 'templates/' );
	}

	return $located;
}

/**
 * Get the view subscription template. A post WC v2.6 compatible version of @see WCS_Template_Loader::add_view_subscription_template()
 *
 * @since 2.0.17
 */
function get_edit_subscription_template() {
	wc_get_template( 'myaccount/edit-subscription.php', array(), '', JGTB_PATH . 'templates/' );
}

/**
 * Redirects user to view subscription template after they submit the edit
 * subscriptions details
 */
function redirect_to_view() {
	if ( isset( $_REQUEST['edit-subscription-button'] ) && '1' === $_REQUEST['edit-subscription-button'] ) {
		wp_safe_redirect( wc_get_endpoint_url( 'view-subscription', $_REQUEST['edit_subscription_id'], wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}
}
