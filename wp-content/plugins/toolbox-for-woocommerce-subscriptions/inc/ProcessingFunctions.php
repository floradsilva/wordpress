<?php
namespace Javorszky\Toolbox\Utilities\Process;

use Javorszky\Toolbox\Utilities\Validate as Validate;
use Javorszky\Toolbox\Utilities as Utilities;
use WCS_Download_Handler;
use DateTime;
use DateTimeZone;

/**
 * Recalculates the next date after the current next date. We need to adjust a filter, so calculate_date will base
 * the calculation on the currently set "next_payment" date.
 *
 * @param  integer                  $user_id    ID of the current user
 * @param  \WC_Subscription          $subscription Subscription we're shipping and keeping the date for
 * @param  string                   $nonce      a security nonce
 * @return boolean                              whether anything successfull happened
 */
function process_skip_next_date( $user_id, $subscription, $nonce ) {
	if ( ! wcs_is_subscription( $subscription ) ) {
		// there's nothing to do
		return false;
	}

	$old_next_timestamp = $subscription->get_time( 'next_payment' );
	$old_next_date      = $subscription->get_date( 'next_payment' );

	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_skip_next_request( $subscription, $old_next_timestamp, $nonce )
		&& apply_filters( 'jgtb_validate_skip_next_request', true, $subscription, $user_id, $old_next_timestamp )
		&& 'yes' === apply_filters( 'jgtb_allow_edit_date_for_subscription', 'yes', $subscription )
		) {
		add_filter( 'wcs_calculate_next_payment_from_last_payment', '__return_false' );
		$next_payment_date = $subscription->calculate_date( 'next_payment' );
		remove_filter( 'wcs_calculate_next_payment_from_last_payment', '__return_false' );

		$subscription->update_dates( array( 'next_payment' => $next_payment_date ) );
		$subscription->add_order_note( sprintf( "Customer chose to skip the next shipping and has set the next payment date \nfrom %s\nto %s.", $old_next_date, $next_payment_date ), false, true );

		do_action( 'jgtb_after_skip_next_date', $subscription, $user_id, $old_next_date, $next_payment_date );

		return true;
	}
	return false;
}

/**
 * A central place to handle processing the ship now keep date request for a subscription. Protected, so it can be
 * accessed from children classess too, but not outside, and handles all validations. Assuming nonces generated
 * with the base of $subscription->id . '_completed_' . $completed_payments.
 *
 * @param  integer                  $user_id    ID of the current user
 * @param  \WC_Subscription          $subscription Subscription we're shipping and keeping the date for
 * @param  string                   $nonce      a security nonce
 * @return boolean                              whether anything successfull happened
 */
function process_ship_now_keep_date( $user_id, $subscription, $nonce ) {
	if ( ! wcs_is_subscription( $subscription ) ) {
		// there's nothing to do
		return false;
	}

	$completed_payments = $subscription->get_payment_count( 'completed' );

	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_ship_now_keep_date( $subscription, $completed_payments, $nonce )
		&& apply_filters( 'jgtb_validate_ship_now_keep_date', true, $subscription, $user_id, $completed_payments )
		&& 'yes' === apply_filters( 'jgtb_allow_edit_date_for_subscription', 'yes', $subscription )
		) {
		Utilities\ship_now( $subscription );
		$subscription->add_order_note( sprintf( 'Customer created a new renewal order and chose to keep the next payment date as is: %s.', $subscription->get_date( 'next_payment' ) ), false, true );

		do_action( 'jgtb_after_ship_now_keep_date', $subscription, $user_id );

		return true;
	}
	return false;

}

/**
 * A central place to handle processing the ship now adjust date request for a subscription. Protected, so it can be
 * accessed from children classes too, but not outside, and handles all validations. Assuming nonces generated
 * with the base of $subscription->id . '_completed_' . $completed_payments.
 *
 * @param  integer                  $user_id    ID of the current user
 * @param  \WC_Subscription          $subscription Subscription we're shipping and keeping the date for
 * @param  string                   $nonce      a security nonce
 * @return boolean                              whether anything successful happened
 */
