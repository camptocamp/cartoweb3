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

require_once(CARTOCOMMON_HOME . 'plugins/hilight/common/Hilight.php');

/**
 * Unit test for server hilight plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class plugins_hilight_server_RemoteServerHilightTest
                    extends client_CartoserverServiceWrapper {

    private function doTestHilightRequest($layerId, $selectedIds, 
                                            $expectedArea, $direct) {

        $hilightRequest = new HilightRequest();
        $hilightRequest->layerId = $layerId;
        $hilightRequest->selectedIds = $selectedIds;
        $hilightRequest->calculateArea = true;

        $mapRequest = $this->createRequest();
        $mapRequest->hilightRequest = $hilightRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals($expectedArea, $mapResult->hilightResult->area);
    }
    
    function testHilightFixedValue0($direct = false) {
        $this->doTestHilightRequest('grid_defaulthilight', array(),
                                   0.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testHilightFixedValue1($direct = false) {
        $this->doTestHilightRequest('grid_defaulthilight', array('11', '12'),
                                   20.0, $direct);
        //$this->redoDirect($direct, __METHOD__);
    }

    function testHilightFixedValue2($direct = false) {
        $this->doTestHilightRequest('grid_defaulthilight', array('11'),
                                   10.0, $direct);
        //$this->redoDirect($direct, __METHOD__);
    }

    function testHilightFromField0($direct = false) {
        $this->doTestHilightRequest('grid_classhilight', array(),
                                   0.0, $direct);
        //$this->redoDirect($direct, __METHOD__);
    }

    function testHilightFromField1($direct = false) {
        $this->doTestHilightRequest('grid_classhilight', array('11', '12'),
                                   23.0, $direct);
        //$this->redoDirect($direct, __METHOD__);
    }

    function testHilightFromField2($direct = false) {
        $this->doTestHilightRequest('grid_classhilight', array('11'),
                                   11.0, $direct);
        //$this->redoDirect($direct, __METHOD__);
    }

    function testHilightFromFieldWithFactor($direct = false) {
        $this->doTestHilightRequest('grid_layerhilight', array('11'),
                                   110.0, $direct);
        //$this->redoDirect($direct, __METHOD__);
    }
}
?>