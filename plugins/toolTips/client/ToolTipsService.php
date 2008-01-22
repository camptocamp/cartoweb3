<?php
/**
 * Service script for ToolTips plugin
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
 * @package Plugins
 * @version $Id$
 */

// TODO: rename queryableLayer -> asyncTooltipLayer 

require_once('QueryableLayer.php');
require_once('LayerResult.php');

/**
 * ToolTips AJAX Service
 * @package Plugins
 */
class ToolTipsService {

    /**
     * Queryable layers and their returned attributes
     * @var Smarty_Plugin Smarty_Plugin instance for toolTips
     */    
    protected $smarty;    

    /**
     * Queryable layers and their returned attributes
     * @var array array of QueryableLayer
     */    
    protected $queryableLayers = array();    

    /**
     * Results for queried layers
     * @var array array of LayerResult
     */    
    protected $layerResults = array();

    /**
     * X coordinate for the query (geographic)
     * @var float 
     */    
    protected $x;

    /**
     * Y coordinate for the query (geographic)
     * @var float 
     */    
    protected $y;

    /**
     * Current geographic scale (geographic unit per pixel)
     * @var float 
     */    
    protected $mapScale;
    
    /**
     * Last instancied PEAR::DB object; for reusing DB object
     * @var array array of string
     */    
    protected $lastDb;
    
    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(Cartoclient $cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;

        $this->mapScale = $this->getLastScale();
        
        // create all QueryableLayer's from ini file
        $plugins = $this->cartoclient->getPluginManager();
        $iniArray = $plugins->getPlugin('toolTips')->getConfig()->getIniArray();
        $configStruct = StructHandler::loadFromArray($iniArray);

        // gets list of layers
        if (isset($configStruct->tooltips)) {
            $tooltips = $configStruct->tooltips;
            $this->copyLayerGroupsConfig($tooltips);
            $this->addByXyQueryableLayers($tooltips);
        }
    }
    
    /**
     * Copies config for all layers of each layer group
     * @param layerId
     */
    protected function copyLayerGroupsConfig($config) {
        
        $layers = $this->cartoclient->getPluginManager()->getPlugin('layers');
        $selected = $layers->fetchChildrenFromLayerGroup($layers->getSelectedLayers());        
        $layerIds = array_keys(get_object_vars($config));
        $addedLayers = array();

        foreach ($layerIds as $layerId) {
            $layer = $layers->getLayerByName($layerId, false);
            if (!$layer instanceof LayerGroup){
                continue;
            }
            $subLayerIds = $layers->fetchChildrenFromLayerGroup(array($layerId));
            if (count($subLayerIds) > 0) {
                foreach ($subLayerIds as $subLayerId) {
                    if (!array_key_exists($subLayerId, $layerIds) &&
                        !in_array($layerId, $addedLayers) &&
                        in_array($subLayerId, $selected)) {
                        
                        $config->$subLayerId = $config->$layerId;
                        $addedLayers[] = $layerId;
                        
                        // remembers layer group id
                        $config->$subLayerId->layerGroup = $layerId;
                    }
                }
                unset($config->$layerId);
            }
        }
    }
    
    /**
     * Adds ByXyQueryableLayer with a given list of layers.
     * @param stdClass
     */
    protected function addByXyQueryableLayers($layers) {
        foreach ($layers as $layerId => $layer) {
            $id = $layerId;
            if (isset($layer->layerGroup)) {
                $id = $layer->layerGroup;
            }
            $queryableLayer = 
                $this->createQueryableLayer($id, 'ByXyQueryableLayer');
            $this->setQueryableLayerMembers($queryableLayer, $layer, $layerId);
            $this->addQueryableLayer($queryableLayer);
        }
    }

    /**
     * Creates an object of the class matching the given layer Id, if possible.
     * @param string layer Id
     * @param string base QueryableLayer class to extend
     * @return QueryableLayer
     */
    protected function createQueryableLayer($layerId, $queryableLayerClass) {
        // instanciates object with dynamic class
        $extendedPhpClass = ucfirst($layerId) . 'QueryableLayer';
        if (class_exists($extendedPhpClass)) {
            $queryableLayer = new $extendedPhpClass;
            if (!$queryableLayer instanceof $queryableLayerClass) {
                throw new CartoclientException('Argument has to be a ' .
                    "$queryableLayerClass extension: $extendedPhpClass");
            }
        } else {
            $queryableLayer = new $queryableLayerClass;
        }
        return $queryableLayer;
    }

