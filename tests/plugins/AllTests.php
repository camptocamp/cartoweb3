<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test suite
 */
require_once 'PHPUnit2/Framework/TestSuite.php';

/**
 * All plugins tests
 */
require_once 'plugins/outline/server/RemoteServerOutlineTest.php';

/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class plugins_AllTests {
    
    public static function suite() {
    
        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('plugins_outline_server_RemoteServerOutlineTest');

        return $suite;
    }
}

?>