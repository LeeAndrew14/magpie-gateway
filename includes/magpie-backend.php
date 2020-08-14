<?php
/** Custom Magpie Backend Class */
class WC_Magpie_Backend {

    protected $db;
    protected $_con;

    public function __construct() {
        $this->_con = new Magpie_Database();
        $this->db = $this->_con->connect();
    }

    public function get_user_by_email( $email ) {
        $sql = "SELECT * FROM magpie_customer_data WHERE email = '$email'";

        $result = $this->db->query( $sql );

        return $result->fetch_assoc();
    }

    public function get_magpie_customer_by_email( $email ) {
        $sql = "SELECT * FROM magpie_customer WHERE email = '$email'";

        $result = $this->db->query( $sql );

        return $result->fetch_assoc();
    }

    public function get_order_status( $order_id ) {
        $sql = "SELECT * FROM magpie_order_status WHERE order_id = '{$order_id}'";

        $result = $this->db->query( $sql );

        return $result->fetch_assoc();
    }

    public function check_if_order_exist( $order_id ) {
        $sql = "SELECT * FROM magpie_order_status WHERE order_id = '$order_id'";

        $result = $this->db->query( $sql );

        return $result->num_rows;
    }

    public function save_customer_details( $data ) {
        $sql = 
            "INSERT INTO magpie_customer_data (
                email, 
                first_name, 
                last_name,
                user_role,
                billing_first_name,
                billing_last_name,
                billing_company,
                billing_address_1,
                billing_city,
                billing_state,
                billing_postcode,
                billing_country,
                billing_email,
                billing_phone,
                shipping_first_name,
                shipping_last_name,
                shipping_company,
                shipping_address_1,
                shipping_city,
                shipping_state,
                shipping_postcode,
                shipping_country )
            VALUES (
                '$data->email', 
                '$data->first_name', 
                '$data->last_name',
                '$data->role',
                '$data->billing_first_name',
                '$data->billing_last_name',
                '$data->billing_company',
                '$data->billing_address_1',
                '$data->billing_city',
                '$data->billing_state',
                '$data->billing_postcode',
                '$data->billing_country',
                '$data->billing_email',
                '$data->billing_phone',
                '$data->shipping_first_name',
                '$data->shipping_last_name',
                '$data->shipping_company',
                '$data->shipping_address_1',
                '$data->shipping_city',
                '$data->shipping_state',
                '$data->shipping_postcode',
                '$data->shipping_country'
        )";

        $this->db->query( $sql );

        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }

    public function save_magpie_customer( $data ) {
        $data->delinquent ? $data->delinquent = 1 : $data->delinquent = 0;

        $sources = json_encode( $data->sources );

        $created = date( 'Y/m/d H:i:s', intval( $data->created ) );

        $sql = 
            "INSERT INTO magpie_customer (
                customer_id,
                account_balance,
                created,
                currency,
                default_source,
                delinquent,
                description,
                email,
                sources,
                source_type, 
                object )
            VALUES (
                '$data->id',
                '$data->account_balance',
                '$created',
                'PHP',
                '$data->default_source',
                '$data->delinquent',
                '$data->description',
                '$data->email',
                '$sources',
                '$data->source_type',
                '$data->object'
        )";

        $this->db->query( $sql );

        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }

    public function save_magpie_token( $order_id, $data ) {
        $sql = 
            "INSERT INTO magpie_token ( 
                order_id, 
                token_id, 
                object )
            VALUES ( 
                '$order_id',
                '$data->id',
                '$data->object'
        )";

        $this->db->query( $sql );

        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }

    public function save_magpie_charge( $order_id, $data ) {
        $charge_details = json_encode( $data );

        $sql = 
            "INSERT INTO magpie_charge (
                order_id,
                charge_id,
                charge_details ) 
            VALUES (
                '$order_id',
                '$data->id',
                '$charge_details' 
        )";

        $this->db->query( $sql );

        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }

    public function save_order_status( $data ) {
        $message        = $data['message'];
        $order_id       = $data['order_id'];
        $order_status   = $data['order_status']; 

        $sql = 
            "INSERT INTO magpie_order_status (
                order_id, 
                order_status,
                message )
            VALUES (
                '$order_id', 
                '$order_status',
                '$message'
        )";

        $this->db->query( $sql );

        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }

    public function update_order_status( $data ) {
        $message        = $data['message']; 
        $order_id       = $data['order_id'];
        $order_status   = $data['new_order_status']; 

        $sql = 
            "UPDATE magpie_order_status 
            SET 
                order_status = '$order_status',
                message = '$message' 
            WHERE 
                order_id = '$order_id'";

        $this->db->query( $sql );

        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }

    public function update_magpie_user_source( $customer_id, $data ) {
        $sources = json_encode( $data->sources );

        $sql = 
            "UPDATE magpie_customer
            SET
                sources = '$sources'
            WHERE
                customer_id = '$customer_id'";

        $this->db->query( $sql );
        
        $error = mysqli_error( $this->db );
        
        $success = $this->db->affected_rows;

        return $error ? $error : $success;
    }
}
