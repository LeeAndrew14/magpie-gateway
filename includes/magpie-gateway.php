<?php
/**
 * Magpie Credit Card Class
 */

class WC_Magpie_Gateway extends WC_Payment_Gateway {

    /**
     * Class constructor
     */
    public function __construct() {
        $this->id = 'magpie_cc'; // payment gateway plugin ID
        $this->has_fields = true; // in case custom credit card form is needed
        $this->method_title = 'Magpie';
        $this->method_description = 'For different form of card payments';

        // Payments types
        $this->supports = array(            
            'product'
        );

        // Card fields
        $this->has_fields = true;

        // Method with all the options fields
        $this->init_form_fields();

        // Load settings.
        $this->init_settings();
        $this->icon                 = $this->get_option( 'icon', '' );
        $this->title                = $this->get_option( 'title' );
        $this->description          = $this->get_option( 'description' );
        $this->payment_description  = $this->get_option( 'payment_description' );
        $this->enabled              = $this->get_option( 'enabled' );
        $this->test_mode            = 'yes' === $this->get_option( 'test_mode' );
        $this->private_key          = $this->test_mode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
        $this->publishable_key      = $this->test_mode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

        $test_message = 'TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="https://magpie.im/documentation/#section/Test-Cards" target="_blank" rel="noopener noreferrer">documentation</a>.';

        $this->description = $this->test_mode ? $test_message : $this->get_option( 'description' );

        // Saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action( 'woocommerce_thankyou_magpie_cc', array( $this, 'check_payment_capture' ) );
        
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    }

