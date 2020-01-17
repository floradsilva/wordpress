<?php
namespace Javorszky\Toolbox;

add_filter( 'wcs_view_subscription_actions', __NAMESPACE__ . '\\add_ship_reschedule_action', 10, 2 );
add_action( 'wp_loaded', __NAMESPACE__ . '\\handle_ship_now_adjust_date_request' );

/**
 * Extra actions on the subscription. Only there if the subscription is active.
 *
 * @param array             $actions        existing actions on the subscription
 * @param \WC_Subscription   $subscription    the subscription we're adding new actions to
 * @return array            $actions
 */
function add_ship_reschedule_action( $actions, $subscription ) {
	$next_timestamp = $subscription->get_time( 'next_payment' );

	if ( 0 != $next_timestamp && 'active' == $subscription->get_status() ) {

		$new_actions = array(
			'ship_now_recalculate' => array(
				'url' => get_ship_now_adjust_date_link( $subscription ),
				'name' => Utilities\replace_key_dates( Utilities\get_button_text( 'ship_reschedule_button_text', 'Ship now and recalculate from today' ), $subscription ),
			),
		);

		$actions = array_merge( $actions, $new_actions );
	}

	return $actions;
}

/**
 * URL to be used on the "Ship now and adjust the date" button.
 *
 * @param  \WC_Subscription  $subscription   Subscription we're getting the link for
 * @return string                           URL to trigger shipping now and keeping the date with
 */
function get_ship_now_adjust_date_link( $subscription ) {
	$completed_payments = $subscription->get_payment_count( 'completed' );

	$action_link = Utilities\strip_custom_query_args();
	$action_link = add_query_arg( array( 'subscription_id' => $subscription->get_id(), 'ship_now_adjust_date' => 1 ), $action_link );
	$action_link = wp_nonce_url( $action_link, $subscription->get_id() . '_completed_adjust_' . $completed_payments );

	return $action_link;
}

/**
 * Hooked into `wp_loaded`, this is responsible for charging the subscription now and adjusting the date if certain
 * GET variables are present.
 */
function handle_ship_now_adjust_date_request() {
	if ( isset( $_GET['ship_now_adjust_date'] ) && isset( $_GET['subscription_id'] ) && isset( $_GET['_wpnonce'] ) && !isset( $_GET['wc-ajax'] )  ) {
		$user_id      = get_current_user_id();
		$subscription = wcs_get_subscription( $_GET['subscription_id'] );
		$nonce        = $_GET['_wpnonce'];


		if ( Utilities\Process\process_ship_now_adjust_date( $user_id, $subscription, $nonce ) ) {
			wc_add_notice( _x( 'Your order has been placed!', 'Notice after ship now adjust date request succeeded.', 'jg-toolbox' ) );
			wp_safe_redirect( wc_get_endpoint_url( 'view-subscription', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
	}
}

