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
     
        $this->assertType('GeoDimension', $mapInfo->keymapGeoDimension);
        $this->assertEquals(100, $mapInfo->keymapGeoDimension->dimension->width);
        $this->assertEquals(-0.5, $mapInfo->keymapGeoDimension->bbox->minx);
     
        $this->assertEquals('bar', $mapInfo->getLayerById('root')
                                               ->getMetadata('foo'));
        $this->assertEquals('admin', $mapInfo->getLayerById('group_admin')
                                              ->getMetadata('security_view'));
        $this->assertEquals('admin', $mapInfo->getLayerById('layer_admin')
                                              ->getMetadata('security_view'));

        $this->redoDirect($direct, __METHOD__);
    }
    
    public function testGetMap($direct = false) {
    
        $mapRequest = $this->createRequest();
        $mapResult = $this->getMap($mapRequest, $direct);
        $this->assertNotNull($mapResult->imagesResult->mainmap->path);
        // TODO: more assertions
        $this->redoDirect($direct, __METHOD__);
    }
}

?>
