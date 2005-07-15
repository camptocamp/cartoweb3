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

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOCOMMON_HOME . 'coreplugins/location/common/Location.php');
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server location plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class projects_testLocationContinuous_coreplugins_location_server_RemoteServerLocationTest
                    extends client_CartoserverServiceWrapper {

    protected function getMapId() {
        return 'test_location_continuous.test';
    }

    private function doTestLocationRequest($locationType, $locationRequestName,
                        $locationRequest, $expectedBbox, $expectedScale, $direct) {
                
        $requ = new LocationRequest();
        $requ->locationType = $locationType;
        $requ->$locationRequestName = $locationRequest;
        
        // FIXME: there should be an additional parameter for this
        if (isset($locationRequest->constraint))
            $requ->locationConstraint = $locationRequest->constraint;

        $mapRequest = $this->createRequest();
        $mapRequest->locationRequest = $requ;
        
        // set a square mainmap
        $mapRequest->imagesRequest->mainmap->width = 200;
        $mapRequest->imagesRequest->mainmap->height = 200;
        
        $mapResult = $this->getMap($mapRequest, $direct);
        
        $this->assertIsMapResult($mapResult);
    
        $resultBbox = $mapResult->locationResult->bbox;
        if ($expectedBbox != NULL) {
            $this->assertSameBbox($expectedBbox, $resultBbox);
        }
        
        $resultScale = $mapResult->locationResult->scale;
        if (!is_null($expectedScale) && $expectedScale != -1) {
            $this->assertTrue($this->almostEq($expectedScale, $resultScale), 
                "scale are not the same, expected $expectedScale, got $resultScale");
        }
        return $mapResult;
    }
    
    private function doTestBboxLocationRequest($bbox, $expectedBbox, 
                                               $expectedScale = -1, $direct) {

        $bboxLocationRequest = new BboxLocationRequest();
        $bboxLocationRequest->bbox = $bbox;

        $this->doTestLocationRequest(LocationRequest::LOC_REQ_BBOX,
                    'bboxLocationRequest', $bboxLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }
    
    private function doTestZoomPointLocationRequest($zoomPointLocationRequest, 
                              $expectedBbox, $expectedScale = -1, $direct) {

        $this->doTestLocationRequest(LocationRequest::LOC_REQ_ZOOM_POINT,
                    'zoomPointLocationRequest', $zoomPointLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }

    private function doTestScaleDiscreteZoomPointLocationRequest($zoomType, 
                                             $bbox, $expectedScale, $direct) {
        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = $zoomType;
        $zoomPointLocationRequest->bbox = $bbox;
        $point = new Point(0, 51);
        $zoomPointLocationRequest->point = $point;

        $this->doTestZoomPointLocationRequest($zoomPointLocationRequest,
                                               NULL, $expectedScale, $direct);
    }

    public function testZoomPointLocationRequest_zoomIn_continuous($direct = false) {

        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = ZoomPointLocationRequest::ZOOM_DIRECTION_IN;
        $bbox = new Bbox(-.5, 50.5, .5, 51.5);
        $scale = 14.173236;
        $zoomFactor = 1.5;
        $zoomPointLocationRequest->bbox = $bbox;
        $point = new Point(0, 51);
        $zoomPointLocationRequest->point = $point;

        $this->doTestZoomPointLocationRequest($zoomPointLocationRequest,
                                              NULL, $scale / $zoomFactor, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function _testZoomPointLocationRequest_zoomOut_continuous($direct = false) {

        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = ZoomPointLocationRequest::ZOOM_DIRECTION_OUT;
        $bbox = new Bbox(-.5, 50.5, .5, 51.5);
        $scale = 14.173236;
        $zoomFactor = 1.5;
        $zoomPointLocationRequest->bbox = $bbox;
        $point = new Point(0, 51);
        $zoomPointLocationRequest->point = $point;

        $this->doTestZoomPointLocationRequest($zoomPointLocationRequest,
                                              NULL, $scale * $zoomFactor, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
}
?>