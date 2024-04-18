<?php

/*
* Plugin Name: WooCommerce HPOS Demo
* Plugin URI: https://codeable.io
* Description: This plugin demonstrates HPOS coding examples
* Version: 1.0.0
* Author: Edith Allison for Codeable
* Author URI: https://agentur-allison.com
* Text Domain: wc-hpos-demo
* Domain Path: /languages
*
* WC requires at least: 8.0.0
* WC tested up to: 8.6.1
*/

namespace AGAL\WHD;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WHD_PLUGIN_PATH' , __DIR__ );

// Initiate the plugin
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	include_once WHD_PLUGIN_PATH . '/includes/class-order-table-examples.php';
}

// HPOS compatibility statement. Neeed for plugins that use "WC requires" and "WC tested up to" headers.
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

