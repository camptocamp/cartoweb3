<?php
/**
 * @package Client
 * @version $Id$
 */

/**
 * @package Client
 */
class ExportConfiguration {
    
    private $renderMap;
    private $renderKeymap;
    private $renderScalebar;
    private $mapHeight;
    private $mapWidth;
       
    function setRenderMap($renderMap) {
        $this->renderMap = $renderMap;
    }
    
    function isRenderMap() {
        return $this->renderMap;
    }

    function setRenderKeymap($renderKeymap) {
        $this->renderKeymap = $renderKeymap;
    }
    
    function isRenderKeymap() {
        return $this->renderKeymap;
    }

    function setRenderScalebar($renderScalebar) {
        $this->renderScalebar = $renderScalebar;
    }
    
    function isRenderScalebar() {
        return $this->renderScalebar;
    }

    function setMapHeight($mapHeight) {
        $this->mapHeight = $mapHeight;
    }
    
    function getMapHeight() {
        return $this->mapHeight;
    }

    function setMapWidth($mapWidth) {
        $this->mapWidth = $mapWidth;
    }
    
    function getMapWidth() {
        return $this->mapWidth;
    }

    // TODO: Add all configuration variables
}


/**
 * @package Client
 */
class ExportOutput {
    
    private $url;
    private $contents;
    
    function __construct() {
        $this->url = null;
        $this->contents = null;
    }
    
    function setUrl($url) {
        $this->url = $url;
    }
    
    function setContents($contents) {
        $this->contents = $contents;
    }
    
    function getUrl() {
        if (is_null($this->url)) {
            if (!is_null($this->contents)) {
            
                // TODO: Write contents in a file and return url                
            } else {
                throw new CartoclientException('ExportOutput is empty');
            }
        } else {
            return $this->url;
        }
    }
    
    function getContents() {
        if (is_null($this->contents)) {
            if (!is_null($this->url)) {
            
                // TODO: Read file and return contents
            } else {
                throw new CartoclientException('ExportOutput is empty');
            }
        } else {
            return $this->contents;
        }
    }
}

/**
 * @package Client
 */
abstract class ExportPlugin extends ClientPlugin {

    function getExport() {

        // Calls all plugins to modify request
        $mapRequest = $this->cartoclient->getClientSession()->lastMapRequest;
        if (!$mapRequest) {
            return NULL;
        }
        $configuration = $this->getConfiguration();
        $this->cartoclient->callPluginsImplementing('Exportable', 'adjustExportMapRequest',
                                                    $configuration, $mapRequest);

        // Calls getMap
        $mapResult = $this->cartoclient->cartoserverService->getMap($mapRequest);

        // Returns export url or contents
        return $this->export($mapResult);
    }

    abstract function getConfiguration();

    abstract function export($mapResult);
}

?>