function process_ship_now_adjust_date( $user_id, $subscription, $nonce ) {
	if ( ! wcs_is_subscription( $subscription ) ) {
		// there's nothing to do
		return false;
	}

	$old_next_date      = $subscription->get_date( 'next_payment' );
	$completed_payments = $subscription->get_payment_count( 'completed' );

	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_ship_now_adjust_date( $subscription, $completed_payments, $nonce )
		&& apply_filters( 'jgtb_validate_ship_now_adjust_date', true, $subscription, $user_id, $completed_payments )
		&& 'yes' === apply_filters( 'jgtb_allow_edit_date_for_subscription', 'yes', $subscription )
		) {
		Utilities\ship_now( $subscription );
		$next_payment_date = Utilities\adjust_date( $subscription, $old_next_date );
		$subscription->add_order_note( sprintf( "Customer created a renewal order, and chose to have the next payment date rescheduled with relation to today\nfrom %s\nto %s.", $old_next_date, $next_payment_date ), false, true );

		do_action( 'jgtb_after_ship_now_adjust_date', $subscription, $user_id );

		return true;
	}
	return false;
}

/**
 * One central place to change the shipping address of a specific subscription. Nonce is in
 * $_REQUEST['jgtb_edit_details_of_' . $subscription->id] and is generated by
 * wcs_edit_details_of_ . $subscription->id. Address details are sieved from $_REQUEST by
 * $this->sieve_address_details and then a difference is calculated to only update what changed.
 *
 * @param  integer                  $user_id    ID of the current user
 * @param  \WC_Subscription          $subscription Subscription we're shipping and keeping the date for
 * @param  string                   $nonce      a security nonce
 * @return boolean                              whether anything successfull happened
 */
function process_change_shipping_address( $user_id, $subscription, $nonce ) {
	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_edit_details( $subscription, $nonce )
		&& apply_filters( 'jgtb_validate_edit_details', true, $subscription, $user_id )
		&& apply_filters( 'jgtb_validate_change_shipping_address', true, $subscription, $user_id )
		) {
		$old_shipping_addresses = $subscription->get_address( 'shipping' );

		$new_shipping_address = Utilities\sieve_address_details( $_REQUEST, 'shipping' ); // WPCS: CSRF ok

		$shipping_diff = Utilities\get_difference( $old_shipping_addresses, $new_shipping_address );

		do_action( 'jgtb_change_shipping_address', $old_shipping_addresses, $new_shipping_address, $shipping_diff, $user_id, $subscription );

		if ( ! empty( $shipping_diff ) ) {
			$subscription->set_address( $shipping_diff, 'shipping' );
			$subscription->add_order_note( sprintf( 'Customer chose to amend the shipping details with %s.', print_r( $shipping_diff, true ) ), false, true );
			return true;
		}
	}
	return false;
}

/**
 * One central place to change the billing address of a specific subscription. Nonce is in
 * $_REQUEST['jgtb_edit_details_of_' . $subscription->id] and is generated by
 * wcs_edit_details_of_ . $subscription->id. Address details are sieved from $_REQUEST by
 * $this->sieve_address_details and then a difference is calculated to only update what changed.
 *
 * @param  integer                  $user_id    ID of the current user
 * @param  \WC_Subscription          $subscription Subscription we're shipping and keeping the date for
 * @param  string                   $nonce      a security nonce
 * @return boolean                              whether anything successfull happened
 */
function process_change_billing_address( $user_id, $subscription, $nonce ) {
	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_edit_details( $subscription, $nonce )
		&& apply_filters( 'jgtb_validate_edit_details', true, $subscription, $user_id )
		&& apply_filters( 'jgtb_validate_change_billing_address', true, $subscription, $user_id )
		) {
		$old_billing_addresses = $subscription->get_address( 'billing' );

		$new_billing_address = Utilities\sieve_address_details( $_REQUEST, 'billing' ); // WPCS: CSRF ok

		$billing_diff = Utilities\get_difference( $old_billing_addresses, $new_billing_address );

		do_action( 'jgtb_change_billing_address', $old_billing_addresses, $new_billing_address, $billing_diff, $user_id, $subscription );

		if ( ! empty( $billing_diff ) ) {
			$subscription->set_address( $billing_diff, 'billing' );
			$subscription->add_order_note( sprintf( 'Customer chose to amend the billing details with %s.', print_r( $billing_diff, true ) ), false, true );
			return true;
		}
	}
	return false;
}

/**
 * One central place to change the product quantities of a specific subscription. Nonce is in
 * $_REQUEST['jgtb_edit_details_of_' . $subscription->id], and is generated by
 * wcs_edit_details_of_ . $subscription->id.
 *
 * @param integer                   $user_id
 * @param \WC_Subscription          $subscription
 * @param string                    $nonce
 * @return boolean                  whether anything successful happened
 * @throws \Exception
 */
