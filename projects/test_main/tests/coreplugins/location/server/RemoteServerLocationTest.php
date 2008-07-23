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

require_once(CARTOWEB_HOME . 'coreplugins/location/common/Location.php');
require_once(CARTOWEB_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server location plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class projects_testMain_coreplugins_location_server_RemoteServerLocationTest
                    extends client_CartoserverServiceWrapper {

    protected function getMapId() {
        return 'test_main.test';
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
    
    public function testBboxLocationRequest1($direct = false) {
        $bbox = new Bbox(-1, 50, 1, 52);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function testBboxLocationRequest2($direct = false) {
        $bbox = new Bbox(-.5, 51, .5, 52);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function testBboxLocationRequest3($direct = false) {
        $bbox = new Bbox(-.1, 51.4, .1, 51.6);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function testBboxLocationRequest_maxscale($direct = false) {
        $bbox = new Bbox(-2, 49, 2, 53);
        $this->doTestBboxLocationRequest($bbox, NULL, 30.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function testBboxLocationRequest_minscale($direct = false) {
        $bbox = new Bbox(-.01, 51.49, .01, 51.51);
        $this->doTestBboxLocationRequest($bbox, NULL, 2.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    
    /* Breaks because of limits: add tests for this
    public function testBboxLocationRequest4($direct = false) {
        $bbox = new Bbox(-.1, 51.4, .1, 5196);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    */
    
    private function doTestZoomPointLocationRequest($zoomPointLocationRequest, 
                              $expectedBbox, $expectedScale = -1, $direct) {

        $this->doTestLocationRequest(LocationRequest::LOC_REQ_ZOOM_POINT,
                    'zoomPointLocationRequest', $zoomPointLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }
    
    public function testZoomPointLocationRequest_zoomNone($direct = false) {

        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = ZoomPointLocationRequest::ZOOM_DIRECTION_NONE;
        $bbox = new Bbox(-1, 50, 1, 52);
        $zoomPointLocationRequest->bbox = $bbox;
        $point = new Point(0, 51);
        $zoomPointLocationRequest->point = $point;

        $this->doTestZoomPointLocationRequest($zoomPointLocationRequest,
                                              new Bbox(-1, 50, 1, 52), 
                                              NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function testZoomPointLocationRequest_zoomNone_maxextent_check($direct = false) {

        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = ZoomPointLocationRequest::ZOOM_DIRECTION_NONE;
        $bbox = new Bbox(-11, 50.5, -10, 51.5);
        $zoomPointLocationRequest->bbox = $bbox;
        $point = new Point(-10.5, 51);
        $zoomPointLocationRequest->point = $point;

        $this->doTestZoomPointLocationRequest($zoomPointLocationRequest,
                                              new Bbox(-3, 50.5,-2 ,51.5), 
                                              NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    public function testZoomPointLocationRequest_zoomIn_maxextent_check($direct = false) {

        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = ZoomPointLocationRequest::ZOOM_FACTOR;
        $bbox = new Bbox(-2, 50, 2, 53);
        $zoomPointLocationRequest->bbox = $bbox;
        $point = new Point(-1.8, 51.5);
        $zoomPointLocationRequest->point = $point;

        $zoomPointLocationRequest->zoomFactor = 8; 

        $this->doTestZoomPointLocationRequest($zoomPointLocationRequest,
                                              /* old values, changed because of difference in php5/mapserver5
                                              new Bbox(-1.97638879364,
                                                       51.3236112064,
                                                       -1.62361120636,
                                                       51.6763887936),
                                              */
                                              new Bbox(-1.97550684967,
                                                       51.3244931503,
                                                       -1.62449315033,
                                                       51.6755068497),
                                              NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
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

    public function testZoomPointLocationRequest_zoomIn1($direct = false) {
        // scale is 14.173236
        $this->doTestScaleDiscreteZoomPointLocationRequest(
                            ZoomPointLocationRequest::ZOOM_DIRECTION_IN,
                            $bbox = new Bbox(-.5, 50.5, .5, 51.5), 12.0, $direct);
    }

    public function testZoomPointLocationRequest_zoomOut1($direct = false) {
        // scale is 14.173236
        $this->doTestScaleDiscreteZoomPointLocationRequest(
                            ZoomPointLocationRequest::ZOOM_DIRECTION_OUT,
                            $bbox = new Bbox(-.5, 50.5, .5, 51.5), 30.0, $direct);
    }

    public function testZoomPointLocationRequest_zoomIn2($direct = false) {
        // scale is 28.346472
        $this->doTestScaleDiscreteZoomPointLocationRequest(
                            ZoomPointLocationRequest::ZOOM_DIRECTION_IN,
                            $bbox = new Bbox(-1, 50, 1, 52), 15.0, $direct);
    }

    public function testZoomPointLocationRequest_zoomOut2($direct = false) {
        // scale is 28.346472
        $this->doTestScaleDiscreteZoomPointLocationRequest(
                            ZoomPointLocationRequest::ZOOM_DIRECTION_OUT,
                            $bbox = new Bbox(-1, 50, 1, 52), 30.0, $direct);
    }

    /* TODO: add more tests for zoom point */

    private function doTestPanLocationRequest($bbox, $verticalPan, $horizontalPan, 
                                   $expectedBbox, $expectedScale = -1, $direct) {

        $panLocationRequest = new PanLocationRequest();

        $panDirection = new PanDirection();
        $panDirection->verticalPan = $verticalPan;
        $panDirection->horizontalPan = $horizontalPan;

        $panLocationRequest->panDirection = $panDirection;

        $panLocationRequest->bbox = $bbox;

        $this->doTestLocationRequest(LocationRequest::LOC_REQ_PAN,
                    'panLocationRequest', $panLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }
    
   public function testPanLocationRequest1($direct = false) {

        $bbox = new Bbox(-1, 50, 1, 52);
        $this->doTestPanLocationRequest($bbox, 
                                        PanDirection::VERTICAL_PAN_NORTH,
                                        PanDirection::HORIZONTAL_PAN_EAST,
                                        new Bbox(0.5, 51.5, 2.5, 53.5),
                                        NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }   

   public function testPanLocationRequest2($direct = false) {

        $bbox = new Bbox(-2, 50, 0, 52);
        $this->doTestPanLocationRequest($bbox, 
                                        PanDirection::VERTICAL_PAN_SOUTH,
                                        PanDirection::HORIZONTAL_PAN_WEST,
                                        new Bbox(-3, 48.5, -1, 50.5),
                                        NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }   

   public function testPanLocationRequest3($direct = false) {

        $bbox = new Bbox(-3, 52, -1, 54);
        $this->doTestPanLocationRequest($bbox, 
                                        PanDirection::VERTICAL_PAN_NORTH,
                                        PanDirection::HORIZONTAL_PAN_WEST,
                                        $bbox,
                                        NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }   

    /* TODO: add more tests for pan */


    private function doTestRecenterLocationRequest($idSelections, $expectedBbox,
                                                  $expectedScale = -1, $direct) {
        $recenterLocationRequest = new RecenterLocationRequest();
        $recenterLocationRequest->idSelections = $idSelections;

        return $this->doTestLocationRequest(LocationRequest::LOC_REQ_RECENTER,
                    'recenterLocationRequest', $recenterLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }    
    
    public function testRecenterLocationRequest1($direct = false) {
        
        $idSelection = new IdSelection();
        $idSelection->layerId = 'some_rectangles'; 
        $idSelection->selectedIds = array('onë', 'twò');
        $idSelections[] = $idSelection;
        $_ = $this->doTestRecenterLocationRequest($idSelections,
                                         new Bbox(0.320234435375, 51.5430527819,
                                                  0.645803592079, 51.8686219386),
                                      NULL, $direct);        
        $this->redoDirect($direct, __METHOD__);
    }

    public function testRecenterLocationRequest_BugOrderShouldNotMatter($direct = false) {
        
        $idSelection = new IdSelection();
        $idSelection->layerId = 'grid_defaulthilight'; 

        $idSelection->selectedIds = array('10', '11');
        $mapResult = $this->doTestRecenterLocationRequest(array($idSelection), NULL, 
                                             NULL, $direct);        
        $bbox1 = $mapResult->locationResult->bbox;

        $idSelection->selectedIds = array('11', '10');
        $mapResult = $this->doTestRecenterLocationRequest(array($idSelection), NULL, 
                                             NULL, $direct);        
        
        $bbox2 = $mapResult->locationResult->bbox;
        
        $this->assertSameBbox($bbox1, $bbox2);
        $this->redoDirect($direct, __METHOD__);
    }
    
    public function testRecenterLocationRequest2($direct = false) {
        
        $idSelection = new IdSelection();
        $idSelection->layerId = 'point'; 
        $idSelection->selectedIds = array('1');
        $idSelections[] = $idSelection;
        $this->doTestRecenterLocationRequest($idSelections, 
                                  /* old values, changed because of difference in php5/mapserver5
                                   new Bbox(-0.705555174556, 50.7716668254, 
                                            0.705555174556, 52.1827771746),
                                  */
                                   new Bbox(-0.702027398683, 50.7751946013, 
                                            0.702027398683, 52.1792493987),
                                   NULL, $direct);        
        $this->redoDirect($direct, __METHOD__);
    }
    
    public function testLocationRequestConstraint($direct = false) {

        $bbox =  new Bbox(-1, 50, 1, 52);
        $maxBbox = new Bbox(-1, 51, 1, 52);
    
        $bboxLocationRequest = new BboxLocationRequest();
        $bboxLocationRequest->bbox = $bbox;

        $bboxLocationRequest->constraint = new LocationConstraint();
        $bboxLocationRequest->constraint->maxBbox = $maxBbox;
        
        $mapResult = $this->doTestLocationRequest(LocationRequest::LOC_REQ_BBOX,
                    'bboxLocationRequest', $bboxLocationRequest,
                    NULL, NULL, $direct);
                    
        $this->assertInsideBbox($maxBbox, $mapResult->locationResult->bbox);
        $this->redoDirect($direct, __METHOD__);
    }    
}
?>