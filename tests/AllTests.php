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
 * All tests
 */
require_once 'client/AllTests.php';
require_once 'common/AllTests.php';

/**
 * @package Tests
 * @author  Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class AllTests {

    public static function suite() {

        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTest(client_AllTests::suite());
        $suite->addTest(common_AllTests::suite());

        return $suite;
    }
}

?>
