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
    
    private $filePath;
    private $fileName;
    private $contents;
    
    function __construct() {
        $this->filePath = null;
        $this->fileName = null;
        $this->contents = null;
    }
    
    function setFile($filePath, $fileName) {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }
    
    function setContents($contents) {
        $this->contents = $contents;
    }
    
    function getFileName() {
    
        if (is_null($this->fileName) || !file_exists($this->filePath . $this->fileName)) {
            return NULL;
        } else {
            return $this->fileName;
        }
    }
    
    function getContents() {
        if (is_null($this->contents)) {
            if (!is_null($this->fileName)) {
            
                return file_get_contents($this->filePath . $this->fileName);
            } else {
                return NULL;
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

        try {
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

        } catch (Exception $exception) {
            
            $this->cartoclient->formRenderer->showFailure($exception);
            return new ExportOutput();
        }
    }

    abstract function getConfiguration();

    abstract function export($mapResult);
}

?>
