<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');

/**
 * Unit tests for class Cartoserver
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class server_CartoserverTest extends PHPUnit2_Framework_TestCase {
    
    private $cartoserver;

    public function setUp() {
        $this->cartoserver = new Cartoserver();
    }

    public function testGetMapInfo() {
        $mapInfo = $this->cartoserver->getMapInfo('test');

        $this->assertNotNull($mapInfo);
        // TODO: more assertions     
    }

    /*
    public function testGetMap() {
        $mapRequest = new MapRequest();
        $mapRequest->mapId = 'test';
        
        $mapResult = $this->cartoserver->getMap($mapRequest);
        //var_dump($mapResult);
    }
    */
}
?>