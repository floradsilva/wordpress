<?php
namespace Javorszky\Toolbox;

add_filter( 'wcs_view_subscription_actions', __NAMESPACE__ . '\\add_skip_next_action', 10, 2 );
add_action( 'wp_loaded', __NAMESPACE__ . '\\handle_skip_next_request', 1 );


/**
 * Extra actions on the subscription. Only there if the subscription is active.
 *
 * @param array           $actions        existing actions on the subscription.
 * @param WC_Subscription $subscription    the subscription we're adding new actions to.
 * @return array          $actions
 */
function add_skip_next_action( $actions, $subscription ) {
	$next_timestamp = $subscription->get_time( 'next_payment' );
	$trial_end_time = $subscription->get_time( 'trial_end' );

	if ( 0 != $next_timestamp && 'active' == $subscription->get_status() && $trial_end_time <= current_time( 'timestamp', true ) ) {

		$new_actions = array(
			'skip_next' => array(
				'url'  => get_skip_next_link( $subscription ),
				'name' => Utilities\replace_key_dates( Utilities\get_button_text( 'skip_next_button_text', 'Skip next scheduled shipment' ), $subscription ),
			),
		);

		$actions = array_merge( $actions, $new_actions );
	}

	return $actions;
}


/**
 * URL to be used on the "Skip next delivery" button.
 *
 * @param  WC_Subscription  $subscription   Subscription we're getting the link for
 * @return string                           URL to trigger skipping the next delivery with
 */
function get_skip_next_link( $subscription ) {
	$next_date = $subscription->get_time( 'next_payment' );

	$action_link = Utilities\strip_custom_query_args();
	$action_link = add_query_arg( array( 'subscription_id' => $subscription->get_id(), 'skip_next_shipping' => 1 ), $action_link );
	$action_link = wp_nonce_url( $action_link, $subscription->get_id() . $next_date );

	return $action_link;
}


/**
 * Hooked into `wp_loaded`, this is responsible for kicking off code to actually change the next payment date if
 * certain GET variables are present.
 */
function handle_skip_next_request() {
	if ( isset( $_GET['skip_next_shipping'] ) && isset( $_GET['subscription_id'] ) && isset( $_GET['_wpnonce'] ) && !isset( $_GET['wc-ajax'] )  ) {
		$user_id      = get_current_user_id();
		$subscription = wcs_get_subscription( $_GET['subscription_id'] );
		$nonce        = $_GET['_wpnonce'];
		if ( ! wcs_is_subscription( $subscription ) ) {
			// there's nothing to do
			return;
		}

		if ( Utilities\Process\process_skip_next_date( $user_id, $subscription, $nonce ) ) {
			wc_add_notice( _x( 'Your next shipment will be skipped.', 'Notice after skip next date succeeded.', 'jg-toolbox' ) );
			wp_safe_redirect( wc_get_endpoint_url( 'view-subscription', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
	}
}
