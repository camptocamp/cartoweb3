<?php

require_once 'PHPUnit2/Framework/TestSuite.php';

require_once 'BasicTypesTest.php';
require_once 'MapInfoTest.php';
require_once 'SerializableTest.php';

/**
 * @author      Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_AllTests {

    public static function suite() {

        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('common_BasicTypesTest');
        $suite->addTestSuite('common_MapInfoTest');
        $suite->addTestSuite('common_SerializableTest');

        return $suite;
    }
}

?>
