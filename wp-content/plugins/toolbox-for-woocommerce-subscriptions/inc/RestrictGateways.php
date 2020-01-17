<?php
/**
 * File ithat houses functionality that makes working with PayPal Standard,
 * and other gateways that do not support editing details better.
 */
namespace Javorszky\Toolbox;

/**
 * Hooked into `jgtb_allow_edit_date_for_subscription` and
 * `jgtb_allow_edit_freq_for_subscription`, this will control
 * whether the change next shipping date is available for the current
 * subscription based on the gateway.
 *
 * @param  string          $allowed      'yes' or 'no'.
 * @param  WC_Subscription $subscription Instance of current subscription.
 * @return string                        'yes' or 'no'.
 */
function allow_date_change( $allowed, $subscription ) {
	if ( 'no' === $allowed ) {
		return $allowed;
	}

	$gateway = wc_get_payment_gateway_by_order( $subscription );

	return $gateway->supports( 'subscription_date_changes' ) ? 'yes' : 'no';
}
add_filter( 'jgtb_allow_edit_date_for_subscription', __NAMESPACE__ . '\\allow_date_change', 10, 2 );
add_filter( 'jgtb_allow_edit_freq_for_subscription', __NAMESPACE__ . '\\allow_date_change', 10, 2 );

/**
 * Hooked into `jgtb_allow_edit_qty_for_subscription`, this will remove the
 * quantity inputs from the edit subscription details page, and the bulk
 * update page from the view-subscriptions page if the payment gateway does
 * not support changing the amount of money being charged.
 *
 * @param  string          $allowed      'yes' or 'no'.
 * @param  WC_Subscription $subscription Instance of current subscription.
 * @return string                        'yes' or 'no'.
 */
function allow_edit_quantity( $allowed, $subscription ) {
	if ( 'no' === $allowed ) {
		return $allowed;
	}

	$gateway = wc_get_payment_gateway_by_order( $subscription );

	if ( false === $gateway ) {
		return $allowed;
	}

	return $gateway->supports( 'subscription_amount_changes' ) ? 'yes' : 'no';
}
add_filter( 'jgtb_allow_edit_qty_for_subscription', __NAMESPACE__ . '\\allow_edit_quantity', 10, 2 );

/**
 * Hooked into `wcs_view_subscription_actions` after this plugin has already
 * added the Ship Now / Skip Next buttons, and remove them, if the gateway
 * for the current subscription does not support changing the dates.
 *
 * @param  array           $actions List of actions that are available on the sub.
 * @param  WC_Subscription $sub     Instance of the current subscription.
 * @return array                    List of actions that should be on the sub.
 */
function remove_actions( $actions, $sub ) {
	$gateway = wc_get_payment_gateway_by_order( $sub );

	if ( ! $gateway->supports( 'subscription_date_changes' ) ) {
		$actions = array_diff_key(
			$actions,
			[
				'ship_now_keep_date'   => 1,
				'ship_now_recalculate' => 1,
				'skip_next'            => 1,
			]
		);
	}

	return $actions;
}
add_filter( 'wcs_view_subscription_actions', __NAMESPACE__ . '\\remove_actions', 20, 2 );
