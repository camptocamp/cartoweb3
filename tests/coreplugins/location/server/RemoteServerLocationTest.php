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

require_once(CARTOCOMMON_HOME . 'coreplugins/location/common/Location.php');
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');

/**
 * Unit test for server location plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class coreplugins_location_server_RemoteServerLocationTest
                    extends client_CartoserverServiceWrapper {

    private $testDirect = true;
    private $defaultMapId = 'test';

    private function pushMapId($mapId) {
        $this->mapId = $mapId;
    }
    
    private function popMapId() {
        if (!isset($this->mapId))
            $this->mapId = $this->defaultMapId;
        $mapId = $this->mapId;
        $this->map =  $this->defaultMapId;
        return $mapId;
    }

    private function doTestLocationRequest($locationType, $locationRequestName,
                        $locationRequest, $expectedBbox, $expectedScale, $direct) {
                
        $requ = new LocationRequest();
        $requ->locationType = $locationType;
        $requ->$locationRequestName = $locationRequest;

        $mapRequest = $this->createRequest();
        $mapRequest->mapId = $this->popMapId();
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
    }
    
    private function doTestBboxLocationRequest($bbox, $expectedBbox, 
                                               $expectedScale = -1, $direct) {

        $bboxLocationRequest = new BboxLocationRequest();
        $bboxLocationRequest->bbox = $bbox;

        $this->doTestLocationRequest(LocationRequest::LOC_REQ_BBOX,
                    'bboxLocationRequest', $bboxLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }
    
    function testBboxLocationRequest1($direct = false) {
        $bbox = new Bbox(-1, 50, 1, 52);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testBboxLocationRequest2($direct = false) {
        $bbox = new Bbox(-.5, 51, .5, 52);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testBboxLocationRequest3($direct = false) {
        $bbox = new Bbox(-.1, 51.4, .1, 51.6);
        $this->doTestBboxLocationRequest($bbox, $bbox, NULL, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testBboxLocationRequest_maxscale($direct = false) {
        $bbox = new Bbox(-2, 49, 2, 53);
        $this->doTestBboxLocationRequest($bbox, NULL, 30.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testBboxLocationRequest_minscale($direct = false) {
        $bbox = new Bbox(-.01, 51.49, .01, 51.51);
        $this->doTestBboxLocationRequest($bbox, NULL, 2.0, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    
    /* Breaks because of limits: add tests for this
    function testBboxLocationRequest4($direct = false) {
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

    public function testZoomPointLocationRequest_zoomIn_continuous($direct = false) {
        $this->pushMapId('test_continuous');
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

    public function testZoomPointLocationRequest_zoomOut_continuous($direct = false) {
        $this->pushMapId('test_continuous');
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


    public function doTestRecenterLocationRequest($idSelections, $expectedBbox,
                                                  $expectedScale = -1, $direct) {
        $recenterLocationRequest = new RecenterLocationRequest();
        $recenterLocationRequest->idSelections = $idSelections;

        $this->doTestLocationRequest(LocationRequest::LOC_REQ_RECENTER,
                    'recenterLocationRequest', $recenterLocationRequest,
                    $expectedBbox, $expectedScale, $direct);
    }    
    
    public function testRecenterLocationRequest1($direct = false) {
        
        $idSelection = new IdSelection();
        $idSelection->layerId = 'some_rectangles'; 
        $idSelection->selectedIds = array('one', 'two');
        $idSelections[] = $idSelection;
        $this->doTestRecenterLocationRequest($idSelections, 
                                      new Bbox(0.300031536463, 51.5847270619,
                                               0.54225213318, 51.8269476586),
                                      NULL, $direct);        
        $this->redoDirect($direct, __METHOD__);
    }

    public function testRecenterLocationRequest2($direct = false) {
        
        $idSelection = new IdSelection();
        $idSelection->layerId = 'point'; 
        $idSelection->selectedIds = array('1');
        $idSelections[] = $idSelection;
        $this->doTestRecenterLocationRequest($idSelections, 
                                   new Bbox(-0.705555174556, 50.7716668254, 
                                            0.705555174556, 52.1827771746),
                                   NULL, $direct);        
        $this->redoDirect($direct, __METHOD__);
    }
}
?>