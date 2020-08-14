<?php
/**
 * Custom Magpie Rewrite Rule
 */
add_action( 'plugins_loaded', array( Magpie_Rewrite::get_instance(), 'setup' ) );
class Magpie_Rewrite {

    protected static $instance = NULL;

    public function __construct() {}

    public static function get_instance() {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }    

    public function setup() {
        add_action( 'init', array( $this, 'rewrite_rules' ));
        add_filter( 'query_vars', array( $this, 'query_vars' ), 10, 1);
        add_action( 'parse_request', array( $this, 'parse_request' ), 10, 1);

        register_activation_hook( __FILE__, array( $this, 'flush_rules' ));
    }

    public function rewrite_rules() {
        add_rewrite_rule( 'magpie-process/?$', 'index.php?magpie-process=true', 'top' );
    }

    public function flush_rules() {
        $this->rewrite_rules();
        flush_rewrite_rules();
    }

    public function query_vars( $vars ) {
        $vars[] = 'magpie-process';
        return $vars;
    }

    public function parse_request( $wp ) {
        if ( array_key_exists( 'magpie-process', $wp->query_vars ) ){
            include plugin_dir_path( __FILE__ ) . 'magpie-process.php';
            exit();
        }
    }
}