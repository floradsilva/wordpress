<?php
/**
 * Edit address form without the form tags
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title = ( 'billing' === $load_address ) ? __( 'Billing Address', 'jg-toolbox' ) : __( 'Shipping Address', 'jg-toolbox' );

if ( ! $load_address ) {
	wc_get_template( 'myaccount/my-address.php' );
} else {
	?>
	<h3>
		<?php echo esc_html( apply_filters( 'woocommerce_my_account_edit_address_title', $page_title ) ); ?>
	</h3>

	<?php
	do_action( "woocommerce_before_edit_address_form_{$load_address}" );

	foreach ( $address as $key => $field ) {
		woocommerce_form_field( $key, $field, ! empty( $_POST[ $key ] ) ? wc_clean( $_POST[ $key ] ) : $field['value'] ); // WPCS: CSRF ok.
	}

	do_action( "woocommerce_after_edit_address_form_{$load_address}" );
}
