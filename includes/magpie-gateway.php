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
        $this->auto_charge          = $this->get_option( 'auto_charge' );

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
                'description' => 'For automatic credit charge. Uncheck this if you want to charge customer after order is completed.',
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
        $magpie = new WC_Magpie();

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
                'order_notes'         => $_POST['order_comments']  
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

        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        $card_details = array(
            'name'      => $customer_name,
            'number'    => str_replace(' ', '', $_POST['magpie_cc-card-number']),
            'exp_month' => $exp_month,
            'exp_year'  => '20' . $exp_year,
            'cvc'       => $_POST['magpie_cc-card-cvc'],            
        );

        $payload = array(
            'amount'            => $order->get_total(),    
            'order_id'          => $order->get_order_number(),
            'stmt_desc'         => get_bloginfo( 'name' ),
            'public_key'        => $this->publishable_key,
            'private_key'       => $this->private_key,
            'description'       => $this->payment_description,
            'auto_charge'       => $this->auto_charge === 'yes' ? true : false,
            'card_details'      => json_encode( $card_details ),
            'is_registered'     => is_user_logged_in(),
            'customer_details'  => json_encode( $customer_details ),
        );
        
        $ch = curl_init();
        
        $post_url = get_site_url() . '/?magpie-process';

        curl_setopt_array( $ch, array(
            CURLOPT_URL             => $post_url,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $payload,
            CURLOPT_RETURNTRANSFER  => true,
        ) );
        
        $response = curl_exec( $ch );

        $body = json_decode( $response );

        if ( isset( $body->error ) ) {
            print_r( $body );

            wc_add_notice( 'Transaction failed! This could be on our side please contact administrator.' , 'error' );

            return;
        }

        curl_close( $ch );

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

    /*
     * Process payment charge here
     */
    public function process_order_charge( $order_id ) {
        $magpie = new WC_Magpie();
        $magpie_backend = new WC_Magpie_Backend();

        $order = wc_get_order( $order_id );

        $amount = $order->get_total();

        $charge = $magpie_backend->get_order_status( $order_id );

        $charge_id = $charge['message'];

        $response = $magpie->retrieve_charge( $charge_id, $this->private_key );

        $is_charge = json_decode( $response );

        $data = array(
            'order_id'  => $order_id,
            'message'   => $is_charge->id,
        );

        if ( $is_charge->captured ) {
            $order->add_order_note( 'Payment successfully charged PHP ' . $is_charge->amount, true );

            $data['new_order_status'] = 'completed';
    
            $magpie_backend->update_order_status( $data );

            return;
        }

        $response = $magpie->capture_charge( $charge_id, $amount, $this->private_key );

        $body = json_decode( $response );

        if ( ! isset( $body->error ) ) {
            $order->add_order_note( 'Payment successfully charged PHP ' . $body->amount, true );

            wc_reduce_stock_levels( $order_id );

            $data['new_order_status'] = 'completed';

            $magpie_backend->update_order_status( $data );
        } else {
            $error = $body->error->message;

            $order->add_order_note( 'Payment failed please try again. ' . $error, false );
            
            $data['new_order_status'] = 'failed';
    
            $magpie_backend->update_order_status( $data );
        }
    }
}

add_action( 'woocommerce_before_order_itemmeta', 'testing_stuff', 10, 3 );
function testing_stuff( $item_id, $item, $order_id) {
    // For testing
}