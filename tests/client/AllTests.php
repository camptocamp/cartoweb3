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
 * All client tests
 */
require_once 'CartoclientTest.php';
require_once 'CartoserverServiceTest.php';

/**
 * @package Tests
 * @author      Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class client_AllTests {
    
    public static function suite() {
    
        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('client_CartoclientTest');
        $suite->addTestSuite('client_CartoserverServiceTest');

        return $suite;
    }

}

?>
