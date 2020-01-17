<?php
namespace Javorszky\Toolbox;

/**
 * Edit Subscription
 *
 * Makes the details of a particular subscription editable
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( empty( $subscription ) ) {
	global $wp;

	if ( ! isset( $wp->query_vars[JGTB_EDIT_SUB_ENDPOINT] ) || 'shop_subscription' !== get_post_type( absint( $wp->query_vars[JGTB_EDIT_SUB_ENDPOINT] ) ) || ! current_user_can( 'view_order', absint( $wp->query_vars[JGTB_EDIT_SUB_ENDPOINT] ) ) ) {
		echo '<div class="woocommerce-error">' . esc_html__( 'Invalid Subscription.', 'jg-toolbox' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html( 'My Account' ) . '</a></div>';
		return;
	}

	$subscription = wcs_get_subscription( $wp->query_vars[JGTB_EDIT_SUB_ENDPOINT] );
}

wc_print_notices();
?>
<form action="<?php echo esc_url( wc_get_endpoint_url( 'view-subscription', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ) ); ?>" method="POST">
	<table class="shop_table subscription_details">
		<tr>
			<td><?php esc_html_e( 'Status', 'jg-toolbox' ); ?></td>
			<td><?php echo esc_html( wcs_get_subscription_status_name( $subscription->get_status() ) ); ?></td>
		</tr>
		<tr>
			<td><?php echo esc_html_x( 'Start Date', 'table heading', 'jg-toolbox' ); ?></td>
			<td><?php echo esc_html( $subscription->get_date_to_display( 'date_created' ) ); ?></td>
		</tr>
		<input type="hidden" name="edit_subscription_id" value="<?php echo esc_attr( $subscription->get_id() ); ?>">

		<?php
		foreach ( array(
			'last_order_date_paid' => _x( 'Last Payment Date', 'admin subscription table header', 'jg-toolbox' ),
			'next_payment'         => _x( 'Next Payment Date', 'admin subscription table header', 'jg-toolbox' ),
			'end'                  => _x( 'End Date', 'table heading', 'jg-toolbox' ),
			'trial_end'            => _x( 'Trial End Date', 'admin subscription table header', 'jg-toolbox' ),
		) as $date_type => $date_title ) {

			$date = $subscription->get_date( $date_type );
			if ( ! empty( $date ) ) {
				?>
				<tr>
					<td><?php echo esc_html( $date_title ); ?></td>
					<td><?php echo esc_html( $subscription->get_date_to_display( $date_type ) ); ?></td>
				</tr>
				<?php
			}
		}

		$allow_frequency_change = apply_filters( 'jgtb_allow_edit_freq_for_subscription', get_option( JGTB_OPTION_PREFIX . 'freq_change_edit_sub_details', 'yes' ), $subscription );

		if ( 'no' !== $allow_frequency_change ) {
			// get change frequency markup
			wc_get_template( 'myaccount/choose-new-frequency.php', array( 'subscription' => $subscription ), '', JGTB_PATH . 'templates/' );
		}

		$allow_date_change = apply_filters( 'jgtb_allow_edit_date_for_subscription', 'yes', $subscription );

		if ( 'no' !== $allow_date_change ) {
			// get change next date markup
			wc_get_template( 'myaccount/choose-next-ship-date.php', array( 'subscription' => $subscription ), '', JGTB_PATH . 'templates/' );
		}
		?>

	</table>

	<header>
		<h2><?php echo esc_html_x( 'Products', 'Heading on the edit subscription details page.', 'jg-toolbox' ); ?></h2>
	</header>

	<?php
	$allow_remove_item = wcs_can_items_be_removed( $subscription );
	// The value of this is eihter (string) 'no', or anything else. The value is a binary 'yes' / 'no'. The code checks if it's not 'no' in the rest of the plugin
	$allow_edit_qty = apply_filters( 'jgtb_allow_edit_qty_for_subscription', get_option( JGTB_OPTION_PREFIX . 'qty_change_edit_sub_details', 'yes' ), $subscription );

	wc_get_template(
		'myaccount/edit-subscription-products.php',
		array(
			'subscription'      => $subscription,
			'allow_remove_item' => $allow_remove_item,
			'allow_edit_qty'    => $allow_edit_qty,
		),
		'',
		JGTB_PATH . 'templates/'
	);
	?>

	<header>
		<h2><?php echo esc_html_x( 'Customer details', 'Heading on the edit subscription details page', 'jg-toolbox' ); ?></h2>
	</header>
	<table class="shop_table shop_table_responsive customer_details">
		<?php
		if ( $subscription->get_billing_email() ) {
			// translators: there is markup here, hence can't use Email: %s
			echo '<tr><th>' . esc_html_x( 'Email', 'heading in customer details on subscription detail page', 'jg-toolbox' ) . '</th><td data-title="' . esc_attr_x( 'Email', 'Used in data attribute for a td tag, escaped.', 'jg-toolbox' ) . '">' . esc_html( $subscription->get_billing_email() ) . '</td></tr>';
		}

		if ( $subscription->get_billing_phone() ) {
			// translators: there is markup here, hence can't use Email: %s
			echo '<tr><th>' . esc_html_x( 'Tel', 'heading in customer details on subscription detail page', 'jg-toolbox' ) . '</th><td data-title="' . esc_attr_x( 'Telephone', 'Used in data attribute for a td tag, escaped.', 'jg-toolbox' ) . '">' . esc_html( $subscription->get_billing_phone() ) . '</td></tr>';
		}

		// Additional customer details hook
		do_action( 'woocommerce_order_details_after_customer_details', $subscription );
		?>
	</table>

	<?php if ( ! wc_ship_to_billing_address_only() && $subscription->needs_shipping_address() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) : ?>

	<div class="col2-set addresses">

		<div class="col-1">

	<?php endif; ?>

			<address>
				<?php
				$billing_address = get_address( 'billing', $subscription );

				wc_get_template(
					'myaccount/edit-subscription-address.php',
					array(
						'load_address' => 'billing',
						'address'      => $billing_address,
					),
					'',
					JGTB_PATH . 'templates/'
				);
				?>
			</address>

	<?php if ( ! wc_ship_to_billing_address_only() && $subscription->needs_shipping_address() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) : ?>

		</div><!-- /.col-1 -->

		<div class="col-2">

			<address>
				<?php
				$shipping_address = get_address( 'shipping', $subscription );

				wc_get_template(
					'myaccount/edit-subscription-address.php',
					array(
						'load_address' => 'shipping',
						'address'      => $shipping_address,
					),
					'',
					JGTB_PATH . 'templates/'
				);
				?>
			</address>

		</div><!-- /.col-2 -->

	</div><!-- /.col2-set -->

	<?php endif; ?>

	<div class="clear"></div>
	<input type="hidden" name="jgtb_edit_subscription_details" value="<?php echo esc_attr( $subscription->get_id() ); ?>">
	<?php wp_nonce_field( 'wcs_edit_details_of_' . $subscription->get_id(), 'jgtb_edit_details_of_' . $subscription->get_id() ); ?>
	<button type="submit" name="edit-subscription-button" value=1><?php echo esc_html_x( 'Save Details', 'Button text on Edit Subscription page', 'jg-toolbox' ); ?></button>
</form>
