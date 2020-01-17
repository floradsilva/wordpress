<tr class="wp-updater-license-row <?php echo sanitize_html_class( $plugin->get_license_status() ); ?>">
	<td colspan="5">
		<label><?php _e( 'License' ); ?>&nbsp;
			<input
				type="text"
				value="<?php echo esc_attr( $plugin->get_license_key() ); ?>"
				class="wp-updater-license-input"
				placeholder="<?php _e( 'Your license key' ); ?>"
				data-plugin="<?php echo esc_attr( $plugin->plugin_basename ); ?>"
			>
		</label>
		<span class="waiting spinner" style="float: none; vertical-align: top;"></span><?php

		if ( $plugin->get_license_status() == 'expired' ) {
			?><em><?php _e( 'Your license has expired. Please renew it to receive plugin updates' ); ?></em><?php
		} else {
			?><em><?php _e( 'Enter your license info and press return to activate it' ); ?></em><?php
		}

		if ( $plugin->client->is_update_available() && $plugin->get_license_status() != 'valid' ) {
			require 'html-invalid-update-available.php';
		}

	?></td>
</tr>