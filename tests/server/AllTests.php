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
 * All server tests
 */
require_once 'server/ServerContextTest.php';
require_once 'server/CartoserverTest.php';

/**
 * @package Tests
 * @author      Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class server_AllTests {

    public static function suite() {

        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('server_CartoserverTest');
        $suite->addTestSuite('server_ServerContextTest');

        return $suite;
    }
}

?>
