<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'common/GeographicalAssert.php';

require_once(CARTOCLIENT_HOME . 'client/CartoserverService.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/images/common/Images.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/location/common/Location.php');
require_once(CARTOCOMMON_HOME . 'common/Message.php');

/**
 * Wrapper against CartoserverService, to use it inside tests.
 * 
 * @package Tests
 * @author      Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class client_CartoserverServiceWrapper extends common_GeographicalAssert {

    private $currentMapId = null;

    protected function getMapId() {
        return 'test';
    }

    protected function setMapId($mapId) {
        $this->currentMapId = $mapId;
    }
    
    private function doGetMapId() {
        if (is_null($this->currentMapId)) {
            return $this->getMapId();
        }
        return $this->currentMapId;
    }
    
    protected function tearDown() {
        $this->currentMapId = null;
    }

    private function getCartoserverBaseUrl() {
     
        $ini_array = parse_ini_file(CARTOCLIENT_HOME . 'client_conf/client.ini');
        return $ini_array['cartoserverBaseUrl'];
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
        $mapRequest->mapId = $this->doGetMapId();

        // images request is necessary to have the server do something        
        $mapRequest->imagesRequest = $this->getDefaultImagesRequest();
           
        return $mapRequest;
    }    

    private function getCartoserverService($direct) {

        $config = new stdClass();
        $config->mapId = $this->doGetMapId();
        // FIXME: disable soap cache ?
        $config->noWsdlCache = false;
        $config->cartoserverUseWsdl = true;
        $config->cartoserverBaseUrl = $this->getCartoserverBaseUrl();        
        $config->writablePath = CARTOCLIENT_HOME . '/www-data/';
        $this->assertNotNull($config->cartoserverBaseUrl, 'You need to set cartoserverBaseUrl in client.ini');
        
        if ($direct) {
            $config->cartoserverDirectAccess = true;
        } else {        
            $config->cartoserverDirectAccess = false;
        }
        return new CartoserverService($config);
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
            $message = '';
            if (isset($e->faultstring))
                $message = $e->faultstring;
            else
                $message = $e->getMessage();
            $this->fail("Exception raised: " . $message);
        }
    }

    protected function assertIsMapResult($mapResult) {
     
        //var_dump($mapResult);
        $this->assertNotNull($mapResult);
        $this->assertType('MapResult', $mapResult);
        $this->assertNotNull($mapResult->imagesResult->mainmap->path);
    }

}

?>
