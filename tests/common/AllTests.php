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
 * All common tests
 */
require_once 'common/MiscTest.php';
require_once 'common/BasicTypesTest.php';
require_once 'common/MapInfoTest.php';
require_once 'common/SerializableTest.php';

/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_AllTests {

    public static function suite() {

        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('common_MiscTest');
        $suite->addTestSuite('common_BasicTypesTest');
        $suite->addTestSuite('common_MapInfoTest');
        $suite->addTestSuite('common_SerializableTest');

        return $suite;
    }
}

?>
