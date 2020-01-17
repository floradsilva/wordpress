<?php
namespace Javorszky\Toolbox;

use WCS_Remove_Item;
?>
<table class="shop_table order_details">
	<thead>
		<tr>
			<?php
			if ( $allow_remove_item ) {
				?>
				<th class="product-remove" style="width: 3em;">&nbsp;</th>
				<?php
			}
			?>
			<th class="product-name"><?php echo esc_html_x( 'Product', 'table headings in notification email', 'jg-toolbox' ); ?></th>
			<th class="product-total"><?php echo esc_html_x( 'Total', 'table heading', 'jg-toolbox' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$subscription_items = $subscription->get_items();
		if ( count( $subscription_items ) > 0 ) {

			foreach ( $subscription_items as $item_id => $item ) {
				$_product = apply_filters( 'woocommerce_subscriptions_order_item_product', $subscription->get_product_from_item( $item ), $item );

				if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $subscription ) ); ?>">
						<?php if ( $allow_remove_item ) : ?>
							<td class="remove_item"><a href="<?php echo esc_url( WCS_Remove_Item::get_remove_url( $subscription->get_id(), $item_id ) ); ?>" class="remove" onclick="return confirm('<?php printf( esc_html__( 'Are you sure you want remove this item from your subscription?', 'jg-toolbox' ) ); ?>' );">&times;</a></td>
						<?php endif; ?>
						<td class="product-name">
							<?php
							if ( $_product && ! $_product->is_visible() ) {
								echo esc_html( apply_filters( 'woocommerce_order_item_name', $item['name'], $item ) );
							} else {
								echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', sprintf( '<a href="%s">%s</a>', get_permalink( $item['product_id'] ), $item['name'] ), $item ) );
							}
							?>
							<strong class="product-quantity">
								&times;
								<?php
								if ( 'no' !== $allow_edit_qty ) {
									?>
									<input type="number" name="new_quantity_<?php echo esc_attr( $item_id ); ?>" min="0" step="1" max="999" value="<?php echo esc_attr( $item['qty'] ); ?>" style="display: inline-block;width: 4rem">
									<?php
								} else {
									echo esc_html( $item->get_quantity() );
								}
								?>
							</strong>
							<?php

							// Allow other plugins to add additional product information here
							do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $subscription );

							$item->get_formatted_meta_data();

							if ( $_product && $_product->exists() && $_product->is_downloadable() && $subscription->is_download_permitted() ) {

								$download_files = $subscription->get_item_downloads( $item );
								$i              = 0;
								$links          = array();

								foreach ( $download_files as $download_id => $file ) {
									$i++;
									// translators: %1$s is the number of the file, %2$s: the name of the file
									$link_text = sprintf( _nx( 'Download file %1$s: %2$s', 'Download file %1$s: %2$s', count( $download_files ), 'Used as link text in view-subscription template', 'jg-toolbox' ), $i, $file['name'] );
									$links[]   = '<small><a href="' . esc_url( $file['download_url'] ) . '">' . esc_html( $link_text ) . '</a></small>';
								}

								echo '<br/>' . wp_kses_post( implode( '<br/>', $links ) );
							}

							// Allow other plugins to add additional product information here
							do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $subscription );
							?>
						</td>
						<td class="product-total">
							<?php echo wp_kses_post( $subscription->get_formatted_line_subtotal( $item ) ); ?>
						</td>
					</tr>
					<?php
				}

				$purchase_note = get_post_meta( $_product->get_id(), '_purchase_note', true );

				if ( $subscription->has_status( array( 'completed', 'processing' ) ) && $purchase_note ) {
					?>
					<tr class="product-purchase-note">
						<td colspan="3"><?php echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) ); ?></td>
					</tr>
					<?php
				}
			}
		}
		?>
	</tbody>

</table>
