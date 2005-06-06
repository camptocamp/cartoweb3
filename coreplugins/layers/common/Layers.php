<?php
/**
 * Layers plugin common classes
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
 
/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * Base class for layers
 * @package Common
 */
class LayerBase extends Serializable {
    
    /**
     * @var string
     */
    public $id;
    
    /**
     * @var string
     */
    public $label;
    
    /**
     * Minimum scale where layer is visible
     * @var int
     */
    public $minScale = 0;
    
    /**
     * Maximum scale where layer is visible
     * @var int
     */
    public $maxScale = 0;
    
    /**
     * Icon to display for layer
     * @var string
     */
    public $icon = '';
    
    /**
     * External link to be added to label
     * @var string
     */
    public $link;
    
    /**
     * A map of metadata values, in the form "key=value"
     * @var array
     */
    public $metadata = array();
    
    /**
     * A map for metadata, it is lazyly constructed from $metadata when
     * requested.
     * @var array
     */
    private $metaHash;
    
    /**
     * Returns a metadata valued from its key, or null if it does not exists.
     * @param string metadata key
     * @return string the value, or null if not there.
     */
    public function getMetadata($key) {
        if (is_null($this->metaHash)) {
            $this->metaHash = array();
            foreach ($this->metadata as $meta) {
                list($k, $val) = explode('=', $meta);
                $this->metaHash[$k] = $val;
            }
        }
        if (!isset($this->metaHash[$key]))
            return null;
        return $this->metaHash[$key];
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id    = self::unserializeValue($struct, 'id'); 
        $this->label = self::unserializeValue($struct, 'label');
        $this->link  = self::unserializeValue($struct, 'link');
        $this->minScale = self::unserializeValue($struct, 'minScale', 
                                                 'double');
        $this->maxScale = self::unserializeValue($struct, 'maxScale',
                                                 'double');
        $this->icon  = self::unserializeValue($struct, 'icon');
        $this->metadata = self::unserializeArray($struct, 'metadata');
    }
}

/**
 * Class containing children for children switching
 * @package Common
 */
class ChildrenSwitch extends Serializable {

    const DEFAULT_SWITCH = 'default';

    /**
     * Switch's Id
     * @var string
     */
    public $id = '';

    /**
     * Array of LayerBase ids
     * @var array
     */
    public $layers = array();
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        if (is_string($struct)) {
            $this->layers = self::unserializeArray($struct);
        } else if (!empty($struct)) {
            $this->id = $struct->id;
            $this->layers = self::unserializeArray($struct, 'layers');
        }
    }
}

/**
 * Layer with children
 * @package Common
 */
class LayerContainer extends LayerBase {
    
    /**
     * Key-values list of ChildrenSwitch
     * @var array
     */
    public $children = array();
    
    /**    
     * Layer Ids cache
     * @var array
     */
    private $layerIds = NULL;
    
    /**
     * Returns children depending on current switch
     * @param string
     * @return array
     */
    public function getChildren($currentSwitch) {

        if (is_null($this->layerIds)) {
            if (!is_array($this->children) || count($this->children) == 0) {
                $this->layerIds = array();
                return $this->layerIds;
            }        
            $switch = NULL;
            if (count($this->children) == 1) {
                $children = array_values($this->children);
                $switch = $children[0]; 
            } else if (isset($this->children[$currentSwitch])) {
                $switch = $this->children[$currentSwitch];
            } else if (isset($this->children['default'])) {
                $switch = $this->children['default'];
            } else {
                $this->layerIds = array();
                return $this->layerIds;
            }
            if (!($switch instanceof ChildrenSwitch)) {
                $this->layerIds = array();
                return $this->layerIds;
            }
            if (is_null($switch->layers)) {
                $this->layerIds = array();
            } else {
                $this->layerIds = $switch->layers;
            }            
        }
        return $this->layerIds;        
    }
    
    /**
     * Sets children given an array of layerIds
     * @param array
     */ 
    public function setChildren($layerIds) {
         
        $this->layerIds = $layerIds;
        $childrenSwitch = new ChildrenSwitch();
        $childrenSwitch->layers = $layerIds;
        $this->children = array($childrenSwitch);
    }
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        parent::unserialize($struct);   
      
        if (isset($struct->children)) {
            if (is_string($struct->children)) {
                // Backward compatibility
                $childrenSwitch = new ChildrenSwitch();
                $childrenSwitch->id = "default";
                $childrenSwitch->layers = self::unserializeArray($struct,
                                                                 'children');
                $this->children = array("default" => $childrenSwitch);
            } else {
                $this->children = self::unserializeObjectMap($struct, 'children',
                                                             'ChildrenSwitch');
                // Needed because array keys are not kept through SOAP
                $children = array();
                foreach($this->children as $childId => $child) {
                    if (empty($child->id)) {
                        $child->id = $childId;
                    }
                    $children[$child->id] = $child;
                }                                  
                $this->children = $children;                           
            }
        }
        // FIXME: do it in unserializeArray ?
        if (is_null($this->children))
            $this->children = array();
    }    
}

/**
 * Layer node
 * @package Common
 */
class LayerGroup extends LayerContainer {
    
    /**
     * If true, children won't be displayed
     * @var boolean
     */
    public $aggregate = false;
    
