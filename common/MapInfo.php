<?php
/**
 * Classes used to store server configuration on client
 * @package Common
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * Standard classes
 */
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

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
    public $icon = 'none';
    
    /**
     * External link to be added to label
     * @var string
     */
    public $link;
    
    function unserialize($struct) {
        $this->id    = self::unserializeValue($struct, 'id'); 
        $this->label = self::unserializeValue($struct, 'label');
        $this->link  = self::unserializeValue($struct, 'link');
        $this->minScale = self::unserializeValue($struct, 'minScale', 
                                                 'double');
        $this->maxScale = self::unserializeValue($struct, 'maxScale',
                                                 'double');
        $this->icon  = self::unserializeValue($struct, 'icon');
    }
}

/**
 * Layer with children
 * @package Common
 */
class LayerContainer extends LayerBase {
    
    /**
     * Array of LayerBase
     * @var array
     */
    public $children = array();
    
    function unserialize($struct) {
        parent::unserialize($struct);   
        $this->children = self::unserializeArray($struct, 'children');
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

    function unserialize($struct) {
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
     * Name of attribute for identification
     * @var string
     */
    public $idAttributeString;

    function unserialize($struct) {
        parent::unserialize($struct);
        $this->msLayer           = self::unserializeValue($struct, 'msLayer'); 
        $this->idAttributeString = self::unserializeValue($struct, 
                                       'idAttributeString'); 
    }
}

/**
 * Layer class
 * @package Common
 */
class LayerClass extends LayerBase {

}

/**
 * Position on a map
 * @package Common
 */
class Location extends Serializable {
    
    /**
     * @var Bbox
     */
    public $bbox;
    
    function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * Initial position
 * @package Common
 * @see InitialMapState
 */
class InitialLocation extends Location {

}

/**
 * Layer display state
 * @package Common
 */
class LayerState extends Serializable {

    /**
     * State's ID
     * @var string
     */
    public $id;
    
    /**
     * @var boolean
     */
    public $hidden;

    /**
     * @var boolean
     */
    public $frozen;

    /**
     * @var boolean
     */
    public $selected;

    /**
     * @var boolean
     */
    public $unfolded;

    function unserialize($struct) {
        $this->id       = self::unserializeValue($struct, 'id');
        $this->hidden   = self::unserializeValue($struct, 'hidden', 'boolean');
        $this->frozen   = self::unserializeValue($struct, 'frozen', 'boolean');
        $this->selected = self::unserializeValue($struct, 'selected', 
                                                     'boolean');
        $this->unfolded = self::unserializeValue($struct, 'unfolded', 
                                                     'boolean');        
    }
}

/**
 * Initial display state for a mapfile 
 * @package Common
 */
class InitialMapState extends Serializable {

    /**
     * State's ID
     * @var string
     */
    public $id;
    
    /**
     * Initial position on map
     * @var InitialLocation
     */
    public $location;
    
    /**
     * Array of layers
     * @var array
     */
    public $layers;

    function unserialize($struct) {
        $this->id       = self::unserializeValue($struct, 'id');
        $this->location = self::unserializeObject($struct, 'location', 
                              'InitialLocation');
        $this->layers   = self::unserializeObjectMap($struct, 'layers', 
                              'LayerState'); 
    }
}

/**
 * Main MapInfo class
 * @package Common
 */
class MapInfo extends Serializable {

    /**
     * Timestamp for cache check
     * @var int
     */
    public $timeStamp;
    
    /**
     * @var string
     */
    public $mapLabel;
    
    /**
     * IDs of plugins to be loaded
     * @var array
     */
    public $loadPlugins;
    
    /**
     * If true, legend's icons will be generated
     * @var boolean
     */
    public $autoClassLegend;
    
    /**
     * @var array
     */
    public $layers;
    
    /**
     * @var array
     */
    public $initialMapStates;
    
    /**
     * @var Bbox
     */
    public $extent;
    
    /**
     * @var Location
     */
    public $location;
    
    /**
     * @var GeoDimension
     */
    public $keymapGeoDimension; 

    function unserialize($struct) {
        $this->timeStamp        = self::unserializeValue($struct, 'timeStamp');
        $this->mapLabel         = self::unserializeValue($struct, 'mapLabel');
  
        $this->loadPlugins      = self::unserializeArray($struct, 'loadPlugins');
        $this->autoClassLegend  = self::unserializeValue($struct, 
                                      'autoClassLegend', 'boolean');
  
        // Layers class names are specicified in className attribute
        $this->layers           = self::unserializeObjectMap($struct, 'layers');
        $this->initialMapStates = self::unserializeObjectMap($struct, 
                                      'initialMapStates', 
                                      'InitialMapState');
        $this->extent           = self::unserializeObject($struct, 'extent', 
                                      'Bbox');
        $this->location         = self::unserializeObject($struct, 'location',
                                      'Location');
        $this->keymapGeoDimension = self::unserializeObject($struct, 
                                      'keymapGeoDimension', 'GeoDimension');
        
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -4) == 'Init') {
                $this->$attr = self::unserializeObject($struct, $attr, 
                                   ucfirst($attr));
            }
        }
    }
    
    /**
     * Returns a layer identified by its ID
     * @param string
     * @return LayerBase
     */
    function getLayerById($layerId) {

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
    function getMsLayerById($msMapObj, $layerId) {
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
    function getInitialMapStateById($mapStateId) {

        foreach ($this->initialMapStates as $mapState) {
            if ($mapState->id == $mapStateId)
                return $mapState;
        }
        return NULL;
    }

    /**
     * @return array
     */
    function getLayers() {

        return $this->layers;
    }

    /**
     * Adds a layer as a child of another
     * @param LayerBase
     * @param LayerBase
     */
    function addChildLayerBase($parentLayer, $childLayer) {
        
        $childLayerId = $childLayer->id;

        if (in_array($childLayerId, array_keys($this->layers)))
            throw new CartocommonException('Trying to replace layer ' .
            $childLayerId);

        if (!in_array($childLayerId, $parentLayer->children))
            $parentLayer->children[] = $childLayerId;

        $this->layers[$childLayerId] = $childLayer;
    }
}

?>
