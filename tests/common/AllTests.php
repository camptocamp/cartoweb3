<?php

require_once 'PHPUnit2/Framework/TestSuite.php';

require_once 'BasicTypesTest.php';

/**
 * @author      Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_AllTests {

    public static function suite() {

        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('common_BasicTypesTest');

        return $suite;
    }
}

?>
