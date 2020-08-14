<?php
/** Magpie API Class */
class WC_Magpie {
    
    public function create_token( $data, $public_key ) {
        $magpie_api_url = 'tokens';

        $data = array(
            'card' => array(
                'name'              => $data['card_name'],
                'number'            => $data['card_number'],
                'exp_month'         => $data['exp_month'],
                'exp_year'          => $data['exp_year'],
                'cvc'               => $data['cvc'],
                'address_city'      => $data['address_city'],
                'address_country'   => $data['address_country'],
                'address_line1'     => $data['address_line1'],
                'address_zip'       => $data['address_zip']
            )
        );

        $result = $this->curl_post( $magpie_api_url, $data, $public_key );

        return $result;
    }

    public function retrieve_token( $token_id, $public_key ) {
        $magpie_api_url = 'tokens/' . $token_id;

        $data = array( '' => '' );

        $result = $this->curl_get( $magpie_api_url, $data, $public_key );

        return $result;
    }

    public function create_charge( $params, $private_key ) {
        $magpie_api_url = 'charges';

        $data = array(
            'amount'                => $params['amount'],
            'currency'              => 'php',
            'source'                => $params['source'],
            'description'           => $params['description'],
            'statement_descriptor'  => $params['statement_descriptor'],
            'capture'               => $params['capture'],
        );

        $result = $this->curl_post( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function retrieve_charge( $charge_id, $private_key ) {
        $magpie_api_url = 'charges/'.$charge_id;

        $data = array( '' => '' );

        $result = $this->curl_get( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function capture_charge( $charge_id, $amount, $private_key ) {
        $magpie_api_url = 'charges/'.$charge_id.'/capture';

        $data = array( 'amount' => $amount );

        $result = $this->curl_post( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function void_charge( $charge_id, $private_key ) {
        $magpie_api_url = 'charges/'.$charge_id.'/void';

        $data = array( '' => '' );

        $result = $this->curl_post( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function refund_charge( $charge_id, $amount, $private_key ) {
        $magpie_api_url = 'charges/'.$charge_id.'/refund';

        $data = array( 'amount' => $amount );

        $result = $this->curl_post($magpie_api_url, $data, $private_key);

        return $result;
    }

    public function create_customer( $customer_data, $private_key ) {
        $magpie_api_url = 'customers';

        $data = array( 
            'email'         => $customer_data['email'],
            'description'   => $customer_data['description']
        );

        $result = $this->curl_post( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function retrieve_customer( $customer_id, $private_key ) {
        $magpie_api_url = 'customers/'.$customer_id;

        $data = array( '' => '' );

        $result = $this->curl_get( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function delete_customer( $customer_id, $private_key ) {
        $magpie_api_url = 'customers/'.$customer_id;

        $data = array( '' => '' );

        $result = $this->curl_delete( $magpie_api_url, $data, $private_key );

        return $result;
    }

    public function update_customer( $customer_id, $token_id, $private_key ) {
        $magpie_api_url = 'customers/'.$customer_id;

        $data = array( 'source' => $token_id );

        $result = $this->curl_put( $magpie_api_url, $data, $private_key );

        return $result;
    }

    /** CURL functions */
    public function curl_post( $magpie_api_url, $data, $api_key ) {
        $params = array(
            'url' => 'https://api.magpie.im/v1/'.$magpie_api_url,
            'request' => 'POST',
        );

        return $this->curl_set_option( $params, $data, $api_key );
    }

    public function curl_get( $magpie_api_url, $data, $api_key ) {
        $params = array(
            'url' => 'https://api.magpie.im/v1/'.$magpie_api_url,
            'request' => 'GET',
        );

        return $this->curl_set_option( $params, $data, $api_key );
    }

    public function curl_delete( $magpie_api_url, $data, $api_key ) {
        $params = array(
            'url' => 'https://api.magpie.im/v1/'.$magpie_api_url,
            'request' => 'DELETE',
        );

        return $this->curl_set_option( $params, $data, $api_key );
    }

    public function curl_put( $magpie_api_url, $data, $api_key ) {
        $params = array(
            'url' => 'https://api.magpie.im/v1/'.$magpie_api_url,
            'request' => 'PUT',
        );

        return $this->curl_set_option( $params, $data, $api_key );
    }

    public function curl_set_option( $params, $data, $api_key ) {
        $ch = curl_init();
    
        curl_setopt_array( $ch, array(
            CURLOPT_URL             => $params['url'],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_CUSTOMREQUEST   => $params['request'],
            CURLOPT_POSTFIELDS      => json_encode( $data ),
            CURLOPT_HTTPHEADER      => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode( $api_key . ':' ),
            )
        ) );
    
        $response = curl_exec( $ch );
    
        curl_close( $ch );
    
        return $response;
    }
}
