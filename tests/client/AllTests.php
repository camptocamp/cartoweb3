<?php

require_once 'PHPUnit2/Framework/TestSuite.php';

require_once 'CartoclientTest.php';
require_once 'CartoserverServiceTest.php';

/**
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
