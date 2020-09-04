<?php
/**
 * Init Magpie Database
 */

global $magpie_db_version;
$magpie_db_version = '1.0.0';

function magpie_db_init() {
    global $wpdb;
    global $magpie_db_version;

    $installed_ver = get_option( 'magpie_db_version' );

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    $magpie_token           = $wpdb->prefix . 'magpie_token'; 
    $magpie_charge          = $wpdb->prefix . 'magpie_charge';
    $magpie_customer        = $wpdb->prefix . 'magpie_customer';
    $magpie_order_status    = $wpdb->prefix . 'magpie_order_status';
    $magpie_customer_data   = $wpdb->prefix . 'magpie_customer_data';

    if ( $installed_ver !== '1.0.1' ) {
        // Check if table already exist
        $sql = 
            "SELECT COUNT(*) AS total FROM 
                information_schema.tables
            WHERE 
                TABLE_SCHEMA = '$wpdb->dbname'
            AND 
                TABLE_NAME 
            IN ( '$magpie_token',
                '$magpie_charge',
                '$magpie_customer',
                '$magpie_order_status',
                '$magpie_customer_data'
            );";
        
        $result = $wpdb->get_results( $sql );

        if ( isset( $result[0]->total ) ) {
            if ( $result[0]->total < 5 ) {
                $sql = 
                    "CREATE TABLE $magpie_token (
                        id INT(6) NOT NULL auto_increment,
                        order_id INT(6),
                        token_id TEXT,
                        object TEXT,
                        created_at DATETIME default current_timestamp,
                        updated_at DATETIME ON UPDATE current_timestamp,
                        PRIMARY KEY (id)
                    ) $charset_collate;
                
                    CREATE TABLE $magpie_charge (
                        id INT(6) NOT NULL auto_increment,
                        order_id INT(6),
                        charge_id TEXT,
                        charge_details TEXT,
                        created_at DATETIME default current_timestamp,
                        updated_at DATETIME ON UPDATE current_timestamp,
                        PRIMARY KEY (id)
                    ) $charset_collate;
    
                    CREATE TABLE $magpie_customer (
                        id INT(6) NOT NULL auto_increment,
                        customer_id TEXT,
                        account_balance double,
                        created DATETIME,
                        currency tinyTEXT,
                        default_source TEXT,
                        delinquent bool,
                        description TEXT,
                        email TEXT,
                        sources mediumTEXT,
                        source_type TEXT,
                        object TEXT,
                        created_at DATETIME default current_timestamp,
                        updated_at DATETIME ON UPDATE current_timestamp,
                        PRIMARY KEY (id)
                    ) $charset_collate;
                    
                    CREATE TABLE $magpie_order_status (
                        id INT(6) NOT NULL auto_increment,
                        order_id INT(6), 
                        order_key TEXT,
                        order_status TEXT,
                        message TEXT,
                        created_at DATETIME default current_timestamp,
                        updated_at DATETIME ON UPDATE current_timestamp,
                        PRIMARY KEY (id)
                    ) $charset_collate;
                    
                    CREATE TABLE $magpie_customer_data (
                        id INT(6) NOT NULL auto_increment,
                        email TEXT,
                        first_name TEXT,
                        last_name TEXT,
                        phone TEXT,
                        user_role TEXT,
                        user_level tinyINT,
                        product_vendor_owner TEXT,
                        product_vendor TEXT,
                        billing_first_name TEXT,
                        billing_last_name TEXT,
                        billing_company TEXT,
                        billing_address_1 TEXT,
                        billing_address_2 TEXT,
                        billing_city TEXT,
                        billing_postcode TEXT,
                        billing_country TEXT,
                        billing_state TEXT,
                        billing_phone TEXT,
                        billing_email TEXT,
                        shipping_first_name TEXT,
                        shipping_last_name TEXT,
                        shipping_company TEXT,
                        shipping_address_1 TEXT,
                        shipping_address_2 TEXT,
                        shipping_city TEXT,
                        shipping_postcode TEXT,
                        shipping_country TEXT,
                        shipping_state TEXT,
                        created_at DATETIME default current_timestamp,
                        updated_at DATETIME ON UPDATE current_timestamp,
                        PRIMARY KEY (id)
                    ) $charset_collate;";
    
                dbDelta( $sql );
    
                update_option( 'magpie_db_version', '1.0.0' );    
            }
        }
    }

    if ( $installed_ver !== '1.0.2' ) {
        $sql = "SHOW COLUMNS FROM $magpie_order_status LIKE 'order_key';";

        $result = $wpdb->query( $sql );

        if ( ! $result ) {
            $sql = "ALTER TABLE $magpie_order_status ADD order_key TEXT AFTER order_id;";

            $wpdb->query( $sql );
    
            update_option( 'magpie_db_version', '1.0.0' );
        }
    }
}

function magpie_update_db_check() {
    global $magpie_db_version;
    if ( get_site_option( 'magpie_db_version' ) != $magpie_db_version ) {
        magpie_db_init();
    }
}
add_action( 'plugins_loaded', 'magpie_update_db_check' );
