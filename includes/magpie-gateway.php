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
        $this->auto_charge          = 'yes' === $this->get_option( 'auto_charge' );
        $this->token_only           = 'yes' === $this->get_option( 'token_only' );

        $test_message = 'TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="https://magpie.im/documentation/#section/Test-Cards" target="_blank" rel="noopener noreferrer">documentation</a>.';

        $this->description = $this->test_mode ? $test_message : $this->get_option( 'description' );

        // Saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        // Process charge when order is completed
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_order_charge' ) );
        
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
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Credit/Debit Card',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay using Magpie',
            ),'payment_description' => array(
                'title'       => 'Payment Description',
                'type'        => 'text',
                'description' => 'This controls the description which the merchant sees in Magpie dashboard.',                
                'desc_tip'    => true,
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
            'auto_charge' => array(
                'title'       => 'Auto Charge',
                'label'       => 'Enable Auto Charge',
                'type'        => 'checkbox',
                'description' => 'For automatic credit charge. Check this if grand total does not change.',
                'default'     => 'no',
                'desc_tip'    => true,
            ),'token_only' => array(
                'title'       => 'Token Only',
                'label'       => 'Enable Token Only',
                'type'        => 'checkbox',
                'description' => 'For creating token for later use. Check this if grand total could change.',
                'default'     => 'no',
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
                'order_notes'         => $_POST['order_comments'],
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

        $this->check_order( $order_id );

        if ( ! $check_customer ) {
            $this->create_magpie_customer( $token_payload, $customer_details );
        }

        $total =  $order->get_total() * 100;

        // A positive integer with minimum amount of 3000.
        if ( $total < 3000 ) {  
            wc_add_notice(  'Minimum transaction should be equal or higher than 30 PHP', 'error' );
            return;
        }

        $this->charge_condition( $order_id, $total, $token_payload, $customer_name );

        $order_status = $magpie_backend->get_order_status( $order_id );

        if ( $order_status['order_status'] === 'processing' ) {
            // Set order status
            $order->update_status( 'processing' );

            // Reduce stock levels
            wc_reduce_stock_levels( $order_id );

            // Remove cart
            WC()->cart->empty_cart();
            
            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
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
    
    public function check_order( $order_id ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $order_exist = $magpie_backend->check_if_order_exist( $order_id );

        if ( ! $order_exist ) {
            $data = array(
                'order_id'      => $order_id,
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
    
        if ( ! $customer && ! empty( $customer_details['email'] ) ) {
            $customer = array(
                'email' => $customer_details['email'],
                'description' => $customer_details['billing_phone'],
            );
    
            $create_customer_res = $magpie->create_customer( $customer, $this->private_key );
    
            $magpie_customer = json_decode( $create_customer_res );
    
            if ( isset( $magpie_customer->error ) ) {
                $message = $magpie_customer->error->message . ' Failed to create customer.';
    
                return wc_add_notice( $message, 'error' );
            }
    
            $customer_id = $magpie_customer->id;
    
            $create_token_res = $magpie->create_token( $token_payload, $this->publishable_key );
    
            $card_token = json_decode( $create_token_res );
    
            if ( isset( $card_token->error ) ) {
    
                $message = $card_token->error->message . '. Failed to create token for Magpie customer update.';
    
                return wc_add_notice( $message, 'error' );
            }
    
            $update_customer_res = $magpie->update_customer( $customer_id, $card_token->id, $this->private_key );
    
            $update_customer = json_decode( $update_customer_res );
    
            if ( isset( $update_user->error ) ) {
                $message = $update_user->error->message. ' Failed to update customer.';
    
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

        if ( isset( $card_token->error ) ) {
            $message = $card_token->error->message . ' Failed to create token for creating a charge.';

            return wc_add_notice( $message, 'error' );
        }

        $magpie_backend->save_magpie_token( $order_id, $card_token );

        if ( $card_token->id && ! $this->token_only ) {
            $charge_payload = array(
                'amount'                => $amount,
                'source'                => $card_token->id,
                'description'           => '[Tag]:' . $this->payment_description . '[Order ID]:' . $order_id . '[Customer Name]:' . $customer_name,
                'statement_descriptor'  => get_bloginfo( 'name' ),
                'capture'               => $this->auto_charge,
            );

            $charge_response = $magpie->create_charge( $charge_payload, $this->private_key );

            $charge_data = json_decode( $charge_response );

            if ( isset( $charge_data->error ) ) {
                $message = $charge_data->error->message. ' Failed to create charge.';

                return wc_add_notice( $message, 'error' );
            }

            $magpie_backend->save_magpie_charge( $order_id, $charge_data );

            $data = array(
                'order_id'          => $order_id,
                'message'           => $charge_data->id,
                'new_order_status'  => 'processing',
            );

            $magpie_backend->update_order_status( $data );
        } else {
            $data = array(
                'order_id'          => $order_id,
                'message'           => 'Token Only',
                'new_order_status'  => 'processing',
            );

            $magpie_backend->update_order_status( $data );
        }
    }

    public function process_order_charge( $order_id ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $order = wc_get_order( $order_id );

        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        $customer_name === ' ' ? $customer_name = 'Guest' : $customer_name;

        $amount = $order->get_total() * 100;

        if ( $this->token_only ) {
            $this->process_token_only( $order_id, $amount, $order, $customer_name );
        }

        $charge = $magpie_backend->get_order_status( $order_id );

        if ( ! $charge ) return;

        $charge_id = $charge['message'];

        $response = $magpie->retrieve_charge( $charge_id, $this->private_key );

        $is_charge = json_decode( $response );

        $data = array(
            'order_id'  => $order_id,
            'message'   => $is_charge->id,
        );

        $currency_symbol =  get_woocommerce_currency_symbol();

        $charge_amount = number_format( $is_charge->amount / 100, 2 );

        if ( $is_charge->captured && $is_charge->status == 'succeeded' ) {
            $order->add_order_note( 'Payment successfully charged ' . $currency_symbol . ' ' . $charge_amount , true );

            $data['new_order_status'] = 'completed';
    
            $magpie_backend->update_order_status( $data );

            return;
        }

        $response = $magpie->capture_charge( $charge_id, $amount, $this->private_key );

        $body = json_decode( $response );
        
        if ( ! isset( $body->error ) && $body->captured == true && $body->status == 'succeeded' ) {
            
            $order->add_order_note( 'Payment successfully charged ' .  $currency_symbol . ' ' . $charge_amount, true );

            wc_reduce_stock_levels( $order_id );

            $data['new_order_status'] = 'completed';

            $magpie_backend->update_order_status( $data );

            return;
        } else {
            $error = $body->error->message;

            $order->add_order_note( 'Payment failed please try again. ' . $error, false );
            
            $data['new_order_status'] = 'failed';
    
            $magpie_backend->update_order_status( $data );

            return;
        }
    }

    public function process_token_only( $order_id, $amount, $order, $customer_name ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $token = $magpie_backend->get_token( $order_id );

        if ( ! $token ) return;

        $token_id = $token['token_id'];

        $create_token_res = $magpie->retrieve_token( $token_id, $this->publishable_key );

        $card_token = json_decode( $create_token_res );
        
        $charge_payload = array(
            'amount'                => $amount,
            'source'                => $card_token->id,
            'description'           => '[Tag]:' . $this->payment_description . '[Order ID]:' . $order_id . '[Customer Name]:' . $customer_name,
            'statement_descriptor'  => get_bloginfo( 'name' ),
            'capture'               => true,
        );

        $charge_response = $magpie->create_charge( $charge_payload, $this->private_key );

        $charge_data = json_decode( $charge_response );

        if ( isset( $charge_data->error ) ) {
            $error = $charge_data->error->message . ' Failed to create charge.';

            $order->add_order_note( 'Payment failed please try again. ' . $error, false );

            $data = array(
                'order_id'          => $order_id,
                'message'           => $error,
                'new_order_status'  => 'failed',
            );
    
            $magpie_backend->update_order_status( $data );
        }

        $data = array(
            'order_id'          => $order_id,
            'message'           => $charge_data->id,
            'new_order_status'  => 'processing',
        );

        $magpie_backend->save_magpie_charge( $order_id, $charge_data );
        
        $magpie_backend->update_order_status( $data );
    }
}
