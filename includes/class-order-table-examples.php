<?php

/**
 * The file that shows Order Table examples
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

class Order_Table_Examples {

	public function __construct() {

		$this->hooks();

	}

	/**
	 * Register the hooks
	 */
	private function hooks() {

		/**
		 * Add a metabox to the order edit screen
		 * for discussion hooks see https://github.com/woocommerce/woocommerce/issues/35814
		 */
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		// add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ) ); // CPT hook
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 20, 2 ); // HPOS + CPT hook. Use this for dual compatibility. 
		
		/**
		 * Modify a column in the orders overview table
		 * If / Else to determine if HPOS is enabled
		 */
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'modify_order_columns' ), 20, 2 );
		} else {
			// Traditional CPT-based orders are in use.
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'modify_order_columns' ), 20, 2 );
		}

		/**
		 * Add a new column to the orders overview table
		 */
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_column' ) ); // CPT hook
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_column_content' ), 10, 2 ); // CPT hook
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_column' ) ); // HPOS hook
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_order_column_content' ), 10, 2 ); // HPOS hook
		
		/**
		 * Add a filter to the orders overview table
		 * For more info on querying in HPOS see: https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
		 * and https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs
		 */
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'shop_order_filter' ), 20, 2 ); // HPOS hook
		add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', array( $this, 'shop_order_run_filter_hpos' ) ); // HPOS hook
		add_action( 'restrict_manage_posts', array( $this, 'shop_order_filter' ), 20, 2 ); // CPT hook
		add_filter( 'pre_get_posts', array( $this, 'shop_order_run_filter' ) ); // CPT hook

		/**
		 * Add an action to bulk edit
		 * Attach to both filters to ensure compatibility with both legacy Post and HPOS
		 */
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'get_custom_order_status_bulk' ), 20 ); // HPOS hook
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'get_custom_order_status_bulk' ), 20 ); // CPT hook

	}

	/**
	 * Add a metabox to the order edit screen
	 * @return void 
	 */
	public function add_meta_box(){

		$screen = OrderUtil::custom_orders_table_usage_is_enabled() 
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

		add_meta_box(
			'urgent',
			__( 'Urgent?', 'wc-hpos-demo' ),
			array( $this, 'render_meta_box' ),
			$screen,
			'side',
			'high'
		);

	}

	/**
	 * Renders out the HTML shown in the metabox.
	 * 
	 * @param WP_Post|WC_Order|ID Accepts Post object, Order object or ID
	 */
	public function render_meta_box( $post ) {

		/**
		 * wc_get_order is a swiss army knife and can cope with an ID, a Post object or an Order object.
		 * 
		 * if ( false === $order ) {
		 *  return self::get_global_order_id();
		 * } elseif ( is_numeric( $order ) ) {
		 *	return $order;
		 * } elseif ( $order instanceof WC_Abstract_Order ) {
		 *	return $order->get_id();
		 * } elseif ( ! empty( $order->ID ) ) {
		 * 	return $order->ID;
		 * } else {
		 *	return false;
		 * }
		 */

		$order =  wc_get_order( $post );
		wp_nonce_field( 'wc_hpos_demo_meta_box_' . $order->get_id(), 'wc_hpos_demo_nonce' );
		?>

		<div class="urgent-wrapper">
			<label>
				<input type="checkbox" name="urgent" id="urgent" value="yes" <?php checked( $order->get_meta( '_urgent', true ), 'yes' ); ?>>
				<?php esc_html_e( 'Is this order urgent?', 'wc-hpos-demo' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Save the meta box
	 * @param int $post_id
	 * @param object $post
	 * 
	 * $post = HPOS = Automattic\WooCommerce\Admin\Overrides\Order Object
	 * $post = CPT = WP_Post Object
	 */
	public function save_meta_box( $post_id, $post ) {

		// do not run on autosave
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		// does the current user have permission to edit the post?
		if ( ! current_user_can( 'edit_post', $post_id ) ){
			return;
		}

		// security check
		if ( !isset( $_POST['wc_hpos_demo_nonce'] ) || ! wp_verify_nonce( $_POST['wc_hpos_demo_nonce'], 'wc_hpos_demo_meta_box_' . $post_id ) ) {
			return;
		}

		// $post is either WP_Post or WC_Order. Using the function makes sure we've got an order object
		$order = wc_get_order( $post ); 

		if( isset( $_POST['urgent'] ) && 'yes' === $_POST['urgent'] ) {
			$order->update_meta_data( '_urgent', 'yes' );
		} else {
			$order->delete_meta_data( '_urgent' );
		}

		$order->save(); // Save the data

	}



	/**
	* Modify order columns
	* CPT hook 2nd argument is ID, HPOS hook $order object but we don’t need two separate functions as wc_get_order will work with either an ID or an object
	**/
	public function modify_order_columns( $column, $post ) {

		if ( $column == 'order_total' ) {

			$order = wc_get_order( $post );

			// add your modifications
			
		}
	}

	/**
	 * Add a new column to the orders overview table
	 */
	public function add_order_column( $columns ) {

		$new_columns = array(
			'vip'            => __( 'VIP', 'wc-hpos-demo' ), // Orders over $100
			'payment_method' => __( 'Payment', 'wc-hpos-demo' ), // Payment Method
			'urgent'				 => __( 'Urgent', 'wc-hpos-demo' ), // Urgent orders
		);

		return array_merge( $columns, $new_columns );

	}

	/**
	 * Add content to the new column
	 * $post is either ID or object
	 */
	public function add_order_column_content( $column, $post ) {

		$order = wc_get_order( $post );

		if ( 'vip' === $column ) {

			if( $order->get_total() > 100.00 ) {
				echo "💰";
			} 

		} elseif( 'payment_method' === $column ) {

			echo $order->get_payment_method_title();

		} elseif( 'urgent' === $column ) {

			if( 'yes' === $order->get_meta( '_urgent', true ) ) {
				echo '🚨';
			}

		}

	}

	/**
	 * Show select filters
	 */
	public function shop_order_filter( $post_type, $which ) {

		if( $post_type !== 'shop_order' || $which !== 'top' ) {
			return;
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$methods = array();

		foreach ($available_gateways as $gateway) {
				$methods[$gateway->id] = $gateway->settings['title'];
		}

		$payment_method = isset( $_GET['_payment_method'] ) ? wc_clean( wp_unslash( $_GET['_payment_method'] ) ) : '';
		$is_vip         = isset( $_GET['is_vip'] ) &&  'on' === $_GET['is_vip'] ? 'checked' : '';
		$is_urgent      = isset( $_GET['is_urgent'] ) &&  'on' === $_GET['is_urgent'] ? 'checked' : '';

		?>

		<label>
			<?php _e( 'VIPs', 'wc-hpos-demo' ); ?>
			<input type="checkbox" name="is_vip" id="is_vip" style="width:20px;height:20px;" <?php echo $is_vip; ?>>
		</label>

		<label>
			<?php _e( 'Is Urgent?', 'wc-hpos-demo' ); ?>
			<input type="checkbox" name="is_urgent" id="is_urgent" style="width:20px;height:20px;" <?php echo $is_urgent; ?>>
		</label>

		<select name='_payment_method'>
			<option value=''><?php _e( 'Payment Method', 'wc-hpos-demo' ); ?></option><?php
			foreach ( $methods as $key => $value ) :
				?><option <?php selected( $payment_method, $key ); ?> value='<?php echo $key; ?>'><?php echo $value; ?></option><?php
			endforeach;
		?></select><?php

	}

	/**
	 * Filter orders the HPOS way
	 * for advanced examples see https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs
	 * 
	 * @param array $args
	 */
	function shop_order_run_filter_hpos ( $args ) {

		// Field Query
		// https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs#order-field-queries-field_query
		if ( ! empty( $_GET['is_vip'] ) && $_GET['is_vip'] === 'on') {
			$args['field_query'] = array( 
				array(
					'field'   => 'total',
					'value'   => '100.00',
					'compare' => '>',
				)
			);
		}

		// Meta Query
		// https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs#metadata-queries-meta_query
		// uses standard WP meta_query https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters 
		if ( ! empty( $_GET['is_urgent'] ) && $_GET['is_urgent'] === 'on' ) {
			$args['meta_query'] = array( 
				array(
					'key'     => '_urgent',
					'value'   => 'yes',
          'compare' => '='
				)
			);
		}

		// Default Param
		// https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query#price
		if( ! empty( $_GET['_payment_method'] ) ) { 
			$args['payment_method'] = wc_clean( wp_unslash( $_GET['_payment_method'] ) );
		}

		return $args;

	}

	/**
	 * Filter orders the CPT way
	 */
	function shop_order_run_filter( $query ) {

		if ( ! is_admin() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' === $pagenow && 'shop_order' === $query->query['post_type'] ) {

			$meta = array();

			if ( ! empty ( $_GET['is_vip'] ) ) {
				$meta[] = array(
					'key'     => '_order_total',
					'value'   => 100,
					'compare' => '>',
					'type'    => 'NUMERIC'
				);
			}

			if ( ! empty ( $_GET['is_urgent'] ) ) {
				$meta[] = array(
					'key'     => '_urgent',
					'value'   => 'yes',
					'compare' => '=',
				);
			}

			if( ! empty( $_GET['_payment_method'] ) ) {
				$meta[] = array(
					'key'     => '_payment_method',
					'value'   =>  wc_clean( wp_unslash( $_GET['_payment_method'] ) ),
				);
			}

			if( ! empty( $meta ) ) {
				$query->set( 'meta_query', $meta );
			}

		}

		return $query;

	}

		/**
	 * Add an action to bulk edit
	 */
	public function get_custom_order_status_bulk( $bulk_actions ) {

		// add custom action
		$new_actions = array(
			'abc' => __( 'Lorem Ipsum', 'wc-hpos-demo' ),
		);

		return array_merge( $new_actions, $bulk_actions );

	}
	

}

new Order_Table_Examples();