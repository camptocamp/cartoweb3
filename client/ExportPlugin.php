<?php
/**
 * Classes used for export
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
     * @var double
     */
    private $mapAngle;

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
     * @var StyledShape
     */
    private $printOutline;

    /**
     * @var int
     */
    private $resolution;

    /**
     * @var array
     */
    private $layerIds;

    /**
     * @var array
     */
    private $querySelections;
       
    /**
     * @var Bbox
     */
    private $queryBbox;

    /**
     * @var string
     */
    private $switchId;

    /**
     * @var boolean
     */
    private $showRefMarks;

    /**
     * @var string
     */
    private $outputFormat;
    
    /**
     * @param boolean
     */
    public function setRenderMap($renderMap) {
        $this->renderMap = $renderMap;
    }
    
    /**
     * @return boolean
     */
    public function isRenderMap() {
        return $this->renderMap;
    }

    /**
     * @param boolean
     */
    public function setRenderKeymap($renderKeymap) {
        $this->renderKeymap = $renderKeymap;
    }
    
    /**
     * @return boolean
     */
    public function isRenderKeymap() {
        return $this->renderKeymap;
    }

    /**
     * @param boolean
     */
    public function setRenderScalebar($renderScalebar) {
        $this->renderScalebar = $renderScalebar;
    }
    
    /**
     * @return boolean
     */
    public function isRenderScalebar() {
        return $this->renderScalebar;
    }

    /**
     * @param int
     */
    public function setMapHeight($mapHeight) {
        $this->mapHeight = $mapHeight;
    }
    
    /**
     * @return int
     */
    public function getMapHeight() {
        return $this->mapHeight;
    }

    /**
     * @param int
     */
    public function setMapWidth($mapWidth) {
        $this->mapWidth = $mapWidth;
    }
    
    /**
     * @return int
     */
    public function getMapWidth() {
        return $this->mapWidth;
    }

    /**
     * @param double
     */
    public function setMapAngle($mapAngle) {
        $this->mapAngle = $mapAngle;
    }
    
    /**
     * @return double
     */
    public function getMapAngle() {
        return $this->mapAngle;
    }

    /**
     * @param Bbox
     */
    public function setBbox(Bbox $bbox) {
        $this->bbox = $bbox;
    }

    /**
     * @return Bbox
     */
    public function getBbox() {
        return $this->bbox;
    }

    /**
     * @param float
     */
    public function setScale($scale) {
        $this->scale = $scale;
    }

    /**
     * @return float
     */
    public function getScale() {
        return $this->scale;
    }

    /**
     * @param Point
     */
    public function setPoint($point) {
        $this->point = $point;
    }

    /**
     * @return Point
     */
    public function getPoint() {
        return $this->point;
    }

    /**
     * @param string
     */
    public function setZoomType($zoomType) {
        $this->zoomType = $zoomType;
    }

    /**
     * @return string
     */
    public function getZoomType() {
        return $this->zoomType;
    }

    /**
     * @param string
     */
    public function setLocationType($type) {
        $this->locationType = $type;
    }

    /**
     * @return string
     */
    public function getLocationType() {
        return $this->locationType;
    }

    /**
     * @param StyledShape
     */
    public function setPrintOutline($styledOutline) {
        $this->printOutline = $styledOutline;
    }

    /**
     * @return StyledShape
     */
    public function getPrintOutline() {
        return $this->printOutline;
    }

    /**
     * @param int
     */
    public function setResolution($resolution) {
        $this->resolution = $resolution;
    }

    /**
     * @return int
     */
    public function getResolution() {
        return $this->resolution;
    }

    /**
     * @param array
     */
    public function setLayerIds($layerIds) {
        $this->layerIds = $layerIds;
    }

    /**
     * @return array
     */
    public function getLayerIds() {
        return $this->layerIds;
    }

    /**
     * @param array
     */
    public function setQuerySelections($querySelections) {
        $this->querySelections = $querySelections;
    }

    /**
     * @return array
     */
    public function getQuerySelections() {
        return $this->querySelections;
    }

    /**
     * @param Bbox
     */
    public function setQueryBbox($queryBbox) {
        $this->queryBbox = $queryBbox;
    }

    /**
     * @return Bbox
     */
    public function getQueryBbox() {
        return $this->queryBbox;
    }

    /**
     * @param string
     */
    public function setSwitchId($switchId) {
        $this->switchId = $switchId;
    }

    /**
     * @return string
     */
    public function getSwitchId() {
        return $this->switchId;
    }

    /**
     * @param boolean
     */
    public function setShowRefMarks($showRefMarks) {
        $this->showRefMarks = $showRefMarks;
    }
    
    /**
     * @return boolean
     */
    public function getShowRefMarks() {
        return $this->showRefMarks;
    }

    /**
     * @param string
     */
    public function setOutputFormat($outputFormat) {
        $this->outputFormat = $outputFormat;
    }

    /**
     * @return string
     */
    public function getOutputFormat() {
        return $this->outputFormat;
    }
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
    
    /**
     * Constructor
     */
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
     * Returns session-saved last MapRequest.
     * @return MapRequest
     */
    public function getLastMapRequest() {
        $mapRequest = StructHandler::deepClone($this->cartoclient->
                                               getClientSession()->
                                               lastMapRequest);

        if (!$mapRequest) {
            throw new CartoclientException('Session expired: reload calling page!');
        }

        return $mapRequest;
    }

    /**
     * Returns session-saved last MapResult.
     * @return MapResult
     */
    public function getLastMapResult() {
        $mapResult = StructHandler::deepClone($this->cartoclient->
                                              getClientSession()->
                                              lastMapResult);

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
    public function getExportResult(ExportConfiguration $configuration) {

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
        $mapResult = $this->cartoclient->getCartoserverService()
                                       ->getMap($mapRequest);
        
        // Initializes plugins  
        $this->cartoclient->callPluginsImplementing('ServerCaller', 
                                                    'initializeResult',
                                                    $mapResult); 
        return $mapResult;
    }

    /**
     * Returns base export plugin URL.
     * @return string
     */
    protected function getExportUrl() {
        // Export plugins must have names that begin with "export".
        $mode = substr($this->getName(), 6); // 6 = strlen('export')
        $mode = strtolower($mode{0}) . substr($mode, 1);
        return sprintf('%s?mode=%s', $this->cartoclient->getSelfUrl(), $mode);
    }

    /**
     * Renders export
     * @return ExportOutput export result
     */
    abstract protected function getExport();

    /**
     * Outputs exported content.
     * @return string
     */
    abstract public function output();
}
