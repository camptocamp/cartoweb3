<?php
/**
 * Server side layers plugin
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
 * @package CorePlugins
 * @version $Id$
 */

require_once(CARTOWEB_HOME . 'coreplugins/layers/server/LayersInitProvider.php');

/**
 * Server plugin for managing the set of layers to render.
 * @package CorePlugins
 */
class ServerLayers extends ClientResponderAdapter
                   implements CoreProvider, InitProvider {
    /**
     * @var Logger
     */
    private $log;

    /**
     * The list of layers requested to be drawn by the client.
     * @var array Array of string
     */
    protected $requestedLayerNames;
    
    /**
     * Image type to use for images (outputformat identifier declared in 
     * mapfile). May be null to use default one.
     * @var string 
     */
    protected $imageType;

    /**
     * Ratio client-required resolution / Mapserver resolution.
     * @var float
     */
    protected $resRatio;
    
    /**
     * Current switch
     */
    protected $switchId;
    
    /**
     * User added layers
     * @var array
     */
    public $userLayers;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log = LoggerManager::getLogger(__CLASS__);
        
        // If image type is null, will use mapfile image type
        $this->imageType = null;
    }
   
    /**
     * Adds user layers
     * @var array of UserLayer
     */
    public function addUserLayers($userLayers) {        
        $msMapObj = $this->serverContext->getMapObj();
        foreach ($userLayers as $key => &$userLayer) {
            if ($userLayer instanceof Layer) {
                $newUserLayer = new UserLayer();
                $newUserLayer->action = UserLayer::ACTION_INSERT;
                $newUserLayer->layer = $userLayer;
                $userLayer = $newUserLayer;
            }
            if ($userLayer instanceof UserLayer) {
                $layer   =& $userLayer->layer;
                $msLayer = $msMapObj->getLayerByName($layer->id);
                //update layer msLayer & label
                $layer->msLayer = $layer->id;                
                if (count($msLayer) && !count($layer->label)) {
                    $layer->label = $msLayer->getMetadata('wms_title');
                }
                if (!count($layer->label)) {
                    $layer->label = $layer->id;
                }                    
                $this->userLayers[] = $userLayer;            
            }
        }
    }

   
    /**
     * Removes user layers
     * @var array of UserLayer
     */
    public function removeUserLayers($userLayers) {
        foreach ($userLayers as $key => $userLayer) {
            if ($userLayer instanceof Layer) {
                $newUserLayer = new UserLayer();
                $newUserLayer->action = UserLayer::ACTION_REMOVE;
                $newUserLayer->layer = $userLayer;
                $userLayer = $newUserLayer;
            }
            if (!($userLayer instanceof UserLayer)) {
                throw new CartoserverException('You can only remove a ' .
                                                     'UserLayer or Layer type');
            } 
            if ($userLayer->action == UserLayer::ACTION_REMOVE) {
                $this->userLayers[] = $userLayer;
            } else {
                throw new CartoserverException('Please use ' .
                        'ServerLayer::addUserLayers() to add a new UserLayer');
            }
        }
    }
            
    /**
     * Returns the list of layers requested to be drawn by the client.
     * @return array
     */
    public function getRequestedLayerNames() {
        if(!$this->requestedLayerNames) return array();
        return $this->requestedLayerNames;
    }

    /**
     * Returns the image type to use for drawing, or null to use mapfile one. 
     * @return string
     */
    public function getImageType() {
        return $this->imageType;
    }

    /**
     * @return float ratio client-required resolution / Mapserver resolution.
     */
    public function getResRatio() {
        return $this->resRatio;
    }
    
    /**
     * Returns current switch
     * @return string
     */
    public function getSwitchId() {
        return $this->switchId;
    }
    
    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) {
    
        // Warning : this works only because plugin layers is the first one
        // which calls ServerContext's getMapObj() 
        if (empty($requ->switchId)) {
            $this->switchId = ChildrenSwitch::DEFAULT_SWITCH;
        } else {
            $this->switchId = $requ->switchId;
        }
        
        $this->resRatio = 1;
        if (isset($requ->resolution) && $requ->resolution) {
        
            $msMapObj = $this->serverContext->getMapObj();
        
            if ($requ->resolution != $msMapObj->resolution) {
                $this->resRatio = $requ->resolution / $msMapObj->resolution;
            }
        }

        $layerIds = $requ->layerIds;
        if (!is_array($layerIds)) {
            throw new CartoserverException('Invalid layer request: ' .
                                           'layerIds is not an array.');
            return;
        }

        $this->account('server_version', 1);
        $this->account('layers', implode(',', $layerIds));
        $this->account('switch_id', $this->switchId);

        $this->requestedLayerNames = $layerIds;

        $this->log->debug('layers to draw: ');
        $this->log->debug($layerIds);
    }    

    /**
     * Generic function to multiply integer properties of a
     * php mapscript object of a given ratio.
     * @param mixed php mapscript object to update
     * @param array array of numerical properties to update
     * @param float multiplicative coefficient
     */
    protected function updateProperties($obj, $properties, $ratio) {
        foreach($properties as $p) {
            $value = $obj->$p;

            // special case for bitmapped labels size
            if ($p == 'size' && get_class($obj) == 'ms_label_obj' && 
                $value <= 4) { 
                $obj->set($p, 4); 
                continue;
            }           

            $obj->set($p, $value * $ratio);
        }
    }

    public function updateRatioClassParameters($class, $resRatio) {

        $label_props = array('size', 'mindistance', 'minfeaturesize', 
                             'minsize', 'maxsize', 'offsetx', 'offsety');
        $invResRatio = 1 / $resRatio;

        for ($k = 0; $k < $class->numstyles; $k++) {
            $style = $class->getStyle($k);

            // style sizes not totally resized by the ratio factor,
            // to improve readability:
            $styleRatio = $resRatio * 1.0;
            $this->updateProperties($style, 
                                    array('size','offsetx', 'offsety',
                                          'minsize', 'maxsize'),
                                    $styleRatio);
        }
        
        $this->updateProperties($class, array('minscaledenom', 'maxscaledenom'),
                                $invResRatio);
        
        $label = $class->label;
        $this->updateProperties($label, $label_props, $resRatio);
    }

    /**
     * Updates mapfile objects (layers, classes, styles) properties
     * according to the ratio between required resolution and mapserver one.
     * @param ms_layer_obj Mapserver layer object
     * @param float resolutions ratio
     */
    public function updateRatioParameters($layer, $resRatio) {
        if ($layer->getMetaData('ratio_updated') == 'ok') {
            // don't update the same layer
            return;
        }
        $invResRatio = 1 / $resRatio;
        
        if ($layer->toleranceunits == MS_PIXELS && $layer->tolerance > 0) {            
            $this->updateProperties($layer, array('tolerance'), $resRatio);
        }

        $this->updateProperties($layer, array('symbolscaledenom', 'minscaledenom', 'maxscaledenom'),
                                $invResRatio);
        
        for ($j = 0; $j < $layer->numclasses; $j++) {
            
            $class = $layer->getclass($j);
            $this->updateRatioClassParameters($class, $resRatio);
        }
        $layer->setMetaData('ratio_updated', 'ok');
    }

    /**
     * @see CoreProvider::handleCorePlugin()
     */
    public function handleCorePlugin($requ) {

        $msMapObj = $this->serverContext->getMapObj();
      
        // disable all layers
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $msLayer = $msMapObj->getLayer($i);
            $msLayer->set('status', MS_OFF);
        }

        $currentScale = isset($msMapObj->scaledenom) ? $msMapObj->scaledenom : 0;
        
        // manage user layers
        $layersResult = new LayersResult();
        $requestedLayerNames = $this->getRequestedLayerNames();
        if(!is_null($this->userLayers)) {
            $layersInitProvider = new LayersInitProvider(
                $this->getServerContext(), $this);
            foreach ($this->userLayers as $key => $userLayer) {
                if ($userLayer->action == UserLayer::ACTION_INSERT) {
                    $layersInitProvider->fillDynamicLayerBase($userLayer->layer);
                    $layersInitProvider->fillDynamicLayer($userLayer->layer);

                    $requestedLayerNames[] = $userLayer->layer->msLayer;
                    $this->userLayers[$key] = $userLayer;
                } else {
                    $key = array_search($userLayer->layer->id, 
                                        $requestedLayerNames);
                    if ($key !== false) {
                        unset($requestedLayerNames[$key]);
                    }
                }
            }
            $layersResult->userLayers = $this->userLayers;
        }

        $this->userLayers = NULL;
        foreach ($requestedLayerNames as $requLayerId) {
            $this->log->debug("testing id $requLayerId");
            if (in_array($requLayerId, $msMapObj->getAllLayerNames())) {
                $msLayer = $msMapObj->getLayerByName($requLayerId);
            } else {
                $msLayer = $this->serverContext->getMapInfo()->layersInit
                                ->getMsLayerById($msMapObj, $requLayerId);
            }

            if (empty($msLayer)) {
                $this->log->warn("Layer $requLayerId does not exist.");
                continue;
            }
            
            $msLayer->set('status', MS_ON);

            if ($this->resRatio && $this->resRatio != 1)
                $this->updateRatioParameters($msLayer, $this->resRatio);
            
            $forceImageType = $msLayer->getMetaData('force_imagetype');
            if (!empty($forceImageType) && $msLayer->isVisible()) {
                $this->imageType = $forceImageType;
            }
        }
        
        return $layersResult;
    }
    
    /**
     * @see InitProvider::getInit()
     */
    public function getInit() {

        $layersInitProvider = new LayersInitProvider($this->getServerContext(),
                                                     $this);
        return $layersInitProvider->getInit();
    }
}
