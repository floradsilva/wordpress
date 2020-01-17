<?php
namespace Javorszky\Toolbox;
use WC_Query;

class ToolBox_Query extends WC_Query {
	public function __construct() {

		add_action( 'init', array( $this, 'add_endpoints' ) );

		$this->init_query_vars();
	}

	/**
	 * Init query vars by loading options.
	 *
	 * @since 2.0
	 */
	public function init_query_vars() {
		$this->query_vars = array(
			JGTB_EDIT_SUB_ENDPOINT => get_option( 'toolbox-edit-subscription', JGTB_EDIT_SUB_ENDPOINT ),
		);
	}
}

new ToolBox_Query;