    /**
     * Common settings of ByXyQueryableLayers and ByIdQueryableLayers.
     * @param QueryableLayer target object
     * @param stdClass source object, retrieved from config
     * @param string layerId
     */
    protected function setQueryableLayerMembers(QueryableLayer $queryableLayer,
                                                stdClass $layer, $layerId) {
        $queryableLayer->setId($layerId);
 
        if (empty($layer->label)) {
            $queryableLayer->setLabel($layerId);
        } else {
            $queryableLayer->setLabel($layer->label);
        }
 
        if (!empty($layer->dsn)) {
            $queryableLayer->setDsn($layer->dsn);
        }
 
        if (empty($layer->dbTableName)) {
            if (!empty($layer->dsn)) {
                // Only throw an error is dsn is not null
                throw new CartoclientException(
                    'DB table name is not set for layer id: ' . $layerId
                );
            }
        } else {
            $queryableLayer->setDbTableName($layer->dbTableName);
        }
 
        if (!empty($layer->geomColName)) {
            $queryableLayer->setDbGeomColumnName($layer->geomColName);
        }
        
        if (!empty($layer->srid)) {
            $queryableLayer->setSrid($layer->srid);
        }

        if (!empty($layer->template)) {
            $queryableLayer->setTemplate($layer->template);
        }
        
        if (!empty($layer->tolerance)) {
            $queryableLayer->setTolerance($layer->tolerance);
        }

        if (!empty($layer->encoderName)) {
            $queryableLayer->setEncoderName($layer->encoderName);
        }
 
        if (empty($layer->attributes)) {
            throw new CartoclientException('No attributes are set for layer id: '
                                           . $layerId);
        } else {
            $queryableLayer->setReturnedAttributes($layer->attributes);
        }
    }
    
    /**
     * Returns session-saved last MapRequest.
     * @return MapRequest
     */
    protected function getLastMapRequest() {
        $mapRequest = StructHandler::deepClone($this->cartoclient
                                                    ->getClientSession()
                                                    ->lastMapRequest);

        if (!$mapRequest) {
            throw new CartoclientException('Session expired: reload'
                                           . ' calling page!');
        }

        return $mapRequest;
    }

    /**
     * Returns session-saved last MapResult.
     * @return MapResult
     */
    protected function getLastMapResult() {
        $mapResult = StructHandler::deepClone($this->cartoclient->
                                              getClientSession()->
                                              lastMapResult);

        if (!$mapResult)
            return NULL;

        return $mapResult;
    }

    /**
     * @return float scale from last session-saved MapResult.
     */
    protected function getLastScale() {
        if (!isset($this->mapScale)) {
            $mapResult = $this->getLastMapResult();

            if (is_null($mapResult))
                return 0;
    
            $this->mapScale = $mapResult->locationResult->scale;
        }
        return $this->mapScale;
    }
    
    /**
     * Returns the Smarty template object used for template rendering. It may be
     * used by plugins if they want to override the template display.
     * 
     * @return Smarty_Cartoclient
     */
    public function getSmarty() {
        if (is_null($this->smarty)) {
            $pluginManager = $this->cartoclient->getPluginManager();        
            $clientToolTipsPlugin = $pluginManager->getPlugin('toolTips');
            $this->smarty = new Smarty_Plugin($this->cartoclient,
                                              $clientToolTipsPlugin);
        }
        
        return $this->smarty;
    }

    /**
     * Runs the query method and renders the results 
     */
    public function run() {
        $this->queryLayers();
        $this->renderResults();
    }
    
    /**
     * Returns a PEAR::DB connection relative to the given layer properties.
     * @param string layerId Id of the layer whose DSN is to be returned
     * @return PEAR::DB DB connection for the given layerId
     */
    protected function getDb($layerId) {
        $queryableLayer = $this->getQueryableLayer($layerId);
        Utils::getDb($db, $queryableLayer->getDsn());
        return $db;
    }

