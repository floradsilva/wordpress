<?php
namespace Javorszky\Toolbox\Utilities\Validate;
use Javorszky\Toolbox\Utilities as Utilities;

use WC_Subscriptions;

/**
 * Utility function that is used in all three validation scenarios for the common tasks of making sure there's a
 * subscription, and that the user actually owns the subscription.
 *
 * @param  integer          $user_id        ID of the user performing the request
 * @param  WC_Subscription  $subscription   the subscription object we're checking
 * @return boolean                          whether the request is valid for the user / subscription
 */
function validate_subscription_ownership( $user_id, $subscription ) {
	if ( ! wcs_is_subscription( $subscription ) ) {
		wc_add_notice( _x( 'That subscription does not exist. Please contact us if you need assistance.', 'Error notice while validating subscription ownership during all actions', 'jg-toolbox' ), 'error' );
		return false;

	} elseif ( ! user_can( $user_id, 'edit_shop_subscription_status', $subscription->get_id() ) ) {
		wc_add_notice( _x( 'That doesn\'t appear to be one of your subscriptions.', 'Error notice while validating subscription ownership during all actions.', 'jg-toolbox' ), 'error' );
		return false;
	}

	return true;
}


/**
 * Validates the request. Nonce needs to be correct so that we can't spam the same link but have to click the button
 * again (new nonce)
 *
 * User's ownership and subscription existing are already verified.
 *
 * @param  WC_Subscription  $subscription   the subscription we're skipping next payment on
 * @param  integer|string   $next_date      timestamp for when the previous next payment was. New next payment will be calculated from this
 * @param  string           $wpnonce        nonce generated based on subscription id and previous next payment timestamp
 * @return boolean                          true if the request is right
 */
function validate_skip_next_request( $subscription, $next_date, $wpnonce ) {

	if ( ! empty( $wpnonce ) && wp_verify_nonce( $wpnonce, $subscription->get_id() . $next_date ) === false ) {
		// translators: placeholder is an error code for better identification.
		wc_add_notice( sprintf( _x( 'Security error. Please contact us if you need assistance. %s.', 'Error notice when a skip next request fails nonce verification', 'jg-toolbox' ),  'E01' ), 'error' );
		return false;
	}

	return true;
}


/**
 * Validates the request part specific to the ship now keep date part. The nonce needs to be correct for completed
 * payment count and subscription ID to guard against spamming the link to charge the user multiple times.
 *
 * @param  WC_Subscription  $subscription   the subscription we're skipping next payment on
 * @param  integer|string   $completed_payments number of times the subscription's been paid for
 * @param  string           $wpnonce        nonce generated based on subscription id and previous next payment timestamp
 * @return boolean                          true if the request is right
 */
function validate_ship_now_keep_date( $subscription, $completed_payments, $wpnonce ) {
	if ( ! empty( $wpnonce ) && wp_verify_nonce( $wpnonce, $subscription->get_id() . '_completed_' . $completed_payments ) === false ) {
		// translators: Placeholder is for error code.
		wc_add_notice( sprintf( _x( 'Security error. Please contact us if you need assistance. %s.', 'Error notice when a ship now keep date fails nonce verification', 'jg-toolbox' ), 'E02' ), 'error' );
		return false;
	}

	return true;
}

/**
 * Validates the request part specific to the ship now adjust date part. The nonce needs to be correct for completed
 * payment count and subscription ID to guard against spamming the link to charge the user multiple times.
 *
 * @param  WC_Subscription  $subscription   the subscription we're skipping next payment on
 * @param  integer|string   $completed_payments number of times the subscription's been paid for
 * @param  string           $wpnonce        nonce generated based on subscription id and previous next payment timestamp
 * @return boolean                          true if the request is right
 */
function validate_ship_now_adjust_date( $subscription, $completed_payments, $wpnonce ) {
	if ( ! empty( $wpnonce ) && wp_verify_nonce( $wpnonce, $subscription->get_id() . '_completed_adjust_' . $completed_payments ) === false ) {
		// translators: placeholder is error code for easier identification of issue.
		wc_add_notice( sprintf( _x( 'Security error. Please contact us if you need assistance. %s.', 'Error notice when ship now adjust date fails nonce verification', 'jg-toolbox' ), 'E03' ), 'error' );
		return false;
	}

	return true;
}

/**
 * Make sure that our nonce is in order.
 *
 * @param  WC_Subscription  $subscription   the sub we're operating on
 * @param  string           $nonce          the nonce
 * @return boolean                          true if the nonce is in order
 */
function validate_edit_details( $subscription, $nonce ) {
	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wcs_edit_details_of_' . $subscription->get_id() ) === false ) {
		// translators: Placeholder is for error code.
		wc_add_notice( sprintf( _x( 'Security error. Please contact us if you need assistance. %s.', 'Error notice when edit details fails nonce verification', 'jg-toolbox' ), 'E04' ), 'error' );
		return false;
	}

	return true;
}


/**
 * Make sure that our nonce is in order.
 *
 * @param  WC_Subscription  $subscription   the sub we're operating on
 * @param  string           $nonce          the nonce
 * @return boolean                          true if the nonce is in order
 */
function validate_change_next_ship_date_request( $subscription, $nonce ) {
	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'change_next_ship_date_' . $subscription->get_id() . $subscription->get_date( 'next_payment', 'site' ) ) === false ) {
		// translators: Placeholder is for error code.
		wc_add_notice( sprintf( _x( 'Security error. Please contact us if you need assistance. %s.', 'Notice text when a validation error happens', 'jg-toolbox' ), 'E05.'), 'error' );
		return false;
	}

	return true;
}


/**
 * Makes sure that the same request can not be replayed to add infinite products to the same subscription. It's
 * based on the items already in the subscription, so it will change after every "add to existing" request.
 *
 * @param  WC_Subscription         $subscription the thing we're adding to
 * @param  string                  $nonce        security nonce belonging to the subscription
 * @return boolean                               whether the nonce is right for the subscription
 */
function validate_add_to_subscription_request( $subscription, $nonce ) {
	$items_string = Utilities\generate_nonce_on_items( $subscription );
	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'add_to_subscription_' . $items_string ) === false ) {
		wc_add_notice( 'Security error. Please contact us if you need assistance. E06.', 'error' );
		return false;
	}

	return true;
}


function validate_change_frequency_request( $subscription, $nonce ) {

	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'change_frequency_' . $subscription->get_id() . $subscription->get_billing_interval() . $subscription->get_billing_period() ) === false ) {
		wc_add_notice( 'Security error. Please contact us if you need assistance. E07.', 'error' );
		return false;
	}

	return true;
}
