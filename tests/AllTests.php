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
require_once 'coreplugins/AllTests.php';
require_once 'plugins/AllTests.php';
require_once 'projects/AllTests.php';
require_once 'server/AllTests.php';

/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class AllTests {

    /**
     * @return PHPUnit2_Framework_TestSuite
     */
    public static function suite() {

        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTest(client_AllTests::suite());
        $suite->addTest(common_AllTests::suite());
        $suite->addTest(coreplugins_AllTests::suite());
        $suite->addTest(plugins_AllTests::suite());
        $suite->addTest(projects_AllTests::suite());
        $suite->addTest(server_AllTests::suite());

        return $suite;
    }
}

?>
