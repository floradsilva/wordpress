<?php
namespace Javorszky\Toolbox;

add_filter( 'wc_get_template', __NAMESPACE__ . '\\add_my_subscription_template', 10, 5 );

add_action( 'woocommerce_my_subscriptions_after_subscription_id', __NAMESPACE__ . '\\add_list_of_items' );

add_action( 'wp_loaded', __NAMESPACE__ . '\\handle_bulk_reschedules' );
add_action( 'wp_loaded', __NAMESPACE__ . '\\handle_bulk_edit_quantities' );


function add_my_subscription_template( $located, $template_name, $args, $template_path, $default_path ) {
	// this is not the template you're looking for
	if ( 'myaccount/my-subscriptions.php' !== $template_name ) {
		return $located;
	}

	$theme_overrides = array(
		get_template_directory(),
		get_stylesheet_directory(),
		'/theme-compat/',
	);

	foreach ( $theme_overrides as $path_part ) {
		if ( false !== strpos( $located, $path_part ) ) {
			// then we found it, let's keep it as is
			return $located;
		}
	}

	return JGTB_PATH . 'templates/' . $template_name;
}

/**
 * @param \WC_Subscription    $subscription
 */
function add_list_of_items( $subscription ) {
	echo '<ul>';
	foreach ( $subscription->get_items() as $item_id => $item ) {
		echo '<li>' . wp_kses_post( $item->get_name() ) . ' &times; <input name="new_quantity_' . esc_attr( $item_id ) . '" value="' . esc_attr( $item->get_quantity() ) . '" type="number" min="0" max="999" step="1"></li>';
	}
	echo '</ul>';
}

/**
 * Routes the request to one of the utility methods in GD_CCC_SubscriptionUtilities. This class is extending that
 * one so the protected method can be accessed safely.
 */