    /**
     * Plugin options
     */
    public function init_form_fields() {
        $desc = '';

        $icon_url = $this->get_option( 'icon', '' );
        if ( $icon_url !== '' ) {
            $desc = '<img src="' . $icon_url . '" alt="' . $this->title . '" title="' . $this->title . '" />';
        }

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Magpie Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the payment title which the user see during checkout.',
                'default'     => 'Credit/Debit Card',
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the payment description which the user see during checkout.',
                'default'     => 'Visa, MasterCard, American Express, Discover',
            ),'payment_description' => array(
                'title'       => 'Payment Description',
                'type'        => 'text',
                'description' => 'This controls the description which the merchant see in Magpie dashboard.',
            ),
            'icon' => array(
                'title'       => 'Icon',
                'type'        => 'text',
                'desc_tip'    => 'If you want to show an image next to the gateway\'s name on the frontend, enter a URL to an image.',
                'default'     => '',
                'description' => $desc,
                'css'         => 'min-width:300px;width:50%;',
            ),
            'test_mode' => array(
                'title'       => 'Test mode',
                'label'       => 'Enable Test Mode',
                'type'        => 'checkbox',
                'description' => 'Place the payment gateway in test mode using test API keys.',
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'test_publishable_key' => array(
                'title'       => 'Test Publishable Key',
                'type'        => 'text'
            ),
            'test_private_key' => array(
                'title'       => 'Test Private Key',
                'type'        => 'text',
            ),
            'publishable_key' => array(
                'title'       => 'Live Publishable Key',
                'type'        => 'text'
            ),
            'private_key' => array(
                'title'       => 'Live Private Key',
                'type'        => 'text'
            )
        );
    }

    /**
     * Custom CSS and JS, in most cases required only for custom credit card form
     */
    public function payment_scripts() {
        if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
            return;
        }

        // do not work with card details without SSL unless website is in a test mode
        if ( ! $this->test_mode && ! is_ssl() ) {
            return;
        }
    }

    /**
     * Customize credit card payment field here
     */
    public function payment_fields() {
        if ( $this->description ) {
            // Display the description with <p> tags etc.
            echo wpautop( wp_kses_post( $this->description ) );
        }

        $cc_form = new WC_Payment_Gateway_CC();
        $cc_form->id = $this->id;
        $cc_form->supports = $this->supports;
        $cc_form->form();
    }

    /**
     * Fields validation
     */
    public function validate_fields() {
        $card_number    = $_POST['magpie_cc-card-number'];
        $expiry         = $_POST['magpie_cc-card-expiry'];
        $cvc            = $_POST['magpie_cc-card-cvc'];
        $message        = '';

        if ( ! isset( $card_number ) || empty( $card_number ) ) {
            $message = 'Please add your card number';
        }

        if ( ! isset( $expiry ) || empty( $expiry ) ) {
            $message = 'Please add your card expiry date';
        }

        if ( ! isset( $cvc ) || empty( $cvc ) ) {
            $message = 'Please add your card CVC security code';
        }

        if ( strlen( $cvc ) < 3 ) {
            $message = 'Invalid CVC card code!';
        }

        $pattern = "/^[0-9]{1,2} \/ [0-9]{1,2}$/";

        $check_date = preg_match( $pattern, $expiry );

        if ( ! $check_date ) {
            $message = 'Invalid date!';
        }

        wc_add_notice( $message, 'error' );

        return;
    }

    /*
     * Process payment here
     */
    public function process_payment( $order_id ) {
        $magpie_backend = new WC_Magpie_Backend();

        $order = wc_get_order( $order_id );

        $order_id = $order->get_id();

        $customer_id = $order->get_customer_id();

        $customer = new WC_Customer( $customer_id );

        if ( is_user_logged_in() ) {
            $customer_details = array(
                'id'                  => $order->get_customer_id(),
                'email'               => $customer->get_email(),
                'first_name'          => $customer->get_first_name(),
                'last_name'           => $customer->get_last_name(),
                'role'                => $customer->get_role(),
                'billing_first_name'  => $customer->get_billing_first_name(),
                'billing_last_name'   => $customer->get_billing_last_name(),
                'billing_company'     => $customer->get_billing_company(),
                'billing_address_1'   => $customer->get_billing_address_1(),
                'billing_city'        => $customer->get_billing_city(),
                'billing_state'       => $customer->get_billing_state(),
                'billing_postcode'    => $customer->get_billing_postcode(),
                'billing_country'     => $customer->get_billing_country(),
                'billing_email'       => $customer->get_billing_email(),
                'billing_phone'       => $customer->get_billing_phone(), 
                'shipping_first_name' => $customer->get_shipping_first_name(),
                'shipping_last_name'  => $customer->get_shipping_last_name(),
                'shipping_company'    => $customer->get_shipping_company(),
                'shipping_address_1'  => $customer->get_shipping_address_1(),
                'shipping_city'       => $customer->get_shipping_city(),
                'shipping_state'      => $customer->get_shipping_state(),
                'shipping_postcode'   => $customer->get_shipping_postcode(),
                'shipping_country'    => $customer->get_shipping_country(),
                'order_notes'         => isset( $_POST['order_comments'] ) ? $_POST['order_comments'] : null,
            ); 
        } else {
            $customer_details = array(
                'id'                  => $order->get_customer_id(),
                'email'               => $order->get_billing_email(),
                'first_name'          => $order->get_billing_first_name(),
                'last_name'           => $order->get_billing_last_name(),
                'role'                => null,
                'billing_first_name'  => $order->get_billing_first_name(),
                'billing_last_name'   => $order->get_billing_last_name(),
                'billing_company'     => $order->get_billing_company(),
                'billing_address_1'   => $order->get_billing_address_1(),
                'billing_city'        => $order->get_billing_city(),
                'billing_state'       => $order->get_billing_state(),
                'billing_postcode'    => $order->get_billing_postcode(),
                'billing_country'     => $order->get_billing_country(),
                'billing_email'       => $order->get_billing_email(),
                'billing_phone'       => $order->get_billing_phone(),
                'shipping_first_name' => $order->get_shipping_first_name(),
                'shipping_last_name'  => $order->get_shipping_last_name(),
                'shipping_company'    => $order->get_shipping_company(),
                'shipping_address_1'  => $order->get_shipping_address_1(),
                'shipping_city'       => $order->get_shipping_city(),
                'shipping_state'      => $order->get_shipping_state(),
                'shipping_postcode'   => $order->get_shipping_postcode(),
                'shipping_country'    => $order->get_shipping_country(),
                'order_notes'         => isset( $_POST['order_comments'] ) ? $_POST['order_comments'] : null,
            ); 
        }

        list( $exp_month, $_, $exp_year ) = explode( ' ', $_POST['magpie_cc-card-expiry'] );

        $customer_name = $customer_details['first_name'] . ' ' . $customer_details['last_name'];

        $customer_name === ' ' ? $customer_name = 'Guest' : $customer_name;

        $token_payload = array(
            'card_name'         => $customer_name,
            'card_number'       => str_replace(' ', '', $_POST['magpie_cc-card-number']),
            'exp_month'         => $exp_month,
            'exp_year'          => '20' . $exp_year,
            'cvc'               => $_POST['magpie_cc-card-cvc'],
            'address_city'      => ( $customer_details['shipping_city'] ) ? $customer_details['shipping_city'] : $customer_details['billing_city'],
            'address_country'   => ( $customer_details['shipping_country'] ) ? $customer_details['shipping_country'] : $customer_details['billing_country'],
            'address_line1'     => ( $customer_details['shipping_address_1'] ) ? $customer_details['shipping_address_1'] : $customer_details['billing_address_1'],
            'address_zip'       => ( $customer_details['shipping_postcode'] ) ? $customer_details['shipping_postcode'] : $customer_details['billing_postcode'],
        );

        $check_customer = $this->check_customer( $customer_details );

        if ( ! $check_customer ) {
            $this->create_magpie_customer( $token_payload, $customer_details );
        }

        $total =  $order->get_total() * 100;

        if ( $total < 5000 ) {  
            wc_add_notice(  'Minimum transaction should be equal or higher than 50 PHP', 'error' );

            return;
        }

        $this->check_order( $order_id, $order->get_order_key() );

        $is_3d_secure = $this->charge_condition( $order_id, $total, $token_payload, $customer_name );

        $order_status = $magpie_backend->get_order_status( $order_id );

        if ( $is_3d_secure && $order_status['order_status'] === 'processing' ) {
            return array(
                'result'    => 'success',
                'redirect'  => $is_3d_secure,
            );
        }

        if ( $order_status['order_status'] === 'processing' ) {
            $order->update_status( 'processing' );

            wc_reduce_stock_levels( $order_id );

            WC()->cart->empty_cart();

            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order ),
            );
        } else {
            wc_add_notice( 'Transaction failed! ' . $order_status['message'], 'error' );

            return;
        }
    }

    public function check_customer( $customer_details ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        // Check if user exists in database or if user is logged in
        $user = $magpie_backend->get_user_by_email( $customer_details['email'] );

        if ( ! $user && ! is_user_logged_in() ) {
            $result = $magpie_backend->save_customer_details( $customer_details );

            return $result;
        }
    }
    
    public function check_order( $order_id, $order_key ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $order_exist = $magpie_backend->check_if_order_exist( $order_id );

        if ( ! $order_exist ) {
            $data = array(
                'order_id'      => $order_id,
                'order_key'     => $order_key,
                'message'       => '',
                'order_status'  => NULL,
            );

            $magpie_backend->save_order_status( $data );
        }
    }

    public function create_magpie_customer( $token_payload, $customer_details ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();
    
        $customer = $magpie_backend->get_magpie_customer_by_email( $customer_details['email'] );

        $message = 'Something went wrong while processing your order.
            <br>Kindly try again or try using other payment methods.
            <br>If the problem persist please contact us.';
    
        if ( ! $customer && ! empty( $customer_details['email'] ) ) {
            $customer = array(
                'email' => $customer_details['email'],
                'description' => $customer_details['billing_phone'],
            );
    
            $create_customer_res = $magpie->create_customer( $customer, $this->private_key );
    
            $magpie_customer = json_decode( $create_customer_res );
    
            if ( isset( $magpie_customer->error ) ) {
                wc_get_logger()->add( 'magpie-gateway', 'Create Customer Error' . wc_print_r( $magpie_customer, true ) );

                return wc_add_notice( $message, 'error' );
            }

            $customer_id = $magpie_customer->id;
    
            $create_token_res = $magpie->create_token( $token_payload, $this->publishable_key );

            $card_token = json_decode( $create_token_res );

            if ( isset( $card_token->error ) ) {
                wc_get_logger()->add( 'magpie-gateway', 'Token Update Customer Error' . wc_print_r( $magpie_customer, true ) );
    
                return wc_add_notice( $message, 'error' );
            }
    
            $update_customer_res = $magpie->update_customer( $customer_id, $card_token->id, $this->private_key );
    
            $update_customer = json_decode( $update_customer_res );
    
            if ( isset( $update_user->error ) ) {
                wc_get_logger()->add( 'magpie-gateway', 'Token Update Customer Error' . wc_print_r( $update_customer, true ) );

                return wc_add_notice( $message, 'error' );
            }
    
            $magpie_backend->save_magpie_customer( $update_customer );
        }
    }

    public function charge_condition( $order_id, $amount, $token_payload, $customer_name ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $order = wc_get_order( $order_id );

        $token_response = $magpie->create_token( $token_payload, $this->publishable_key );

        $card_token = json_decode( $token_response );
            
        $message = 'Something went wrong while processing your order.
            <br>Kindly try again or try using other payment methods.
            <br>If the problem persist please contact us.';

        if ( isset( $card_token->error ) ) {
            $error_type = $card_token->error->type;

            if ( $error_type === 'card_error' ) {
                $message = $card_token->error->message;

                return wc_add_notice( $message, 'error' );
            } elseif ( $error_type === 'invalid_request_error' || $error_type === 'authentication_error' ) {
                wc_get_logger()->add( 'magpie-gateway', 'Charge Token Error ' . wc_print_r( $card_token, true ) );

                return wc_add_notice( $message, 'error' );
            }
        }

        $magpie_backend->save_magpie_token( $order_id, $card_token );

        $description = '[Tag]:' . $this->payment_description . '[Order ID]:' . $order_id . '[Customer Name]:' . $customer_name;

        if ( $card_token->id ) {
            $charge_payload = array(
                'amount'                => $amount,
                'source'                => $card_token->id,
                'description'           => $description,
                'statement_descriptor'  => get_bloginfo( 'name' ),
                'gateway'               => 'magpie_3ds',
                'capture'               => true,
                'redirect_url'          => get_site_url() . '/',
                'callback_url'          => get_site_url() . '/3ds/callback',
            );

            $charge_response = $magpie->create_charge( $charge_payload, $this->private_key );

            $charge_data = json_decode( $charge_response );

            if ( isset( $charge_data->error ) ) {
                $error_type = $charge_data->error->type;

                if ( $error_type === 'card_error' ) {
                    $message = $charge_data->error->message;

                    return wc_add_notice( $message, 'error' );
                } elseif ( $error_type === 'invalid_request_error' || $error_type === 'authentication_error' ) {
                    wc_get_logger()->add( 'magpie-gateway', 'Charge Response Error' . wc_print_r( $charge_data, true ) );

                    return wc_add_notice( $message, 'error' );
                }
            }

            $magpie_backend->save_magpie_charge( $order_id, $charge_data );

            $data = array(
                'order_id'          => $order_id,
                'message'           => $charge_data->id,
                'order_key'         => $order->get_order_key(),
                'new_order_status'  => 'processing',
            );

            $magpie_backend->update_order_status( $data );

            $checkout_url = $charge_data->checkout_url;

            return $checkout_url !== null ? $checkout_url : null;
        }
    }

    public function check_payment_capture( $order_id ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $order = new WC_Order( $order_id );

        $get_charge = $magpie_backend->get_order_status( $order_id );

        $charge_id = $get_charge['message'];

        $response = $magpie->retrieve_charge( $charge_id, $this->private_key );

        $charge_details = json_decode( $response );

        $data = array(
            'order_id'  => $order_id,
            'order_key' => $order->get_order_key(),
            'message'   => $charge_details->id,
        );

        if ( isset( $charge_details->error ) ) {
            wc_get_logger()->add( 'magpie-gateway', 'Retrieve Charge Error ' . wc_print_r( $charge_details, true ) );

            $message = $charge_details->error->message;

            $order->add_order_note( $message, false );

            wc_add_notice( 'Something went wrong while processing your order. Please try again.
                <br>If the problem persists please contact us.', 'error' );

            return;
        }

        $currency_symbol = get_woocommerce_currency_symbol();

        $charge_amount = number_format( $charge_details->amount / 100, 2 );
        
        if ( $charge_details->captured && $charge_details->status === 'succeeded' ) {
            $order->payment_complete();

            $order->update_status( 'processing' );

            wc_reduce_stock_levels( $order_id );

            $order->add_order_note( 'Your order successfully charged ' . $currency_symbol . ' ' . $charge_amount . ', Thank you!', true );

            $data['new_order_status'] = 'completed';
    
            $magpie_backend->update_order_status( $data );

            return;
        } elseif ( isset( $charge_details->redirect_response->state ) ) {
            $state = $charge_details->redirect_response->state;

            $message = $charge_details->redirect_response->message;

            if ( $state === 'pending' ) {
                $order->update_status( 'pending' );

                $order->add_order_note( 'Magpie payment state is pending .', false );

                $data['new_order_status'] = 'failed';
        
                $magpie_backend->update_order_status( $data );

                wp_redirect( wc_get_checkout_url() );

                wc_add_notice( 'Something went wrong while processing your order. Please try again.
                    <br>If the problem persists please contact us.', 'error' );

                return;
            } elseif ( $state === 'gateway_processing_failed' ) {
                if ( $message === 'Your card has insufficient funds.' ) {
                    wp_redirect( wc_get_checkout_url() );

                    wc_add_notice( $message, 'error' );

                    return;
                } elseif ( $message === 'Your card was declined.' ) {
                    wp_redirect( wc_get_checkout_url() );

                    wc_add_notice( $message, 'error' );

                    return;
                }

                $order->update_status( 'pending' );

                $order->add_order_note( $message, false );

                $data['new_order_status'] = 'failed';
        
                $magpie_backend->update_order_status( $data );

                wp_redirect( wc_get_checkout_url() );

                return;
            } elseif ( $state === 'gateway_setup_failed' ) {
                $order->update_status( 'pending' );

                $order->add_order_note( $message, false );

                $data['new_order_status'] = 'failed';
        
                $magpie_backend->update_order_status( $data );

                wp_redirect( wc_get_checkout_url() );

                wc_add_notice( 'Something went wrong while processing your order. Please try again.
                    <br>If the problem persists please contact us.', 'error' );

                return;
            }
        } else {
            if ( ! $charge_details->captured && $charge_details->status === 'pending' ) {
                $this->capture_charge( $charge_details );

                return;
            }

            wc_get_logger()->add( 
                'magpie-gateway', 
                'Payment Failed ' . $charge_details->source->name . ' ' . wc_print_r( $charge_details, true )
            );

            $order->update_status( 'pending' );

            $data['new_order_status'] = 'failed';

            $magpie_backend->update_order_status( $data );

            if ( isset( $charge_details->redirect_response->message ) ) {
                $order->add_order_note( $charge_details->redirect_response->message, false );
            } else {
                $order->add_order_note( 'Payment failed', false );
            }

            wp_redirect( wc_get_checkout_url() );
            
            wc_add_notice( 'Something went wrong while processing your order. Please try again.
                    <br>If the problem persists please contact us.', 'error' );

            return;
        }
    }

    public function capture_charge( $charge_details ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $capture_res = $magpie->capture_charge( $charge_details->id, $charge_details->amount, $this->private_key );

        $charge_details = json_decode( $capture_res );

        if ( isset( $charge_details->error ) ) {
            wc_get_logger()->add( 'magpie-gateway', 'Capture Charge Error ' . wc_print_r( $charge_details, true ) );

            $message = $charge_details->error->message;

            $order->add_order_note( $message, false );

            wc_add_notice( 'Something went wrong while processing your order. Please try again.
                <br>If the problem persists please contact us.', 'error' );

            return;
        }

        $order_status = $magpie_backend->get_order_status_by_charge_id( $charge_details->id );
        
        if ( $charge_details->captured && $charge_details->status === 'succeeded' ) {
            $this->check_payment_capture( $order_status['order_id'] );
        } else {
            wc_get_logger()->add( 
                'magpie-gateway', 
                'Payment Failed ' . $charge_details->source->name . ' ' . wc_print_r( $charge_details, true )
            );

            $order->update_status( 'pending' );

            $data['new_order_status'] = 'failed';

            $magpie_backend->update_order_status( $data );

            if ( isset( $charge_details->redirect_response->message ) ) {
                $order->add_order_note( $charge_details->redirect_response->message, false );
            } else {
                $order->add_order_note( 'Payment failed', false );
            }

            wp_redirect( wc_get_checkout_url() );

            wc_add_notice( 'Something went wrong while processing your order. Please try again.
                <br>If the problem persists please contact us.', 'error' );

            return;
        }
    }
}
