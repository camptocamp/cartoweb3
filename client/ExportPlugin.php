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
    
    /**
     * @var boolean
     */
    private $renderMap;
    
    /**
     * @var boolean
     */
    private $renderKeymap;
    
    /**
     * @var boolean
     */
    private $renderScalebar;
    
    /**
     * @var int
     */
    private $mapHeight;
    
    /**
     * @var int
     */
    private $mapWidth;
       
    /**
     * @param boolean
     */
    function setRenderMap($renderMap) {
        $this->renderMap = $renderMap;
    }
    
    /**
     * @return boolean
     */
    function isRenderMap() {
        return $this->renderMap;
    }

    /**
     * @param boolean
     */
    function setRenderKeymap($renderKeymap) {
        $this->renderKeymap = $renderKeymap;
    }
    
    /**
     * @return boolean
     */
    function isRenderKeymap() {
        return $this->renderKeymap;
    }

    /**
     * @param boolean
     */
    function setRenderScalebar($renderScalebar) {
        $this->renderScalebar = $renderScalebar;
    }
    
    /**
     * @return boolean
     */
    function isRenderScalebar() {
        return $this->renderScalebar;
    }

    /**
     * @param int
     */
    function setMapHeight($mapHeight) {
        $this->mapHeight = $mapHeight;
    }
    
    /**
     * @return int
     */
    function getMapHeight() {
        return $this->mapHeight;
    }

    /**
     * @param int
     */
    function setMapWidth($mapWidth) {
        $this->mapWidth = $mapWidth;
    }
    
    /**
     * @return int
     */
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
    
    /**
     * @var string
     */
    private $filePath;
    
    /**
     * @var string
     */
    private $fileName;
    
    /**
     * @var string
     */
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
     * @param string file path
     * @param string file name
     */
    function setFile($filePath, $fileName) {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }
    
    /**
     * Sets contents
     *
     * File and contents shouldn't be set together.
     * @param string output contents
     */
    function setContents($contents) {
        $this->contents = $contents;
    }

    /**
     * Returns file name if the file exists, null otherwise
     * @return string file name   
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
     * @return string output contents
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
     * @return ExportOutput export result 
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
     * @return ExportConfiguration configuration
     */
    abstract function getConfiguration();

    /**
     * Renders export
     * @param MapRequest current map request
     * @param MapResult MapResult returned by server
     * @return ExportOutput export result
     */
    abstract function export($mapRequest, $mapResult);
}

?>
