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
 * All projects tests
 */
require_once 'projects/testproject/server/RemoteServerProjectTest.php';

/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class projects_AllTests {
    
    public static function suite() {
    
        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('projects_testproject_server_RemoteServerProjectTest');

        return $suite;
    }
}

?>