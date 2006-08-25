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
        // TODO: use InitProvider as in layers plugin
        $plugins = $this->cartoclient->getPluginManager();
        $iniArray = $plugins->getPlugin('toolTips')->getConfig()->getIniArray();
        $configStruct = StructHandler::loadFromArray($iniArray);

        // gets list of timeout_async layers
        if (isset($configStruct->timeout_async)) {
            $timeout_async = $configStruct->timeout_async;
            $this->addByXyQueryableLayers($timeout_async);
        }
        // gets list of area_async layers
        if (isset($configStruct->area_async)) {
            $area_async = $configStruct->area_async;
            $this->addByIdQueryableLayers($area_async);
        }
    }
    
    /**
     * Adds byXyQueryableLayer with a given list of layers
     */
    protected function addByXyQueryableLayers($layers) {
        foreach ($layers as $layerId => $layer) {
            // instanciate object with dynamic class
            if (class_exists(ucfirst($layerId) . "QueryableLayer")) {
                $extendedPhpClass = ucfirst($layerId) . "QueryableLayer";
                $queryableLayer = new $extendedPhpClass; 
                if (!$queryableLayer instanceof ByIdQueryableLayer) {
                    throw new Exception('Argument has to be a ' .
                            'ByXyQueryableLayer extension : ' .
                            $extendedPhpClass);
                }
            } else {
                $queryableLayer = new ByXyQueryableLayer;
            }
            $queryableLayer->setId($layerId);
            
            if (!isset($layer->label)) {
                $queryableLayer->setLabel($layerId);
            } else {
                $queryableLayer->setLabel($layer->label);
            }
            
            if (!isset($layer->dsn)) {
                throw new Exception ("dsn is not set for layer id: $layerId. ");
            } else {
                $queryableLayer->setDsn($layer->dsn);
            }
            
            if (!isset($layer->dbTableName)) {
                throw new Exception ("DB table name is not set for layer id: " .
                        $layerId . ".");
            } else {
                $queryableLayer->setDbTableName($layer->dbTableName);
            }
            
            if (isset($layer->template)) {
                $queryableLayer->setCustomTemplate($layer->template);
            }
            if (!isset($layer->attributes)) {
                throw new Exception ("No attributes are set for layer id: " .
                        $layerId . ". ");
            } else {
                $queryableLayer->addReturnedAttribute($layer->attributes);
            }
            
            $this->addQueryableLayer($queryableLayer);
        }
    }
    
    /**
     * Adds queryableLayers with a given list of layers
     * @param array
     */
    protected function addByIdQueryableLayers($layers) {
        foreach ($layers as $layerId => $layer) {
            
            // instanciate object with dynamic class
            if (class_exists(ucfirst($layerId) . "QueryableLayer")) {
                $extendedPhpClass = ucfirst($layerId) . "QueryableLayer";
                $queryableLayer = new $extendedPhpClass;
                if (!$queryableLayer instanceof ByIdQueryableLayer) {
                    throw new Exception('Argument has to be a ' .
                            'ByIdQueryableLayer extension : ' .
                            $extendedPhpClass);
                }
            } else {
                $queryableLayer = new ByIdQueryableLayer;
            }
            $queryableLayer->setId($layerId);
            
            if (!isset($layer->label)) {
                $queryableLayer->setLabel($layerId);
            } else {
                $queryableLayer->setLabel($layer->label);
            }
            
            if (!isset($layer->dsn)) {
                throw new Exception ("dsn is not set for layer id: $layerId. ");
            } else {
                $queryableLayer->setDsn($layer->dsn);
            }
            
            if (!isset($layer->dbTableName)) {
                throw new Exception ('DB table name is not set for layer id: ' .
                        $layerId . '. ');
            } else {
                $queryableLayer->setDbTableName($layer->dbTableName);
            }
            
            if (isset($layer->template)) {
                $queryableLayer->setCustomTemplate($layer->template);
            }
            if (!isset($layer->attributes)) {
                throw new Exception ('No attributes are set for layer id: ' .
                        $layerId . '. ');
            } else {
                $queryableLayer->addReturnedAttribute($layer->attributes);
            }
            
            if (!isset($layer->idAttributeString)) {
                throw new Exception ('id_attribute_string is not set for ' .
                        "layer id: $layerId. ");
            } else {
                $queryableLayer->setIdAttribute($layer->idAttributeString);
            }
            
            $this->addQueryableLayer($queryableLayer);
        }
    }
    
    /**
     * Returns session-saved last MapRequest.
     * @return MapRequest
     */
    public function getLastMapRequest() {
        $mapRequest = StructHandler::deepClone($this->cartoclient->
                                               getClientSession()->
                                               lastMapRequest);

        if (!$mapRequest) {
            throw new CartoclientException('Session expired: reload' .
                    ' calling page!');
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
     * Returns a PEAR::DB connexion
     * @param string layerId Id of the layer whose DSN is to be returned
     * @return PEAR::DB DB connexion for the given layerId
     */
    protected function getDb($layerId) {
        $queryableLayer = $this->getQueryableLayer($layerId);
        $queryableLayer->checkDsn();
        
        Utils::getDb($db, $queryableLayer->getDsn());
        Utils::checkDbError($db);
        return $db;
    }

    /**
     * Adds the given QueryableLayer to the toolTipsService queryableLayers array
     * @param QueryableLayer
     */
    public function addQueryableLayer($queryableLayer) {
        if (!$queryableLayer instanceof QueryableLayer) {
            throw new Exception('Given argument has to be a QueryableLayer' .
                    ' instance');
        }
        $queryableLayer->checkId();
        $this->queryableLayers[$queryableLayer->getId()] = $queryableLayer;
    }

    /**
     * Gives the list of queryableLayers
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
            throw new Exception('Argument has to be a LayerResult instance');
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

    protected function getLayerResult($layerId) {
        // TODO: checks
        return $this->layerResults[$layerId];
    }
    
    /**
     * Retrieve list of selected layers
     * @return array
     */
    protected function getSelectedLayers() {
        $lastMapRequest = $this->getLastMapRequest();
        
        $selectedLayers = $lastMapRequest->layersRequest->layerIds;
        
        return $selectedLayers;
    }
    
    /**
      * Queries all selected layers
      * @return 
      */   
    protected function queryLayers() {
        if (isset($_REQUEST['geoX']) && isset($_REQUEST['geoY'])) {
            $this->queryLayersByXy($_REQUEST['geoX'], $_REQUEST['geoY']);
        } else if (isset($_REQUEST['layer']) && isset($_REQUEST['id'])) {
            $this->queryLayerById($_REQUEST['layer'], $_REQUEST['id']);
        } else {
            throw new Exception('There are missing or incorrect parameter(s) ' .
                    'in HTTP request');
        }
    }
     
    /**
     * Queries all selected layers with given coordinates
     * @return 
     */   
    protected function queryLayersByXy($geoX, $geoY) {
        
        $lastDsn = NULL;
        
        $layersCorePlugin = $this->cartoclient->getPluginManager()->
                                                 getPlugin('layers');
        $printedLayers = $layersCorePlugin->
            getPrintedLayers($this->getSelectedLayers(), $this->mapScale);
        
        foreach ($this->getSelectedLayers() as $activeLayerId) {
            if (array_key_exists($activeLayerId, $this->queryableLayers) &&
                $layersCorePlugin->isLayerVisibleAtCurrentScale($activeLayerId)) {
                $layerId = $activeLayerId;
                $queryableLayer = $this->getQueryableLayer($layerId);

                // only query xy layers
                if (!$queryableLayer instanceof ByXyQueryableLayer) {
                    continue;
                }
                
                // Requires a new DB connection only if the current cannot be
                // reused 
                if ($lastDsn != $queryableLayer->getDsn()) {
                    $db = $this->getDb($layerId);
                    $lastDsn = $queryableLayer->getDsn();
                }

                $queryableLayer->setDb($db);
                
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
     * Queries given layer with given id
     * @return 
     */   
    protected function queryLayerById($layerId, $id) {
        
        $lastDsn = NULL;
        
        $queryableLayer = $this->getQueryableLayer($layerId);
                
        // Requires a new DB connection only if the current cannot be
        // reused 
        if ($lastDsn != $queryableLayer->getDsn()) {
            $db = $this->getDb($layerId);
            $lastDsn = $queryableLayer->getDsn();
        }

        $queryableLayer->setDb($db);
        
        $layerResults = $queryableLayer->queryLayerById($id);
        $layerResults = $queryableLayer->filterResults($layerResults);
        
        $this->addLayerResults($layerResults);
    }

    /**
     * Sets the Html result for each layer
     */
    protected function renderResults() {
        foreach ($this->layerResults as $layerId => $layerResult) {
            $layerResult->
                setResultHtml($layerResult->renderResult($this->getSmarty()));
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