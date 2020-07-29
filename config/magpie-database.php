<?php
/**
 * Magpie Database Connection Class
 */

class Magpie_Database {
    // Database credentials
    protected $host       = 'localhost';
    protected $username   = 'root';
    protected $password   = 'zotacpowered14';
    protected $database   = 'wp_barapidomart';

    protected $_con = null;

    public function connect() {
        $this->_con = mysqli_connect( $this->host, $this->username, $this->password, $this->database ) or die( 'Error connection to MySQL server.' );

        if ( $this->_con ) {
            $db = mysqli_select_db( $this->_con, $this->database ) or die( 'Error '{mysqli_error( $this->_con )} );

            if ( $db ) {
                return $this->_con;
            }
        }
    }
}