function handle_bulk_reschedules() {
	$user_id = get_current_user_id();

	if ( isset( $_REQUEST['submit_button'] ) && isset( $_REQUEST['jgtb_my_subscriptions'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk_ship_again_request_' . $user_id, '_wpnonce' ) ) {

		$subscriptions = $_REQUEST['jgtb_my_subscriptions'];
		$user_id       = get_current_user_id();
		$button        = $_REQUEST['submit_button'];

		switch ( $button ) {
			case 'ship_now_keep':
				if ( ! Utilities\is_ship_keep_available() ) {
					return;
				}

				$status = process_bulk_ship_now_keep( $user_id, $subscriptions );
				display_ship_now_notice( $status, $user_id );
				break;
			case 'ship_now_reschedule':
				if ( ! Utilities\is_ship_reschedule_available() ) {
					return;
				}

				$status = process_bulk_ship_now_reschedules( $user_id, $subscriptions );
				display_ship_now_notice( $status, $user_id );
				break;
		}
	}
}

/**
 * Routes the request to one of the utility methods in GD_CCC_SubscriptionUtilities. This class is extending that
 * one so the protected method can be accessed safely.
 */
function handle_bulk_edit_quantities() {
	$user_id = get_current_user_id();
	// dd( $_REQUEST, __FILE__ . ':' . __LINE__ );
	if ( isset( $_REQUEST['submit_button'] ) && isset( $_REQUEST['jgtb_my_subscriptions'] ) && isset( $_REQUEST['jgtb_all_subscriptions'] ) && isset( $_REQUEST['jgtb_all_subscriptions_hash'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk_ship_again_request_' . $user_id, '_wpnonce' ) ) {

		$all_subscriptions_hash = wp_hash( $_REQUEST['jgtb_all_subscriptions'], 'jgtb_edit_quantities' );
		$remote_hash            = $_REQUEST['jgtb_all_subscriptions_hash'];
		$subscriptions          = get_all_subscriptions( $_REQUEST['jgtb_all_subscriptions'], $all_subscriptions_hash, $remote_hash );
		$button                 = $_REQUEST['submit_button'];
		$ticked_subscriptions   = array_map( 'intval', $_REQUEST['jgtb_my_subscriptions'] );
		$ticked_subscriptions   = array_intersect( $subscriptions, $ticked_subscriptions );

		switch ( $button ) {
			case 'save_quantities':
				$status = process_bulk_change_quantities( $user_id, $ticked_subscriptions );
				if ( false !== $status ) {
					display_quantity_notice( $status, $user_id );
				}
				break;
		}
	}
}

/**
 * Utility function that extracts all the subscription IDs from a request and compares it against a hash we also
 * passed down.
 *
 * @return array                    array of subscription ids (or empty), or false, if there was a problem
 */
function get_all_subscriptions( $all_subscriptions, $all_subscriptions_hash, $remote_hash ) {

	// let's check whether values haven't been changed
	if ( $all_subscriptions_hash !== $remote_hash ) {
		// they have
		return false;
	}

	// let's expand the subscription list
	$all_subscriptions = maybe_unserialize( $all_subscriptions );

	if ( empty( $all_subscriptions ) ) {
		// there weren't any
		return false;
	}

	return $all_subscriptions;
}

/**
 * Loops through the tickboxes, and handles individual reschedules. Nonces for individual subscriptions are then
 * grabbed from the REQUEST array based on the current integer.
 *
 * @param  integer      $user_id                ID of the current user
 * @param  array        $subscription_ids       array of integer values based on checkboxes
 * @return array        $status
 */
function process_bulk_ship_now_keep( $user_id, $subscription_ids ) {
	$status = array(
		'succeeded' => array(),
		'failed'    => array(),
	);

	foreach ( $subscription_ids as $id ) {
		$subscription = wcs_get_subscription( $id );
		$nonce        = isset( $_REQUEST[ '_completed_' . $id ] ) ? $_REQUEST[ '_completed_' . $id ] : ''; // WPCS: CSRF ok. Will be double checked in process_ship_now_keep_date

		if ( Utilities\Process\process_ship_now_keep_date( $user_id, $subscription, $nonce ) ) {
			$status['succeeded'][] = $id;
		} else {
			$status['failed'][] = $id;
		}
	}

	return $status;
}

/**
 * Loops through the tickboxes, and handles individual reschedules. Nonces for individual subscriptions are then
 * grabbed from the REQUEST array based on the current integer.
 *
 * @param  integer      $user_id                ID of the current user
 * @param  array        $subscription_ids       array of integer values based on checkboxes
 * @return array        $status
 */
function process_bulk_ship_now_reschedules( $user_id, $subscription_ids ) {
	$status = array(
		'succeeded' => array(),
		'failed'    => array(),
	);

	foreach ( $subscription_ids as $id ) {
		$subscription = wcs_get_subscription( $id );
		$nonce        = isset( $_REQUEST[ '_completed_adjust_' . $id ] ) ? $_REQUEST[ '_completed_adjust_' . $id ] : ''; // WPCS: CSRF ok. Will be checked in the process_ship_now_adjust_date

		if ( Utilities\Process\process_ship_now_adjust_date( $user_id, $subscription, $nonce ) ) {
			$status['succeeded'][] = $id;
		} else {
			$status['failed'][] = $id;
		}
	}

	return $status;
}

/**
 * Loops through the tickboxes, and handles individual reschedules. Nonces for individual subscriptions are then
 * grabbed from the REQUEST array based on the current integer.
 *
 * @param  integer      $user_id                ID of the current user
 * @param  array        $subscriptions          array of integer values based on checkboxes
 * @return array        $status
 */
function process_bulk_change_quantities( $user_id, $subscriptions ) {
	if ( empty( $subscriptions ) ) {
		return false;
	}
	$status = array(
		'succeeded' => array(),
		'failed'    => array(),
	);

	foreach ( $subscriptions as $subscription_id ) {
		$subscription = wcs_get_subscription( $subscription_id );
		$nonce        = isset( $_REQUEST[ 'wcs_edit_details_of_' . $subscription_id ] ) ? $_REQUEST[ 'wcs_edit_details_of_' . $subscription_id ] : ''; // WPCS: CSRF ok. Will be checked in process_change_product_quantities

		$response = Utilities\Process\process_change_product_quantities( $user_id, $subscription, $nonce );

		if ( 1 === $response ) {
			$status['succeeded'][] = $subscription_id;
		} elseif ( -1 === $response ) {
			$status['failed'][] = $subscription_id;
		}
	}
	return $status;
}

/**
 * Displays notice about updating ship now requests.
 *
 * @param  array        $status     an array of ( 'succeeded' => array(), 'failed' => array() ) with subscription
 *                                  IDs as the values
 * @param  integer      $user_id    ID of the user requesting the change
 */
function display_ship_now_notice( $status, $user_id ) {
	$logger = wc_get_logger();
	$logger->debug( sprintf( 'Bulk edit quantity request from %s for the following subscriptions:', $user_id ), array( 'source' => 'jgtb_bulk_actions' ) );
	$logger->debug( print_r( $status, true ), array( 'source' => 'jgtb_bulk_actions' ) );

	if ( count( $status['failed'] ) ) {
		wc_add_notice( _x( 'Some subscriptions could not be processed. Please get in touch with us.', 'Notice when ship now failed for some subscriptions', 'jg-toolbox' ), 'notice' );
	} else {
		wc_add_notice( _x( 'We have processed all the subscriptions.', 'Notice when all ship now succeeded', 'jg-toolbox' ) );
	}
}

/**
 * Displays notice about updating quantities if any of them got changed or failed validation. Keeps silent if
 * quantities need not be changed.
 *
 * @param  array        $status     an array of ( 'succeeded' => array(), 'failed' => array() ) with subscription
 *                                  IDs as the values
 * @param  integer      $user_id    ID of the user requesting the change
 */
function display_quantity_notice( $status, $user_id ) {
	if ( empty( $status['succeeded'] ) && empty( $status['failed'] ) ) {
		return;
	}
	$logger = wc_get_logger();
	$logger->debug( sprintf( 'Bulk renewal request from user %s for the following subscriptions:', $user_id ), array( 'source' => 'jgtb_bulk_actions' ) );
	$logger->debug( print_r( $status, true ), array( 'source' => 'jgtb_bulk_actions' ) );

	if ( count( $status['failed'] ) ) {
		wc_add_notice( _x( 'Some item quantities could not be processed. Please get in touch with us.', 'Notice when some quantity did not update', 'jg-toolbox' ), 'notice' );
	} else {
		wc_add_notice( _x( 'We have updated the quantities on requested items.', 'Notice after all quantity updates succeeded.', 'jg-toolbox' ) );
	}
}
