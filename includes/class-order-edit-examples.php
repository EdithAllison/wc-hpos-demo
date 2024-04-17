<?php

/**
 * The file that shows Order Edit examples
 *
 * @link       https://agentur-allison.com/
 * @since      1.0.0
 */

namespace AGAL\WHD;

use \Automattic\WooCommerce\Utilities\OrderUtil;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Order_Edit_Examples {

	public function __construct() {

		$this->hooks();

	}

	/**
	 * Register the hooks
	 */
	private function hooks() {
	}

	/**
	 * Get WP Admin Order Edit Link
	 * For demo purpose only
	 * IRL use $order->get_edit_order_url() which contains compatibility for both HPOS and CPT-based orders
	 * 
	 * @param WC_Order $order
	 * return string
	 */
	public static function get_order_admin_link( $order ) {

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$url = esc_url( admin_url( 'admin.php?page=wc-orders' ) ) . '&action=edit&id=' . absint( $order->get_id() );
		} else {
			// Traditional CPT-based orders are in use.
			$url = esc_url( admin_url( 'post.php?action=edit' ) ) . '&post=' . absint( $order->get_id() );
		}

		return $url;

	}

}
