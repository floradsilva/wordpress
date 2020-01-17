<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
* Tools class
*/
class Learndash_WooCommerce_Tools {
	
	/**
	 * Hook functions
	 */
	public function __construct() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'course_retroactive_access_tool' ) );
		add_action( 'learndash_woocommerce_cron', array( $this, 'cron_execute_action_queue' ) );
		add_action( 'admin_notices', array( $this, 'output_notice' ) );
	}

	/**
	 * Add tools button for LD WooCommerce
	 * 
	 * @param  array  $tools Existing tools
	 * @return array         New tools
	 */
	public function course_retroactive_access_tool( $tools ) {
		$tools['learndash_retroactive_access'] = array(
			'name' => __( 'LearnDash retroactive course access', 'learndash-woocommerce' ),
			'button' => __( 'Check LearnDash course access', 'learndash-woocommerce' ),
			'desc' => __( 'Check LearnDash course access of WooCommerce integration. Enroll and unenroll users according to WooCommerce purchase/subscription data.', 'learndash-woocommerce' ),
			'callback' => array( $this, 'execute_course_retroactive_access' ),
		);

		return $tools;
	}

	/**
	 * Callback for retroactive access tool action
	 */
	public function execute_course_retroactive_access() {
		$options = get_option( 'learndash_woocommerce_settings', array() );
		$options['action_queue'][] = array( 'name' => 'retroactive_access' );
		update_option( 'learndash_woocommerce_settings', $options );

		return __( 'The process is being done in the background. Please wait a while for the process to be finished.', 'learndash-woocommerce' );
	}

	/**
	 * Execute action queue
	 *
	 * Hooked to once per minute cron schedule.
	 */
	public function cron_execute_action_queue() {
		$lock_file = WP_CONTENT_DIR . '/uploads/learndash/learndash-woocommerce/process-lock.txt';
		$dirname   = dirname( $lock_file );

		if ( ! is_dir( $dirname ) ) {
			wp_mkdir_p( $dirname );
		}

		$lock_fp   = fopen( $lock_file, 'c+' );

		// Now try to get exclusive lock on the file. 
		if ( ! flock( $lock_fp, LOCK_EX | LOCK_NB ) ) { 
			// If you can't lock then abort because another process is already running
			exit(); 
		}

		$options = get_option( 'learndash_woocommerce_settings', array() );

		if ( ! isset( $options['action_queue'] ) || ( isset( $options['action_queue'] ) && empty( $options['action_queue'] ) ) ) {
			return;
		}

		foreach ( $options['action_queue'] as $key => $action ) {
			switch ( $action['name'] ) {
				case 'retroactive_access':
					$this->check_retroactive_access( $key, $action );
					break;
			}

			// unset( $options['action_queue'][ $key ] );
		}

		// update_option( 'learndash_woocommerce_settings', $options );
	}

	/**
	 * Check retroactive access tool function
	 */
	public function check_retroactive_access( $key, $args ) {
		// Process orders and subscription in batch
		$limit  = isset( $args['limit'] ) && is_numeric( $args['limit'] ) ? $args['limit'] : 50;
		$page   = isset( $args['page'] ) && is_numeric( $args['page'] ) ? $args['page'] : 1;
		$offset = ( $page - 1 ) * $limit;

		// Get orders
		$orders = wc_get_orders( array(
			'limit'  => $limit,
			'offset' => $offset,
			'order'  => 'ASC',
		) );
		// Foreach orders
		foreach ( $orders as $order ) {
			$status = $order->get_status();
			$id     = $order->get_id();

			switch ( $status ) {
				case 'completed':
				case 'processing':
					Learndash_WooCommerce::add_course_access( $id );
					break;
				
				case 'pending':
				case 'on-hold':
				case 'cancelled':
				case 'refunded':
				case 'failed':
					Learndash_WooCommerce::remove_course_access( $id );
					break;
			}
		}

		$subscriptions = array();
		if ( function_exists( 'wcs_get_subscriptions' ) ) {
			// Get subscriptions
			$subscriptions = wcs_get_subscriptions( array(
				'subscriptions_per_page' => $limit,
				'offset'                 => $offset,
				'order'                  => 'ASC',
			) );

			foreach ( $subscriptions as $subscription ) {
				$status = $subscription->get_status();
				$id     = $subscription->get_id();

				switch ( $status ) {
					case 'active':
						Learndash_WooCommerce::add_subscription_course_access( $subscription );
						break;
					
					case 'cancelled':
					case 'on-hold':
					case 'expired':
						Learndash_WooCommerce::remove_subscription_course_access( $subscription );
						break;
				}
			}	
		}

		$options = get_option( 'learndash_woocommerce_settings', array() );
		unset( $options['action_queue'][ $key ] );

		// Exit if both $orders and $subscriptions are empty, no next batch
		if ( empty( $orders ) && empty( $subscriptions ) ) {
			if ( ! in_array( 'retroactive_access', $options['action_queue_success'] ) ) {
				$options['action_queue_success'][] = 'retroactive_access';
			}
			update_option( 'learndash_woocommerce_settings', $options );
			return;
		}

		// Add action queue for the next iteration
		$options['action_queue'][] = array(
			'name'   => 'retroactive_access',
			'limit'  => $limit,
			'page'   => $page + 1,
		);

		update_option( 'learndash_woocommerce_settings', $options );
	}

	public function output_notice() {
		$options = get_option( 'learndash_woocommerce_settings', array() );

		if ( ! empty( $options['action_queue_success'] ) ) {

			foreach ( $options['action_queue_success'] as $key => $name ) {

				switch ( $name ) {
					case 'retroactive_access':
						?>
						<div class="notice notice-success">
							<p>
								<?php _e( 'The LearnDash WooCommerce retroactive course access process has been successfully done.', 'learndash-woocommerce' ); ?>
							</p>
						</div>

						<?php
						break;
				}
				
				unset( $options['action_queue_success'][ $key ] );
			}

			update_option( 'learndash_woocommerce_settings', $options );
		}
	}
}

new Learndash_WooCommerce_Tools();