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

    // TODO: maybe put this in a geographic assertion super class
    
    private function assertSameBbox(Bbox $bbox1, Bbox $bbox2) {
        $this->assertTrue($bbox1->minx == $bbox2->minx &&
                          $bbox1->miny == $bbox2->miny &&
                          $bbox1->maxx == $bbox2->maxx &&
                          $bbox1->maxy == $bbox2->maxy,
                           "bbox are not the same : " . $bbox1->__toString() . 
                            "  " . $bbox2->__toString());
    }

    function testBboxLocationRequest($direct = false) {
        
        $bboxLocationRequest = new BboxLocationRequest();
        $bbox = new Bbox();
        $bbox->setFromBbox(-1, 50, 1, 52);
        $bboxLocationRequest->bbox = $bbox;
        
        $locationRequest = new LocationRequest();
        $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;
        $locationRequest->bboxLocationRequest = $bboxLocationRequest;

        $mapRequest = $this->createRequest();
        $mapRequest->locationRequest = $locationRequest;
        
        // set a square mainmap
        $mapRequest->imagesRequest->mainmap->width = 200;
        $mapRequest->imagesRequest->mainmap->height = 200;
        
        //var_dump($mapRequest);
        $mapResult = $this->getMap($mapRequest);
        
        $this->assertIsMapResult($mapResult);
        //var_dump($mapResult);
    
        $resultBbox = $mapResult->locationResult->bbox;
        // FIXME: use unserialize mechanism !!!
        $tmp = $resultBbox;
        $resultBbox = new Bbox();
        //die($resultBbox->minx);
        $resultBbox->setFromMsExtent($tmp);
        //die($resultBbox->minx);

        $this->assertSameBbox($resultBbox, $bbox);

        $this->redoDirect($direct, __METHOD__);
    }
    
    /* FIXME: templorary disabled for now
    public function testPanLocationRequest($direct = false) {

        $panLocationRequest = new PanLocationRequest();

        $bbox = new Bbox();
        $bbox->setFromBbox(-1, 50, 1, 52);
        $bboxLocationRequest->bbox = $bbox;

        $panLocationRequest->bbox = $bbox;

        $panDirection = new PanDirection();
        $panDirection->verticalPan = PanDirection::VERTICAL_PAN_NORTH;
        $panDirection->horizontalPan = PanDirection::HORIZONTAL_PAN_WEST;

        $panLocationRequest->panDirection = $panDirection;
        
        $locationRequest = new LocationRequest();
        $locationRequest->locationType = LocationRequest::LOC_REQ_PAN;
        $locationRequest->panLocationRequest = $panLocationRequest;

        $mapRequest = $this->createRequest();
        $mapRequest->locationRequest = $locationRequest;
        
        // set a square mainmap
        $mapRequest->imagesRequest->mainmap->width = 200;
        $mapRequest->imagesRequest->mainmap->height = 200;
        
        //var_dump($mapRequest);
        $mapResult = $this->getMap($mapRequest);
        
        $this->assertIsMapResult($mapResult);
        var_dump($mapResult);
    
        $resultBbox = $mapResult->locationResult->bbox;
        // FIXME: use unserialize mechanism !!!
        $tmp = $resultBbox;
        $resultBbox = new Bbox();
        //die($resultBbox->minx);
        $resultBbox->setFromMsExtent($tmp);
        //die($resultBbox->minx);

        $this->assertSameBbox($resultBbox, $bbox);        

        $this->redoDirect($direct, __METHOD__);
    }
    */

    public function testZoomPointLocationRequest($direct = false) {

        $zoomPointLocationRequest = new ZoomPointLocationRequest();
        $zoomPointLocationRequest->zoomType = ZoomPointLocationRequest::ZOOM_DIRECTION_NONE;

        $bbox = new Bbox();
        $bbox->setFromBbox(-1, 50, 1, 52);
        $zoomPointLocationRequest->bbox = $bbox;
        
        $point = new Point(0, 51);
        $zoomPointLocationRequest->point = $point;
        
        $locationRequest = new LocationRequest();
        $locationRequest->locationType = LocationRequest::LOC_REQ_ZOOM_POINT;
        $locationRequest->zoomPointLocationRequest = $zoomPointLocationRequest;

        $mapRequest = $this->createRequest();
        $mapRequest->locationRequest = $locationRequest;
        
        // set a square mainmap
        $mapRequest->imagesRequest->mainmap->width = 200;
        $mapRequest->imagesRequest->mainmap->height = 200;
        
        //var_dump($mapRequest);
        $mapResult = $this->getMap($mapRequest);
        
        $this->assertIsMapResult($mapResult);
        //var_dump($mapResult);
    
        $resultBbox = $mapResult->locationResult->bbox;
        // FIXME: use unserialize mechanism !!!
        $tmp = $resultBbox;
        $resultBbox = new Bbox();
        //die($resultBbox->minx);
        $resultBbox->setFromMsExtent($tmp);
        //die($resultBbox->minx);

        $this->assertSameBbox($resultBbox, $bbox);

        $this->redoDirect($direct, __METHOD__);
    }

   public function testPanLocationRequest($direct = false) {

        $panLocationRequest = new PanLocationRequest();

        $panDirection = new PanDirection();
        $panDirection->verticalPan = PanDirection::VERTICAL_PAN_NORTH;
        $panDirection->horizontalPan = PanDirection::HORIZONTAL_PAN_EAST;

        $panLocationRequest->panDirection = $panDirection;

        $bbox = new Bbox();
        $bbox->setFromBbox(-1, 50, 1, 52);
        $panLocationRequest->bbox = $bbox;
        
        $locationRequest = new LocationRequest();
        $locationRequest->locationType = LocationRequest::LOC_REQ_PAN;
        $locationRequest->panLocationRequest = $panLocationRequest;

        $mapRequest = $this->createRequest();
        $mapRequest->locationRequest = $locationRequest;
        
        // set a square mainmap
        $mapRequest->imagesRequest->mainmap->width = 200;
        $mapRequest->imagesRequest->mainmap->height = 200;
        
        //var_dump($mapRequest);
        $mapResult = $this->getMap($mapRequest);
        
        $this->assertIsMapResult($mapResult);
        //var_dump($mapResult);
    
        $resultBbox = $mapResult->locationResult->bbox;
        // FIXME: use unserialize mechanism !!!
        $tmp = $resultBbox;
        $resultBbox = new Bbox();
        //die($resultBbox->minx);
        $resultBbox->setFromMsExtent($tmp);
        //die($resultBbox->minx);

        $bbox->setFromBbox(0.5, 51.5, 2.5, 53.5);
        $this->assertSameBbox($resultBbox, $bbox);

        $this->redoDirect($direct, __METHOD__);
    }    

    // TODO: refactor tests and do more tests

}
?>