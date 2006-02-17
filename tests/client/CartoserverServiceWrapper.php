<?php
/**
 * CartoserverService wrapper for tests
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
require_once 'common/GeographicalAssert.php';

require_once(CARTOWEB_HOME . 'client/Cartoclient.php');
require_once(CARTOWEB_HOME . 'client/CartoserverService.php');
require_once(CARTOWEB_HOME . 'common/Common.php');
require_once(CARTOWEB_HOME . 'common/Request.php');
require_once(CARTOWEB_HOME . 'coreplugins/images/common/Images.php');
require_once(CARTOWEB_HOME . 'coreplugins/location/common/Location.php');
require_once(CARTOWEB_HOME . 'coreplugins/layers/common/Layers.php');
require_once(CARTOWEB_HOME . 'common/Message.php');
require_once(CARTOWEB_HOME . 'common/MapInfo.php');

/**
 * Wrapper against CartoserverService, to use it inside tests.
 * 
 * @package Tests
 * @author      Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
abstract class client_CartoserverServiceWrapper extends common_GeographicalAssert {

    private  $cartoclient;
    
    abstract protected function getMapId();

    public function dummyErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
    }

    private function getCartoclient($project) {
        if (!$this->cartoclient) {
            $GLOBALS['headless'] = true;
            $_ENV['CW3_PROJECT'] = $project;

            set_error_handler(array($this, 'dummyErrorHandler'));
            $this->cartoclient = new Cartoclient();
            restore_error_handler();
        }
        return $this->cartoclient;
    }
    
    private function getCartoserverBaseUrl() {

        list($project, $mapId) = explode('.', $this->getMapId());
        return $this->getCartoclient($project)->getConfig()->cartoserverBaseUrl;
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
        $mapRequest->mapId = $this->getMapId();

        // images request is necessary to have the server do something        
        $mapRequest->imagesRequest = $this->getDefaultImagesRequest();
           
        return $mapRequest;
    }    

    private function getCartoserverService($direct) {

        $config = new stdClass();
        $config->mapId = $this->getMapId();
        // FIXME: disable soap cache ?
        $config->noWsdlCache = false;
        $config->useWsdl = true;
        $config->cartoserverBaseUrl = $this->getCartoserverBaseUrl();        
        $config->writablePath = CARTOWEB_HOME . '/www-data/';
        $config->accountingOn = false;
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
            if ($direct) {
                $config = new stdClass();
                $config->developerIniConfig = true;
                Common::initializeCartoweb($config);
            }
            $ret = $this->getCartoserverService($direct)->$function($argument);
            if ($direct) {
                Common::shutdownCartoweb($config);
            }            
            return $ret;
        } catch (CartowebException $e) {
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
