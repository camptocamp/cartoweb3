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

require_once(CARTOCOMMON_HOME . 'plugins/selection/common/Selection.php');
require_once(CARTOCOMMON_HOME . 'plugins/hilight/common/Hilight.php');

/**
 * Unit test for server selection plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class plugins_selection_server_RemoteServerSelectionTest
                    extends client_CartoserverServiceWrapper {

    private function doTestSelectionRequest($bbox, $policy, $layerId, 
                                $selectedIds, $expectedIds, $direct = false) {
        
        $selectionRequest = new SelectionRequest(); 
        $selectionRequest->bbox = $bbox;
        $selectionRequest->policy = $policy;  

        $hilightRequest = new HilightRequest();
        $hilightRequest->layerId = $layerId;
        $hilightRequest->selectedIds = $selectedIds;

        $mapRequest = $this->createRequest();
        $mapRequest->selectionRequest = $selectionRequest;
        $mapRequest->hilightRequest = $hilightRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $expectedIds = array_values($expectedIds);
        $returnedIds = array_values($mapResult->selectionResult->selectedIds);

        $this->assertEquals($expectedIds, $returnedIds);
    }

    function testSelectionRequestUnion($direct = false) {
        $this->doTestSelectionRequest(new Bbox(-.01, 51.49, .01, 51.51), 
                SelectionRequest::POLICY_UNION, 'grid_defaulthilight', 
                array('10', '12'), array('10', '12'), $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionRequestXor($direct = false) {
        $this->doTestSelectionRequest(new Bbox(-.01, 51.49, .01, 51.51), 
                SelectionRequest::POLICY_XOR, 'grid_defaulthilight', 
                array('10', '12'), array('12'), $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionRequestIntersection($direct = false) {
        $this->doTestSelectionRequest(new Bbox(-.01, 51.49, .01, 51.51), 
                SelectionRequest::POLICY_INTERSECTION, 'grid_defaulthilight', 
                array('10', '12'), array('10'), $direct);
        $this->redoDirect($direct, __METHOD__);
    }
}
?>