function process_change_product_quantities( $user_id, $subscription, $nonce ) {

	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_edit_details( $subscription, $nonce )
		&& apply_filters( 'jgtb_validate_edit_details', true, $subscription, $user_id )
		&& apply_filters( 'jgtb_validate_change_product_quantities', true, $subscription, $user_id )
		&& 'yes' === apply_filters( 'jgtb_allow_edit_qty_for_subscription', 'yes', $subscription )
		) {
		// edit the quantity changes
		$qty_changed        = false;
		$subscription_items = $subscription->get_items();
		$changed            = [];
		if ( count( $subscription_items ) > 0 ) {
			foreach ( $subscription_items as $item_id => $item ) {
				$new_qty = ( isset( $_REQUEST[ 'new_quantity_' . $item_id ] ) ) ? $_REQUEST[ 'new_quantity_' . $item_id ] : null; // WPCS: CSRF ok
				$old_qty = $item->get_quantity();
				if ( null !== $new_qty && $new_qty != $old_qty ) { // WPCS: loose comparison ok
					$qty_changed         = true;
					$product_id          = wcs_get_canonical_product_id( $item );
					$product             = wc_get_product( absint( $product_id ) );
					$item_name           = wcs_get_line_item_name( $item );
					$changed[ $item_id ] = [
						'product_id' => $product_id,
						'old'        => $old_qty,
						'new'        => $new_qty,
					];

					if ( 0 === absint( $new_qty ) ) {
						// remove the item
						WCS_Download_Handler::revoke_downloadable_file_permission( $product_id, $subscription->get_id(), $subscription->get_user_id() );

						wc_update_order_item( $item_id, array( 'order_item_type' => 'line_item_removed' ) );

						$subscription->add_order_note( sprintf( 'Customer removed "%1$s" (Product ID: #%2$d) via the Edit Subscription Details page.', $item_name, $product_id ) );
					}

					// set prices
					$item->set_subtotal( ( $item->get_subtotal() / $old_qty ) * $new_qty );
					$item->set_total( ( $item->get_total() / $old_qty ) * $new_qty );

					// set quantity
					$item->set_quantity( $new_qty );

					// save
					$item->save();

					$subscription->add_order_note( sprintf( 'Customer changed quantity of "%1$s" from %2$s to %3$s.', $item_name, $old_qty, $new_qty ) );
				}
			}
		}

		if ( $qty_changed ) {
			// recalculate things
			$subscription->calculate_totals();
			$subscription->save();

			// need to differentiate between 3 things: this is succeeded
			do_action( 'jgtb_after_change_product_quantities', $subscription, $changed );
			return 1;
		}

		$subscription->save();

		// need to differentiate between 3 things: this is succeeded in not changing anything
		return 0;
	}

	// need to differentiate between 3 things: this is failed
	return -1;
}

/**
 * Change frequency. Hooked into priority 30, because this needs to happen after the change frequency processing is done.
 * Otherwise this would first change the next payment date of the subscription making the nonce that relied on the previous
 * next payment date invalid for the change next payment date function which would throw a notice then.
 *
 * I've gotten around that two ways:
 * 1. first change the next payment date, as the nonce for this function does not depend on the next payment date, so both
 *    will successfully run, and
 * 2. only run the change next payment date function if the user changed the next payment date.
 *
 * If the user changed the frequncy, the next payment date will be recalculated from today anyways though, so in that sense
 * any change that they've done to the next payment date will be irrelevant and they'd have to do it again.
 *
 * @param  integer                  $user_id           ID of the current user
 * @param  \WC_Subscription          $subscription      Subscription we're shipping and keeping the date for
 * @param  string                   $nonce             a security nonce
 * @param  integer                  $new_interval      a number between 1-6 inclusive by default
 * @param  string                   $new_period        day / week / month / year by default
 */
