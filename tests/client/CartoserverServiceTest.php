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
require_once(CARTOCLIENT_HOME . 'client/CartoserverService.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/images/common/Images.php');

/**
 * Unit tests for CartoserverService 
 *  
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class client_CartoserverServiceTest extends client_CartoserverServiceWrapper {

    public function testGetMapInfo($direct = false) {

        $mapInfo = $this->getMapInfo('test', $direct);
        $this->assertNotNull($mapInfo);
        $this->assertTrue(is_array($mapInfo->layers));
        $this->assertTrue(is_array($mapInfo->initialMapStates));
     
        $initialMapState = $mapInfo->getInitialMapStateById('default');
        $this->assertType('Bbox', $initialMapState->location->bbox);
     
        $this->redoDirect($direct, __METHOD__);
    }
}

?>
