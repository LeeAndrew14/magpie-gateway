<?php

include( 'magpie-backend.php' );

$magpie_backend = new WC_Magpie_Backend();

$magpie_charge_id = $_GET['charge_id'];

if ( $magpie_charge_id ) {
    $order = $magpie_backend->get_order_status_by_charge_id( $magpie_charge_id );

    if ( $order ) {
        $redirect_url = 'http' . ( ( $_SERVER['SERVER_PORT'] == 443 ) ? 's' : '' ) . '://' . 
            $_SERVER['HTTP_HOST'] . '/index.php/checkout/order-received/' . $order['order_id'] . '/?key=' . $order['order_key'];

        header( 'Location: ' . $redirect_url );

        die();
    }
}