    /**
     * Type of rendering for children (tree, dropdown, radio)
     * @var string
     */
    public $rendering = 'tree';

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        parent::unserialize($struct);
        $this->aggregate = self::unserializeValue($struct, 'aggregate',
                                                  'boolean');
        $this->rendering = self::unserializeValue($struct, 'rendering');
    }
}

/**
 * Layer linked to a MapServer layer
 * @package Common
 */
class Layer extends LayerContainer {

    /**
     * MapServer layer
     * @var string
     */
    public $msLayer;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        parent::unserialize($struct);
        $this->msLayer           = self::unserializeValue($struct, 'msLayer'); 
    }
}

/**
 * Layer class
 * @package Common
 */
class LayerClass extends LayerBase {

}

/**
 * A request for layers.
 *
 * @package CorePlugins
 */
class LayersRequest extends Serializable {
    
    /**
     * The list of layers to draw
     * @var array
     */
    public $layerIds;

    /**
     * Resolution used to draw the images. Another good place for this would
     * have been in ImagesRequest.
     * @var int
     */
    public $resolution;

    /**
     * Current switch
     */
    public $switchId;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layerIds   = self::unserializeArray($struct, 'layerIds');
        $this->resolution = self::unserializeValue($struct, 'resolution',
                                                   'int');
        $this->switchId   = self::unserializeValue($struct, 'switchId');
    }
}

/**
 * Result of a layers request. It is empty.
 *
 * @package CorePlugins
 */
class LayersResult {}

/**
 * Switch information
 */
class SwitchInit extends Serializable {
    
    /**
     * Switch's Id
     * @var string
     */
    public $id;
    
    /**
     * Switch's label
     */
    public $label;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id    = self::unserializeValue($struct, 'id');
        $this->label = self::unserializeValue($struct, 'label');
    }
}

/**
 * Layers initialization information. It contains all the layer related static 
 * information. 
 */
class LayersInit extends Serializable {

    /**
     * If true, legend's icons will be generated
     * @var boolean
     */
    public $autoClassLegend;
    
    /**
     * Icon to show when layer not available (current scale is above or below 
     * this layer max scale)
     * @var string
     */
    public $notAvailableIcon;
    
    /**
     * Icon to show when layer not available (current scale is above this 
     * layer max scale)
     * @var string
     */
    public $notAvailablePlusIcon;

    /**
     * Icon to show when layer not available (current scale is below this 
     * layer max scale)
     * @var string
     */
    public $notAvailableMinusIcon;

    /**
     * Array of all available layers.
     * @var array
     */
    public $layers;

    /**
     * Array of switches
     * @var array
     */
    public $switches;

    /**
     * Returns a layer identified by its ID
     * @param string
     * @return LayerBase
     */
    public function getLayerById($layerId) {

        foreach ($this->layers as $layer) {
            if ($layer->id == $layerId)
                return $layer;
        }
        return NULL;
    }

    /**
     * Helper function to get a mapserver layer from a layerId
     * @param MsMapObj MapServer object
     * @param string
     * @return MsLayer MapServer layer object
     */
    public function getMsLayerById($msMapObj, $layerId) {
        $layer = $this->getLayerById($layerId);
        if (is_null($layer))
            throw new CartocommonException("can't find layer $layerId");
        $msLayer = @$msMapObj->getLayerByName($layer->msLayer);
        if (is_null($msLayer))
            throw new CartocommonException("can't open msLayer $layer->msLayer");
        return $msLayer;
    }

    /**
     * Returns a map state identified by its ID
     * @param string
     * @return InitialMapState
     */
    public function getInitialMapStateById($mapStateId) {

        foreach ($this->initialMapStates as $mapState) {
            if ($mapState->id == $mapStateId)
                return $mapState;
        }
        return NULL;
    }

    /**
     * Returns the array of all available layers
     * @return array
     */
    public function getLayers() {
        return $this->layers;
    }

    /**
     * Adds a layer as a child of another
     * @param LayerBase The parent layer where to add this layer, or NULL if no parent
     * @param LayerBase The child layer to be added.
     */
    public function addChildLayerBase($parentLayer, $childLayer) {
        
        $childLayerId = $childLayer->id;

        if (in_array($childLayerId, array_keys($this->layers)))
            throw new CartocommonException('Trying to replace existing layer ' .
            $childLayerId);

        if (!is_null($parentLayer) && !in_array($childLayerId, 
                                                $parentLayer->children))
            $parentLayer->children[] = $childLayerId;

        $this->layers[$childLayerId] = $childLayer;
    }
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {

        $this->autoClassLegend  = self::unserializeValue($struct, 
                                      'autoClassLegend', 'boolean');
  
        $this->notAvailableIcon = self::unserializeValue($struct, 
                                                    'notAvailableIcon');
        $this->notAvailablePlusIcon = self::unserializeValue($struct, 
                                                    'notAvailablePlusIcon');
        $this->notAvailableMinusIcon = self::unserializeValue($struct, 
                                                    'notAvailableMinusIcon');
        
        // Layers class names are specicified in className attribute
        $this->layers = self::unserializeObjectMap($struct, 'layers');
        
        $this->switches = self::unserializeObjectMap($struct, 'switches',
                                                     'SwitchInit');
    }    
}

?>
