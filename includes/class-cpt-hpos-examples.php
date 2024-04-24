<?php

/**
 * The file that shows CPT vs HPOS coding examples
 *
 * @link       https://agentur-allison.com/
 * @since      1.0.0
 */

namespace AGAL\WHD;

use \Automattic\WooCommerce\Utilities\OrderUtil;
use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CPT_HPOS_Examples {

	public function __construct() {}

	/**
	 * Get WP Admin Order Edit Link
	 * 
	 * @param WC_Order $order
	 * return string
	 */
	public static function get_order_admin_link( $order ) {

		/**
		 * If we want to make our lives hard we could construct the link from scratch
		 */
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$url = esc_url( admin_url( 'admin.php?page=wc-orders' ) ) . '&action=edit&id=' . absint( $order->get_id() );
		} else {
			// Traditional CPT-based orders are in use.
			$url = esc_url( admin_url( 'post.php?action=edit' ) ) . '&post=' . absint( $order->get_id() );
		}

		/**
		 * If we want to make our lives easy we user the $order class method
		 */
		$url = $order->get_order_edit_url();

		return $url;

	}

	/**
	 * Get Order Status from ID
	 * 
	 * @param int $id 
	 * @return object 
	 */
	public static function get_order_status( $id ) {

		/* Instead of this */
		//	$post = get_post( $id );
		//	$order_status = $post->post_status;

		/* Use this */
		$order = wc_get_order( $id );
		$order_status = $order->get_status();

		return $order_status;

	}

	public static function get_most_recent_orders() {

		/* Instead of this */
		// $query = new WP_Query( array( 'post_type' => 'shop_order', 'posts_per_page' => 10 ) );

		/* Use this */
		$query = new \WC_Order_Query( array( 'limit' => 10 ) );
		$orders = $query->get_orders();

		/* Or even shorter */

		/* Instead of this */
		// $orders = get_posts( array( 'post_type' => 'shop_order', 'posts_per_page' => 10 ) );

		/* Use this */
		$orders = wc_get_orders( array( 'limit' => 10 ) );

		return $orders;
		
	}

	/**
	 * Update Order Meta
	 * 
	 * @param int $order_id 
	 * @return void 
	 */
	public static function update_meta( $order_id ) {

		/* Instead of this: */
		// $custom_meta = get_post_meta( $order_id, '_codeable_key', true );
		// $custom_meta = $custom_meta .= 'test'; // just change something
		// update_post_meta( $order_id, '_codeable_key', $custom_meta );

		/* Use this. CRUD always works. */
		$order       = wc_get_order( $order_id );
		$custom_meta = $order->get_meta( '_codeable_key', true );
		$custom_meta = $custom_meta .= ' test'; // just change something

		$order->update_meta_data( '_codeable_key', $custom_meta );
		$order->save();

	}

	/**
	 * Check if HPOS is enabled
	 * 
	 * use \Automattic\WooCommerce\Utilities\OrderUtil;
	 * use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
	 * 
	 * @return bool 
	 */
	public function is_hpos_active() {
		
		/**
		 * the long way
		 */
		//return wc_get_container()->get( \CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();

		/**
		 * the short way
		 */
		return OrderUtil::custom_orders_table_usage_is_enabled();

	}

}
