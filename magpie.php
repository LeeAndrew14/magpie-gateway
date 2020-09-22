<?php
/*
 * Plugin Name: Magpie Payment Gateway
 * Plugin URI: https://github.com/LeeAndrew14/paymongo-gateway
 * Description: Credit card payments.
 * Author: Code Disruptors Inc.
 * Author URI: https://github.com/LeeAndrew14
 * Version: 1.0.5
 */

// Block direct access to php files
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Include database init function
include_once( 'magpie-db-init.php');

// Include rewrite rule
include_once( 'magpie-rewrite-rule.php');

// Register Magpie database init hook
register_activation_hook( __FILE__, 'magpie_db_init' );

// Registers Magpie Gateway class as a WooCommerce payment gateway
add_filter( 'woocommerce_payment_gateways', 'magpie_gateway_init' );
function magpie_gateway_init( $gateway ) {
    if ( ! class_exists( 'WC_Payment_Gateway' ) || ! class_exists( 'WC_Payment_Gateway' ) ) return;

    // Include Magpie Gateway classes
    include_once( 'includes/magpie-api.php' );
    include_once( 'includes/magpie-backend.php' );
    include_once( 'includes/magpie-gateway.php' );

    $gateway[] = 'WC_Magpie_Gateway';

	return $gateway;
}