function process_change_frequency( $user_id, $subscription, $nonce, $new_interval, $new_period ) {
	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_change_frequency_request( $subscription, $nonce )
		&& apply_filters( 'jgtb_validate_change_frequency', true, $subscription, $user_id, $new_interval, $new_period )
		&& 'yes' === apply_filters( 'jgtb_allow_edit_date_for_subscription', 'yes', $subscription )
		) {
		$old_interval = $subscription->get_billing_interval();
		$old_period   = $subscription->get_billing_period();

		if ( $new_interval != $old_interval ) { // WPCS: loose comparison ok.
			$subscription->set_billing_interval( $new_interval );
		}

		if ( $new_period != $old_period ) { // WPCS: loose comparison ok.
			$subscription->set_billing_period( $new_period );
		}

		$next_ship_date = date( 'Y-m-d H:i:s', strtotime( '+' . $new_interval . ' ' . $new_period ) );
		$subscription->update_dates( array( 'next_payment' => $next_ship_date ) );

		$subscription->add_order_note( sprintf( 'Customer chose to change the frequency' . PHP_EOL . 'from %1$s %2$s' . PHP_EOL . 'to %3$s %4$s.', $old_interval, $old_period, $new_interval, $new_period ), false, true );
		$subscription->save();

		do_action( 'jgtb_after_change_frequency', $subscription, $old_interval, $old_period, $new_interval, $new_period, $next_ship_date );

		wc_add_notice( apply_filters( 'jgtb_frequency_updated_message', __( 'The subscription frequency has been updated!', 'jg-toolbox' ), $subscription ) );
	}
}

/**
 * Function to actually change the shipping date if it changed. Called from RescheduleNextRenewal, only if the user actually changed
 * the date in the input.
 *
 * @param  integer                  $user_id           ID of the current user
 * @param  \WC_Subscription          $subscription      Subscription we're shipping and keeping the date for
 * @param  string                   $nonce             a security nonce
 * @param  string                   $new_ship_date     the new date in Y-m-d format (2018-01-28)
 * @return int
 */
function process_change_next_shipping_date( $user_id, $subscription, $nonce, $new_ship_date ) {
	if (
		Validate\validate_subscription_ownership( $user_id, $subscription )
		&& Validate\validate_change_next_ship_date_request( $subscription, $nonce )
		&& Utilities\sanitize_new_date( $new_ship_date )
		&& apply_filters( 'jgtb_validate_change_next_ship_date', $subscription, $user_id, $new_ship_date )
		&& 'yes' === apply_filters( 'jgtb_allow_edit_date_for_subscription', 'yes', $subscription )
		) {

		$tz = new DateTimeZone( wc_timezone_string() );

		$old_ship_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription->get_date( 'next_payment', 'site' ), $tz );
		$old_end_date  = DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription->get_date( 'end', 'site' ), $tz );
		$new_ship_date = DateTime::createFromFormat( 'Y-m-d', $_REQUEST['new_ship_date'], $tz ); // WPCS: CSRF ok

		// compare if it's the same date...
		if ( ! $old_ship_date || $old_ship_date->format( 'Y-m-d' ) === $new_ship_date->format( 'Y-m-d' ) ) {
			// nothing to do
			return 0;
		}

		$new_ship_date->setTime( 11, 0, 0 );

		$new_ship_date = apply_filters( 'jgtb_new_ship_date', $new_ship_date, $subscription, $user_id );

		$dates = array(
			'next_payment' => $new_ship_date->format( 'Y-m-d H:i:s' ),
		);

		$note = sprintf( '<p>Customer chose to change the next shipment date<br>from %s<br>to %s.', $old_ship_date->format( 'Y-m-d H:i:s' ), $new_ship_date->format( 'Y-m-d H:i:s' ) );

		$adjust_end_date = apply_filters( 'jgtb_adjust_end_date_with_next_date', false, $subscription, $new_ship_date->format( 'Y-m-d H:i:s' ), $old_ship_date->format( 'Y-m-d H:i:s' ) );

		if ( $adjust_end_date ) {

			// normalize all of them
			$old_ship_date->setTime( 11, 0, 0 );
			$ship_diff = $old_ship_date->diff( $new_ship_date );

			$end_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription->get_date( 'end', 'site' ), $tz );

			$old_end_date = $end_date->format( 'Y-m-d H:i:s' );

			$end_date->add( $ship_diff );
			$end_date->setTime( 11, 0, 0 );

			$dates['end'] = $end_date->format( 'Y-m-d H:i:s' );

			$note .= sprintf( '<br>&nbsp;<br>The end date was also changed<br>from %s<br>to %s.', $old_end_date, $end_date->format( 'Y-m-d H:i:s' ) );
		}

		$note .= '</p>';
		$subscription->update_dates( $dates, 'site' );
		$subscription->add_order_note( $note, false, true );

		$subscription->save();

		do_action( 'jgtb_after_change_next_ship_date', $subscription, $user_id, $old_ship_date, $new_ship_date, $old_end_date, $adjust_end_date );

		wc_add_notice( apply_filters( 'jgtb_date_renewal_successful_message', __( 'Next shipment date has been successfully updated', 'jg-toolbox' ), $subscription ), 'success' );
	}
}
