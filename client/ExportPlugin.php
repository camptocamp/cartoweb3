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
     * @var Bbox
     */
    private $bbox;

    /**
     * @var float
     */
    private $scale;

    /**
     * @var Point
     */
    private $point;

    /**
     * @var string
     */
    private $zoomType;

    /**
     * @var string
     */
    private $locationType;

    /**
     * @var Rectangle
     */
    private $printOutline;

    /**
     * @var int
     */
    private $resolution;
       
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

    /**
     * @param Bbox
     */
    function setBbox(Bbox $bbox) {
        $this->bbox = $bbox;
    }

    /**
     * @return Bbox
     */
    function getBbox() {
        return $this->bbox;
    }

    /**
     * @param float
     */
    function setScale($scale) {
        $this->scale = $scale;
    }

    /**
     * @return float
     */
    function getScale() {
        return $this->scale;
    }

    /**
     * @param Point
     */
    function setPoint($point) {
        $this->point = $point;
    }

    /**
     * @return Point
     */
    function getPoint() {
        return $this->point;
    }

    /**
     * @param string
     */
    function setZoomType($zoomType) {
        $this->zoomType = $zoomType;
    }

    /**
     * @return string
     */
    function getZoomType() {
        return $this->zoomType;
    }

    /**
     * @param string
     */
    function setLocationType($type) {
        $this->locationType = $type;
    }

    /**
     * @return string
     */
    function getLocationType() {
        return $this->locationType;
    }

    /**
     * @param Rectangle
     */
    function setPrintOutline($bbox) {
        $this->printOutline = $bbox;
    }

    /**
     * @return Rectangle
     */
    function getPrintOutline() {
        return $this->printOutline;
    }

    /**
     * @param int
     */
    function setResolution($resolution) {
        $this->resolution = $resolution;
    }

    /**
     * @return int
     */
    function getResolution() {
        return $this->resolution;
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
    
    public function __construct() {
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
    public function setFile($filePath, $fileName) {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }
    
    /**
     * Sets contents
     *
     * File and contents shouldn't be set together.
     * @param string output contents
     */
    public function setContents($contents) {
        $this->contents = $contents;
    }

    /**
     * Returns file name if the file exists, null otherwise
     * @return string file name   
     */
    public function getFileName() {
    
        if (is_null($this->fileName) || 
            !file_exists($this->filePath . $this->fileName)) {
            return NULL;
        } else {
            return $this->fileName;
        }
    }
    
    /**
     * Returns contents if it is not null, contents of file otherwise
     * @return string output contents
     */
    public function getContents() {
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
abstract class ExportPlugin extends ClientPlugin
                            implements GuiProvider {

    /**
     * Returns export script path. This assumes the script is called export.php
     * in the htdocs directory of the plugin. Clients should override this if
     * this is not the case.
     *
     * @return string The export script path
     */
    public function getExportScriptPath() {
        $urlProvider = $this->cartoclient->getResourceHandler()->getUrlProvider();
        $project = $this->getCartoclient()->getProjectHandler()->getProjectName();
        return $urlProvider->getHtdocsUrl($this->getName(), $project, 'export.php');
    }
    
    /**
     * Returns session-saved last MapRequest.
     * @return MapRequest
     */
    public function getLastMapRequest() {
        $mapRequest = $this->cartoclient->getClientSession()->lastMapRequest;

        if (!$mapRequest)
            return NULL;

        return $mapRequest;
    }

    /**
     * Returns session-saved last MapResult.
     * @return MapResult
     */
    public function getLastMapResult() {
        $mapResult = $this->cartoclient->getClientSession()->lastMapResult;

        if (!$mapResult)
            return NULL;

        return $mapResult;
    }
    
    /**
     * Retrieves MapResult
     *
     * - Gets last request
     * - Calls plugins to adjust request
     * - Calls server's getMap
     * @param ExportConfiguration configuration
     * @return MapResult server result 
     */
    public function getExportResult($configuration) {

        try {
            // Calls all plugins to modify request
            $mapRequest = $this->getLastMapRequest();
            if (is_null($mapRequest)) {
                return NULL;
            }

            $this->cartoclient->callPluginsImplementing('Exportable', 
                                                        'adjustExportMapRequest',
                                                        $configuration,
                                                        $mapRequest);

            // Calls getMap
            $mapResult = $this->cartoclient->getCartoserverService()->
                                       getMap($mapRequest);

            // Initializes plugins  
            $this->cartoclient->callPluginsImplementing('ServerCaller', 
                                                        'initializeResult',
                                                        $mapResult);                                                 
            return $mapResult;
            
        } catch (Exception $exception) {
            $this->cartoclient->getFormRenderer()->showFailure($exception);
            return NULL;
        }
    }

    /**
     * Renders export
     * @return ExportOutput export result
     */
    abstract function getExport();
}

?>
