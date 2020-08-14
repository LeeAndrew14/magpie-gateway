<?php
/*
 * Plugin Name: Magpie Payment Gateway
 * Plugin URI: https://github.com/LeeAndrew14/paymongo-gateway
 * Description: Credit card payments.
 * Author: Code Disruptors Inc.
 * Author URI: https://github.com/LeeAndrew14
 * Version: 1.0.1
 *

/*
 * Registers Magpie class as a WooCommerce payment gateway
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once( 'magpie-rewrite-rule.php' );

add_filter( 'woocommerce_payment_gateways', 'magpie_gateway_init' );
function magpie_gateway_init( $gateways ) {
    if ( ! class_exists( 'WC_Payment_Gateway' ) || ! class_exists( 'WC_Payment_Gateway' ) ) return;

    // Include  Gateway Class
    include_once( 'config/magpie-database.php' );
    include_once( 'includes/magpie-gateway.php' );
    include_once( 'includes/magpie-backend.php' );
    include_once( 'includes/magpie-api.php' );

    $gateways[] = 'WC_Magpie_Gateway';

	return $gateways;
}
