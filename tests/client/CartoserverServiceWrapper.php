<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCLIENT_HOME . 'client/CartoserverService.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/images/common/Images.php');

/**
 * Wrapper against CartoserverService, to use it inside tests.
 * 
 * @package Tests
 * @author      Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class client_CartoserverServiceWrapper extends PHPUnit2_Framework_TestCase {

    private function getCartoserverUrl() {
     
        $ini_array = parse_ini_file(CARTOCLIENT_HOME . 'client_conf/client.ini');
        return $ini_array['cartoserverUrl'];
    }

    public function setUp() {

        $config = new stdClass();
        $config->mapId = 'test';
        $config->developerMode = false;
        $config->cartoserverUrl = $this->getCartoserverUrl();        
        $config->writablePath = CARTOCLIENT_HOME . '/www-data/';
        $this->assertNotNull($config->cartoserverUrl, 'You need to set cartoserverUrl in client.ini');
        $config->cartoserverDirectAccess = true;
        
        $this->cartoserverServiceDirect = new CartoserverService($config);        
        $config = clone $config;
        $config->cartoserverDirectAccess = false;
        $this->cartoserverService = new CartoserverService($config);
    }

    function isTestDirect() {
        return true;
    }

    protected function redoDirect($direct, $method) {
        if (!$this->isTestDirect() || $direct)
            return;
        list($class, $method) = explode('::', $method);
        $this->$method(true);
    }

    private function getDefaultImagesRequest() {
     
        $images = new ImagesRequest();

        $scalebar_image = new Image();
        $scalebar_image->isDrawn = false;
        $images->scalebar = $scalebar_image;

        $keymap_image = new Image();
        $keymap_image->isDrawn = false;
        $images->keymap = $keymap_image;

        $mainmap_image = new Image();
        $mainmap_image->isDrawn = true;
        $mainmap_image->width = 400;
        $mainmap_image->height = 200;
        $images->mainmap = $mainmap_image;

        return $images;
    }

    protected function createRequest() {
     
        $mapRequest = new MapRequest();
           
        $mapRequest->mapId = 'test';

        // images request are necessary to have the server do something
        
        $mapRequest->imagesRequest = $this->getDefaultImagesRequest();
           
        return $mapRequest;
    }    

    private function getCartoserverService($direct) {
        return $direct ? $this->cartoserverServiceDirect : 
            $this->cartoserverService;
    }

    protected function getMap(MapRequest $request, $direct = false) {
        return $this->callFunction('getMap', $request, $direct);
    }

    protected function getMapInfo($mapId, $direct = false) {
        return $this->callFunction('getMapInfo', $mapId, $direct);
    }

    // TODO: more than one arguement
    private function callFunction($function, $argument, $direct) {
       try {
            return $this->getCartoserverService($direct)->$function($argument);
        } catch (Exception $e) {
            $this->fail("Exception raised: " . $e->faultstring);
        }
    }

    protected function assertIsMapResult($mapResult) {
     
        //var_dump($mapResult);
        $this->assertNotNull($mapResult);
        $this->assertType('MapResult', $mapResult);
        $this->assertNotNull($mapResult->imagesResult->mainmap->path);
    }

    public function XtestGetMapDirect() {
        $mapRequest = new MapRequest();

        try {
            $mapResult = $this->cartoserverServiceDirect->getMap($mapRequest);
        } catch (Exception $e) {
            //var_dump($e->faultstring);   
            $this->fail("Exception raised: " . $e->faultstring);
        }

        var_dump($mapResult);
    }

    public function XtestGetMap() {
        $mapRequest = new MapRequest();
        try {
            $mapResult = $this->cartoserverService->getMap($mapRequest);
        } catch (Exception $e) {
            var_dump($e->faultstring);   
        }
        var_dump($mapResult);
    }
    
}

?>
