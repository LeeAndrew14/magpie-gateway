<?php

include( 'includes/magpie-api.php' );
include( 'includes/magpie-backend.php' );
include( 'config/magpie-database.php' );

$magpie = new WC_Magpie();
$magpie_backend = new WC_Magpie_Backend();

$magpie_payload = $_POST;

if ( count( $magpie_payload ) !== 0 ) {
    $amount             = $magpie_payload['amount'];
    $capture            = $magpie_payload['auto_charge'];
    $order_id           = $magpie_payload['order_id'];
    $stmt_desc          = $magpie_payload['stmt_desc'];
    $public_key         = $magpie_payload['public_key'];
    $private_key        = $magpie_payload['private_key'];
    $description        = $magpie_payload['description'];
    $card_details       = json_decode( stripslashes( $magpie_payload['card_details'] ) );
    $is_registered      = $magpie_payload['is_registered'];
    $customer_details   = json_decode( stripslashes( $magpie_payload['customer_details'] ) );

    // Check if user exists in database or if user is logged in
    $user = $magpie_backend->get_user_by_email( $customer_details->email );

    if ( ! $user && ! $is_registered ) {
        $magpie_backend->save_customer_details( $customer_details );
    }

    // Check if order exist in database
    $order_exist = $magpie_backend->check_if_order_exist( $order_id );

    if ( ! $order_exist ) {
        $data = array(
            'order_id'      => $order_id,
            'message'       => '',
            'order_status'  => NULL,
        );

        $magpie_backend->save_order_status( $data );
    }

    $token_payload = array(
        'card_name'         => $card_details->name,
        'card_number'       => $card_details->number,
        'exp_month'         => $card_details->exp_month,
        'exp_year'          => $card_details->exp_year,
        'cvc'               => $card_details->cvc,
        'address_city'      => ( $customer_details->shipping_city ) ? $customer_details->shipping_city : $customer_details->billing_city,
        'address_country'   => ( $customer_details->shipping_country ) ? $customer_details->shipping_country : $customer_details->billing_country,
        'address_line1'     => ( $customer_details->shipping_address_1 ) ? $customer_details->shipping_address_1 : $customer_details->billing_address_1,
        'address_zip'       => ( $customer_details->shipping_postcode ) ? $customer_details->shipping_postcode : $customer_details->billing_postcode,
    );

    $customer = $magpie_backend->get_magpie_customer_by_email( $customer_details->email );

    if ( ! $customer ) {
        $customer = array(
            'email' => $customer_details->email,
            'description' => $customer_details->billing_phone,
        );

        $create_customer_res = $magpie->create_customer( $customer, $private_key );

        $magpie_customer = json_decode( $create_customer_res );

        if ( isset( $magpie_customer->error ) ) {
            $message = $magpie_customer->error->message . '. Failed to create customer.';

            return_error( null, $message, null );
        }

        $customer_id = $magpie_customer->id;

        $create_token_res = $magpie->create_token( $token_payload, $public_key );

        $card_token = json_decode( $create_token_res );

        if ( isset( $card_token->error ) ) {

            $message = $card_token->error->message . '. Failed to create tokens.';

            return_error( $order_id, $message, 'failed' );
        }

        $update_customer_res = $magpie->update_customer( $customer_id, $card_token->id, $private_key );

        $update_customer = json_decode( $update_customer_res );

        if ( isset( $update_user->error ) ) {
            $message = $update_user->error->message. ' Failed to update customer.';

            return_error( null, $message, null );
        }

        $magpie_backend->save_magpie_customer( $update_customer );
    } else {
        $customer_id = $customer['customer_id'];
    }

    if ( $customer_id ) {
        $token_response = $magpie->create_token( $token_payload, $public_key );

        $card_token = json_decode( $token_response );

        if ( isset( $card_token->error ) ) {

            $message = $card_token->error->message . '. Failed to create token.';

            return_error( $order_id, $message, 'failed' );
        }

        $magpie_backend->save_magpie_token( $order_id, $card_token );

        if ( $card_token->id ) {
            $charge_payload = array(
                'amount'                => $amount,
                'source'                => $card_token->id,
                'description'           => $description,
                'statement_descriptor'  => $stmt_desc,
                'capture'               => $capture,
            );

            $charge_response = $magpie->create_charge( $charge_payload, $private_key );

            $charge_data = json_decode( $charge_response );

            if ( isset( $charge_data->error ) ) {
                $message = $charge_data->error->message. '. Failed to create charge.';

                return_error( $order_id, $message, 'failed' );
            }

            $magpie_backend->save_magpie_charge( $order_id, $charge_data );

            $data = array(
                'order_id'          => $order_id,
                'message'           => $charge_data->id,
                'new_order_status'  => 'processing',
            );

            $magpie_backend->update_order_status( $data );

            die();
        }
    } else {
        $message = 'Something went wrong.';

        return_error( null, $message, null);
    }
}

function return_error( $order_id, $message, $order_status ) {
    $magpie_backend = new WC_Magpie_Backend();

    $data = array(
        'error'             => true,
        'message'           => $message,
        'order_id'          => $order_id,
        'new_order_status'  => $order_status,
    );

    if ( $order_id ) $magpie_backend->update_order_status( $data ); 

    echo json_encode( $data );

    die();
}