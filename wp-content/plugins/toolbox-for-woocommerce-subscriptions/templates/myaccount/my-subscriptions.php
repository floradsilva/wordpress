<?php
/**
 * My Subscriptions section on the My Account page
 *
 * @author      Gabor Javorszky
 * @category    WooCommerce Subscriptions/Templates
 * @version     2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="woocommerce_account_subscriptions">
	<?php
	if ( ! empty( $subscriptions ) ) {
		?>
		<form action="#" method="post" name="jgtb_my_subscriptions_form">
			<?php
			$user_id = get_current_user_id();
			// used in checking to see whether the change request came from this page
			wp_nonce_field( 'bulk_ship_again_request_' . $user_id, '_wpnonce', false );

			if ( Javorszky\Toolbox\Utilities\is_ship_reschedule_available() ) {
				?>
				<button type="submit" name="submit_button" value="ship_now_reschedule">Ship Now / Reschedule</button>
				<?php
			}

			if ( Javorszky\Toolbox\Utilities\is_ship_keep_available() ) {
				?>
				<button type="submit" name="submit_button" value="ship_now_keep">Ship Now / Keep</button>
				<?php
			}
			?>
			<button type="submit" name="submit_button" value="save_quantities">Save Quantities</button>

			<table class="shop_table shop_table_responsive my_account_subscriptions my_account_orders">

				<thead>
					<tr>
						<th class="subscription-id order-number"><span class="nobr"><?php esc_html_e( 'Subscription', 'jg-toolbox' ); ?></span></th>
						<th class="subscription-status order-status"><span class="nobr"><?php esc_html_e( 'Status', 'jg-toolbox' ); ?></span></th>
						<th class="subscription-next-payment order-date"><span class="nobr"><?php echo esc_html_x( 'Next Payment', 'table heading', 'jg-toolbox' ); ?></span></th>
						<th class="subscription-total order-total"><span class="nobr"><?php echo esc_html_x( 'Total', 'table heading', 'jg-toolbox' ); ?></span></th>
					</tr>
				</thead>

				<tbody>
					<?php
					$all_subscriptions = array(); // used in the edit quantities update

					foreach ( $subscriptions as $subscription ) {
						$subscription_id = $subscription->get_id();
						?>
						<tr class="order">
							<td class="subscription-id order-number" data-title="<?php esc_attr_e( 'ID', 'jg-toolbox' ); ?>">
								<input type="checkbox" name="jgtb_my_subscriptions[]" value="<?php echo esc_attr( $subscription_id ); ?>">
								<?php
								$all_subscriptions[] = $subscription_id;
								$completed_payments  = $subscription->get_payment_count( 'completed' );
								wp_nonce_field( $subscription_id . '_completed_' . $completed_payments, '_completed_' . $subscription_id, false );
								wp_nonce_field( $subscription_id . '_completed_adjust_' . $completed_payments, '_completed_adjust_' . $subscription_id, false );
								wp_nonce_field( 'wcs_edit_details_of_' . $subscription_id, 'jgtb_edit_details_of_' . $subscription_id, false );

								// translators: placeholder is order number for subscription
								$link = sprintf( _x( '#%s', 'hash before order number', 'jg-toolbox' ), $subscription->get_order_number() );
								?>
								<a href="<?php echo esc_url( $subscription->get_view_order_url() ); ?>"><?php echo esc_html( $link ); ?></a>
								<?php do_action( 'woocommerce_my_subscriptions_after_subscription_id', $subscription ); ?>
							</td>
							<td class="subscription-status order-status" style="text-align:left; white-space:nowrap;" data-title="<?php esc_attr_e( 'Status', 'jg-toolbox' ); ?>">
								<?php echo esc_attr( wcs_get_subscription_status_name( $subscription->get_status() ) ); ?>
							</td>
							<td class="subscription-next-payment order-date" data-title="<?php echo esc_attr_x( 'Next Payment', 'table heading', 'jg-toolbox' ); ?>">
								<?php echo esc_attr( $subscription->get_date_to_display( 'next_payment' ) ); ?>
								<?php if ( ! $subscription->is_manual() && $subscription->has_status( 'active' ) && $subscription->get_time( 'next_payment' ) > 0 ) : ?>
									<?php
									// translators: placeholder is the display name of a payment gateway a subscription was paid by
									$payment_method_to_display = sprintf( __( 'Via %s', 'jg-toolbox' ), $subscription->get_payment_method_to_display() );
									$payment_method_to_display = apply_filters( 'woocommerce_my_subscriptions_payment_method', $payment_method_to_display, $subscription );
									?>
								<br/><small><?php echo esc_attr( $payment_method_to_display ); ?></small>
								<?php endif; ?>
							</td>
							<td class="subscription-total order-total" data-title="<?php echo esc_attr_x( 'Total', 'Used in data attribute. Escaped', 'jg-toolbox' ); ?>">
								<?php echo wp_kses_post( $subscription->get_formatted_order_total() ); ?>
								<a href="<?php echo esc_url( $subscription->get_view_order_url() ); ?>" class="button view">View</a>
								<?php do_action( 'woocommerce_my_subscriptions_actions', $subscription ); ?>
							</td>
						</tr>
						<?php
					}
					$serialized_all_subscriptions = maybe_serialize( $all_subscriptions );
					$hash                         = wp_hash( $serialized_all_subscriptions, 'jgtb_edit_quantities' );
					?>
					<input type="hidden" name="jgtb_all_subscriptions" value="<?php echo esc_attr( $serialized_all_subscriptions ); ?>">
					<input type="hidden" name="jgtb_all_subscriptions_hash" value="<?php echo esc_attr( $hash ); ?>">
				</tbody>
			</table>
		</form>
		<?php
	} else {
		?>
		<p class="no_subscriptions">
			<?php
			// translators: placeholders are opening and closing link tags to take to the shop page. Don't change the order
			printf( esc_html__( 'You have no active subscriptions. Find your first subscription in the %1$sstore%2$s.', 'jg-toolbox' ), '<a href="' . esc_url( apply_filters( 'woocommerce_subscriptions_message_store_url', get_permalink( wc_get_page_id( 'shop' ) ) ) ) . '">', '</a>' );
			?>
		</p>
		<?php
	}
	?>
</div>
<?php
