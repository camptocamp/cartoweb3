<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Configuration for test cases
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class common_Config {

    private static $instance;

    private $ini_array;

    function __construct() {
     
        $ini_file = CARTOCOMMON_HOME . '/tests/test.ini';
        if (!is_readable($ini_file)) {
            $this->ini_array = array();
            return;   
        }
            
        $this->ini_array = parse_ini_file($ini_file);
    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new common_Config();
        }
        return self::$instance;
    }

    function __get($nm) {
        if (isset($this->ini_array[$nm])) {
            $r = $this->ini_array[$nm];
            return $r;
        } else {
            return NULL;
        }
    }
}
?>