<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOCOMMON_HOME . 'coreplugins/query/common/Query.php');
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');

/**
 * Unit test for server query plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class coreplugins_query_server_RemoteServerQueryTest
                    extends client_CartoserverServiceWrapper {

    function isTestDirect() {
        return false;   
    }

    function testQueryRequest1($direct = false) {
    
        // TODO

        $this->redoDirect($direct, __METHOD__);
    }
}
?>