    /**
     * Adds the given QueryableLayer to the toolTipsService queryableLayers array
     * @param QueryableLayer
     */
    protected function addQueryableLayer(QueryableLayer $queryableLayer) {
        $this->queryableLayers[$queryableLayer->getId()] = $queryableLayer;
    }

    /**
     * Gives the list of queryableLayers
     * @param string
     * @return array
     */
    protected function getQueryableLayer($layerId) {
        // TODO: checks
        return $this->queryableLayers[$layerId];
    }
    
    /**
     * @param LayerResult
     */
    protected function addLayerResult($layerResult) {
        if ($layerResult instanceof LayerResult) {
            throw new CartoclientException(
                'Argument has to be a LayerResult instance');
        }
        $this->layerResults[] = $layerResult;
    }

    /**
     * @param array array of LayerResult
     */
    protected function addLayerResults($layerResults) {
        if (is_array($layerResults)) {
            foreach ($layerResults as $layerResult) {
                $this->layerResults[] = $layerResult;
            }
        } else {
            $this->addLayerResult($layerResults);
        }        
    }

    /**
     * Returns the results related to the given layer.
     * @param string layerId
     * @return LayerResult
     */
    protected function getLayerResult($layerId) {
        // TODO: checks
        return $this->layerResults[$layerId];
    }
    
    /**
     * Retrieves the list of selected layers.
     * @return array
     */
    public function getSelectedLayers() {
        $lastMapRequest = $this->getLastMapRequest();
        return $lastMapRequest->layersRequest->layerIds;
    }
    
    /**
      * Queries all selected layers.
      */   
    protected function queryLayers() {
        if (isset($_REQUEST['geoX']) && isset($_REQUEST['geoY'])) {
            $this->queryLayersByXy($_REQUEST['geoX'], $_REQUEST['geoY']);
        } else {
            throw new CartoclientException(
                'There are missing or incorrect parameter(s) in HTTP request');
        }
    }
     
    /**
     * Queries all selected layers with given coordinates
     * @param float X coord
     * @param float Y coord
     */   
    protected function queryLayersByXy($geoX, $geoY) {
        
        $lastDsn = NULL;
        $layersCorePlugin = $this->cartoclient->getPluginManager()->
                                                 getPlugin('layers');

        foreach ($this->getSelectedLayers() as $activeLayerId) {  
            if (array_key_exists($activeLayerId, $this->queryableLayers) &&
                $layersCorePlugin->isLayerVisibleAtScale($activeLayerId, $this->mapScale)) {
                $layerId = $activeLayerId;
                $queryableLayer = $this->getQueryableLayer($layerId);

                // Only queries xy layers
                if (!$queryableLayer instanceof ByXyQueryableLayer) {
                    continue;
                }

                // Assigns a PEAR::DB instance to the current QueryableLayer
                // only if QueryableLayer::db is not NULL
                if ($queryableLayer->getDsn() != NULL) {
                    // Requires a new DB connection only if the current cannot be
                    // reused 
                    if ($lastDsn != $queryableLayer->getDsn()) {
                        $db = $this->getDb($layerId);
                        $lastDsn = $queryableLayer->getDsn();
                    }
                    $queryableLayer->setDb($db);
                }
                
                $plugins = $this->cartoclient->getPluginManager();
                $mainmapDimensions = $plugins->getPlugin('images')->
                    getMainmapDimensions();
                $bbox = $plugins->getPlugin('location')->getLocation();
                
                $layerResults = $queryableLayer->queryLayerByXy($geoX, $geoY,
                    $mainmapDimensions, $bbox);
                    
                $layerResults = $queryableLayer->filterResults($layerResults);         
                $this->addLayerResults($layerResults);
                
            }
        }
    }

    /**
     * Sets the HTML result for each layer.
     */
    protected function renderResults() {
        foreach ($this->layerResults as $layerId => $layerResult) {
            $layerResult->setResultHtml(
                $layerResult->renderResult($this->getSmarty()));
        }
    }

    /**
     * @return array array of layersHtmlResult
     */
    public function getResponse() {
        // TODO : a unique HTML result per layer
        // Makes an array containing each layer HTML result
        $layersHtmlResult = array();
        foreach ($this->layerResults as $layerResult) {
            $layersHtmlResult[] = $layerResult->getResultHtml(); 
        }       
        return $layersHtmlResult;
    }    
}
                
?>
