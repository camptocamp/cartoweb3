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
// Please convert the RemoteServerSelectionTest test to use the new query plugin
// require_once 'plugins/selection/server/RemoteServerSelectionTest.php';
require_once 'plugins/auth/client/AuthClientTest.php';

/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class plugins_AllTests {
    
    public static function suite() {
    
        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('plugins_outline_server_RemoteServerOutlineTest');
        // Please convert the RemoteServerSelectionTest test to use the new query plugin
        // $suite->addTestSuite('plugins_selection_server_RemoteServerSelectionTest');
        $suite->addTestSuite('plugins_auth_client_AuthClientTest');

        return $suite;
    }
}

?>
