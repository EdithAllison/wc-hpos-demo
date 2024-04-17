<?php

/**
 * The file that shows WC coding examples
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

class WC_Examples {

	public function __construct() {}

	/*********
	 * File: wp-content/plugins/woocommerce/src/Checkout/Helpers/ReserveStock.php
	 * class Automattic\WooCommerce\Checkout\Helpers\ReserveStock
	***************/

	/**
	 * Returns query statement for getting reserved stock of a product.
	 *
	 * @param int     $product_id Product ID.
	 * @param integer $exclude_order_id Optional order to exclude from the results.
	 * @return string|void Query statement.
	 */
	private function get_query_for_reserved_stock( $product_id, $exclude_order_id = 0 ) {
		global $wpdb;

		/**
		 * Default join and where_status variables for legacy Post storage
		 */
		$join         = "$wpdb->posts posts ON stock_table.`order_id` = posts.ID";
		$where_status = "posts.post_status IN ( 'wc-checkout-draft', 'wc-pending' )";

		/**
		 * Checks if HPOS is enabled. If it is, overwrites the join and where_status variables.
		 */
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$join         = "{$wpdb->prefix}wc_orders orders ON stock_table.`order_id` = orders.id";
			$where_status = "orders.status IN ( 'wc-checkout-draft', 'wc-pending' )";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			"
			SELECT COALESCE( SUM( stock_table.`stock_quantity` ), 0 ) FROM $wpdb->wc_reserved_stock stock_table
			LEFT JOIN $join
			WHERE $where_status
			AND stock_table.`expires` > NOW()
			AND stock_table.`product_id` = %d
			AND stock_table.`order_id` != %d
			",
			$product_id,
			$exclude_order_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * Filter: woocommerce_query_for_reserved_stock
		 * Allows to filter the query for getting reserved stock of a product.
		 *
		 * @since 4.5.0
		 * @param string $query            The query for getting reserved stock of a product.
		 * @param int    $product_id       Product ID.
		 * @param int    $exclude_order_id Order to exclude from the results.
		 */
		return apply_filters( 'woocommerce_query_for_reserved_stock', $query, $product_id, $exclude_order_id );
	}

}
