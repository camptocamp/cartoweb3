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

require_once(CARTOSERVER_HOME . 'coreplugins/layers/server/LayersInitProvider.php');

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
    private $requestedLayerNames;
    
    /**
     * Image type to use for images (outputformat identifier declared in 
     * mapfile). May be null to use default one.
     * @var string 
     */
    private $imageType;

    /**
     * Ratio client-required resolution / Mapserver resolution.
     * @var float
     */
    private $resRatio;
    
    /**
     * Current switch
     */
    private $switchId;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        // If image type is null, will use mapfile image type
        $this->imageType = null;
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
        
        if (isset($requ->resolution) && $requ->resolution) {
        
            $msMapObj = $this->serverContext->getMapObj();
        
            if ($requ->resolution != $msMapObj->resolution)
                $this->resRatio = $requ->resolution / $msMapObj->resolution;
            else
                $this->resRatio = 1;
        }
    }    

    /**
     * Generic function to multiply integer properties of a
     * php mapscript object of a given ratio.
     * @param mixed php mapscript object to update
     * @param array array of numerical properties to update
     * @param float multiplicative coefficient
     */
    private function updateProperties($obj, $properties, $ratio) {
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

    /**
     * Updates mapfile objects (layers, classes, styles) properties
     * according to the ratio between required resolution and mapserver one.
     * @param ms_layer_obj Mapserver layer object
     * @param float resolutions ratio
     */
    private function updateRatioParameters($layer, $resRatio) {
 
        $invResRatio = 1 / $resRatio;
        
        $label_props = array('size', 'mindistance', 'minfeaturesize', 
                             'minsize', 'maxsize', 'offsetx', 'offsety');

        if ($layer->toleranceunits == MS_PIXELS)
            $this->updateProperties($layer, array('tolerance'), $resRatio);

        $this->updateProperties($layer, array('minscale', 'maxscale'),
                                $invResRatio);
        
        for ($j = 0; $j < $layer->numclasses; $j++) {
            $class = $layer->getclass($j);

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
            
            $this->updateProperties($class, array('minscale', 'maxscale'),
                                    $invResRatio);
            
            $label = $class->label;
            $this->updateProperties($label, $label_props, $resRatio);
        }
    }

    /**
     * @see CoreProvider::handleCorePlugin()
     */
    public function handleCorePlugin($requ) {

        $msMapObj = $this->serverContext->getMapObj();

        $layerIds = $requ->layerIds;
        $this->requestedLayerNames = $layerIds;
        
        if (!is_array($layerIds)) {
            throw new CartoclientException('Invalid layer request: ' .
                                           'layerIds not array');
            return;
        }

        $this->log->debug('layers to draw: ');
        $this->log->debug($layerIds);
      
        // disable all layers
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $msLayer = $msMapObj->getLayer($i);
            $msLayer->set('status', MS_OFF);
        }
        
        foreach ($this->getRequestedLayerNames() as $requLayerId) {
            $this->log->debug("testing id $requLayerId");
            
            $msLayer = $this->serverContext->getMapInfo()->layersInit->
                                    getMsLayerById($msMapObj, $requLayerId);
            $msLayer->set('status', MS_ON);

            if ($this->resRatio && $this->resRatio != 1)
                $this->updateRatioParameters($msLayer, $this->resRatio);
            
            $forceImageType = $msLayer->getMetaData('force_imagetype');
            if (!empty($forceImageType)) {
                $this->imageType = $forceImageType;
            }
        }
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
?>
