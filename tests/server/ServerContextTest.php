<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOSERVER_HOME . 'server/ServerContext.php');

/**
 * Unit tests for class ServerContext
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class server_ServerContextTest extends PHPUnit2_Framework_TestCase {

    public function testConstruct() {
     
        $serverContext = new ServerContext('test');
        
        $this->assertNotNull($serverContext);
        // TODO: more assertions
    }

    public function test1() {
        
    }
}
?>