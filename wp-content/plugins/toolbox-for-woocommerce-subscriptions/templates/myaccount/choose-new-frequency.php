<?php
// every / every 2nd / every 3rd...
$intervals = apply_filters( 'jgtb_change_intervals', wcs_get_subscription_period_interval_strings(), $subscription );

// day / week / month / year
$periods = apply_filters( 'jgtb_change_periods', wcs_get_available_time_periods(), $subscription );
?>
<tr>
	<td>
		<p><?php esc_html_e( 'Change Frequency', 'jg-toolbox' ); ?></p>
	</td>
	<td>
		<?php
		if ( 'cancelled' !== $subscription->get_status() ) {
			echo ( isset( $embed_form ) && $embed_form ) ? '<form action="" method="POST">' : '';
			wp_nonce_field( 'change_frequency_' . $subscription->get_id() . $subscription->get_billing_interval() . $subscription->get_billing_period(), 'jgtb_change_frequency_nonce', false );
			?>
			<select name="new_interval" id="new_interval">
				<?php
				foreach ( $intervals as $interval => $label ) {
					?>
					<option value="<?php echo esc_attr( $interval ); ?>" <?php selected( $subscription->get_billing_interval(), $interval ); ?>><?php echo esc_html( $label ); ?></option>
					<?php
				}
				?>
			</select>
			<select name="new_period" id="new_period">
				<?php
				foreach ( $periods as $period => $label ) {
					?>
					<option value="<?php echo esc_attr( $period ); ?>" <?php selected( $subscription->get_billing_period(), $period ); ?>><?php echo esc_html( $label ); ?></option>
					<?php
				}
				?>
			</select>
			<?php
			echo ( isset( $embed_form ) && $embed_form ) ? '<button type="submit">' . esc_html_x( 'Go', 'Button to submit date change', 'jg-toolbox' ) . '</button></form>' : '';
		} else {
			?>
			<label>
				<?php echo esc_html( $options[ $subscription->get_billing_interval() ] ); ?>
			</label>
			<?php
		}
		?>
	</td>
</tr>
<?php

