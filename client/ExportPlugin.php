<?php
/**
 * Classes used for export
 * @package Client
 * @version $Id$
 */

/**
 * Export configuration
 *
 * Export configuration objects are passed to plugins so they know what is 
 * needed for export. For instance, plugin Images should know which maps must
 * be rendered. 
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
 * Output of an export
 * 
 * Output can be either a file or a string which contains output content.
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
    
    /**
     * Sets file name and path
     *
     * File and contents shouldn't be set together.
     */
    function setFile($filePath, $fileName) {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }
    
    /**
     * Sets contents
     *
     * File and contents shouldn't be set together.
     */
    function setContents($contents) {
        $this->contents = $contents;
    }

    /**
     * Returns file name if the file exists, null otherwise   
     */
    function getFileName() {
    
        if (is_null($this->fileName) || !file_exists($this->filePath . $this->fileName)) {
            return NULL;
        } else {
            return $this->fileName;
        }
    }
    
    /**
     * Returns contents if it is not null, contents of file otherwise
     */
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
 * Export plugin
 * @package Client
 */
abstract class ExportPlugin extends ClientPlugin {

    /**
     * Retrieves MapResult and exports
     *
     * - Gets last request
     * - Calls plugins to adjust request
     * - Calls server's getMap
     * - Renders export output by calling child object's export() 
     */
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
            return $this->export($mapRequest, $mapResult);

        } catch (Exception $exception) {
            
            $this->cartoclient->formRenderer->showFailure($exception);
            return new ExportOutput();
        }
    }

    /** 
     * Returns export configuration
     */
    abstract function getConfiguration();

    /**
     * Renders export
     */
    abstract function export($mapRequest, $mapResult);
}

?>
