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
 * All coreplugins tests
 */
require_once 'coreplugins/location/server/ServerLocationTest.php';
require_once 'coreplugins/location/server/RemoteServerLocationTest.php';
require_once 'coreplugins/query/server/RemoteServerQueryTest.php';
require_once 'coreplugins/tables/common/TablesCommonTest.php';

/**
 * @package Tests
 * @author      Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class coreplugins_AllTests {
    
    public static function suite() {
    
        $suite = new PHPUnit2_Framework_TestSuite;

        $suite->addTestSuite('coreplugins_location_server_ServerLocationTest');
        $suite->addTestSuite('coreplugins_location_server_RemoteServerLocationTest');
        $suite->addTestSuite('coreplugins_query_server_RemoteServerQueryTest');
        $suite->addTestSuite('coreplugins_tables_common_TablesCommonTest');

        return $suite;
    }
}

?>