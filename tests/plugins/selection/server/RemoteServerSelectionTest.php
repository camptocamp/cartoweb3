<?php
/**
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Tests
 * @version $Id$
 */

///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
// This is currently disabled. Convert this to use the new query plugin
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////

/**
 * Abstract test case
 */
require_once 'PHPUnit/Framework/TestCase.php';
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOWEB_HOME . 'plugins/selection/common/Selection.php');
require_once(CARTOWEB_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server selection plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class plugins_selection_server_RemoteServerSelectionTest
                    extends client_CartoserverServiceWrapper {

    private function doTestSelectionRequest($bbox, $policy, $layerId, 
                                $selectedIds, $expectedIds, $retrieveAttributes, 
                                $direct = false) {
        
        $selectionRequest = new SelectionRequest(); 
        $selectionRequest->bbox = $bbox;
        $selectionRequest->policy = $policy;  

        $selectionRequest->layerId = $layerId;
        $selectionRequest->selectedIds = $selectedIds;
        $selectionRequest->returnResults = true;
        $selectionRequest->retrieveAttributes = $retrieveAttributes;

        $mapRequest = $this->createRequest();
        $mapRequest->selectionRequest = $selectionRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        if (!is_null($expectedIds)) {
            $expectedIds = array_values($expectedIds);
            $returnedIds = array_values($mapResult->selectionResult->selectedIds);
            $this->assertEquals($expectedIds, $returnedIds);
        }

        return $mapResult;
    }

    function _testSelectionRequestUnion($direct = false) {
        $this->doTestSelectionRequest(new Bbox(-.01, 51.49, .01, 51.51), 
                SelectionRequest::POLICY_UNION, 'grid_defaulthilight', 
                array('10', '12'), array('10', '12'), false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function _testSelectionRequestXor($direct = false) {
        $this->doTestSelectionRequest(new Bbox(-.01, 51.49, .01, 51.51), 
                SelectionRequest::POLICY_XOR, 'grid_defaulthilight', 
                array('10', '12'), array('12'), false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function _testSelectionRequestIntersection($direct = false) {
        $this->doTestSelectionRequest(new Bbox(-.01, 51.49, .01, 51.51), 
                SelectionRequest::POLICY_INTERSECTION, 'grid_defaulthilight', 
                array('10', '12'), null, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    private function getTotalArea($selectionResult) {
        if (is_null($selectionResult))
            return 0.0;
        $area = 0.0;
        $layerResult = $selectionResult->layerResults[0];
        $areaIndex = array_search('area', $layerResult->fields);
        if ($areaIndex === false)
            return 0.0;
        foreach ($layerResult->resultElements as $resElement) {
            $area += (double)$resElement->values[$areaIndex];
        }
        return $area;
    }
    
    function doTestSelectionArea($layerId, $selectedIds, $expectedArea, $direct = false) {
        $mapResult = $this->doTestSelectionRequest(null, 
                null, $layerId, 
                $selectedIds, null, true, $direct);
        
        $area = $this->getTotalArea($mapResult->selectionResult);
        $this->assertEquals($expectedArea, $area);
    }
    
    function testSelectionAreaFixedValue0($direct = false) {
        $this->doTestSelectionArea('grid_defaulthilight', array(),
                                   0.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionAreaFixedValue1($direct = false) {
        $this->doTestSelectionArea('grid_defaulthilight', array('11', '12'),
                                   20.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionAreaFixedValue2($direct = false) {
        $this->doTestSelectionArea('grid_defaulthilight', array('11'),
                                   10.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionAreaFromField0($direct = false) {
        $this->doTestSelectionArea('grid_classhilight', array(),
                                   0.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionAreaFromField1($direct = false) {
        $this->doTestSelectionArea('grid_classhilight', array('11', '12'),
                                   23.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionAreaFromField2($direct = false) {
        $this->doTestSelectionArea('grid_classhilight', array('11'),
                                   11.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testSelectionAreaFromFieldWithFactor($direct = false) {
        $this->doTestSelectionArea('grid_layerhilight', array('11'),
                                   110.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
}
?>