<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * Request information for plugin Location
 * @package CorePlugins
 */
class LocationRequest extends Serializable {

    /**
     * LocationType constants
     */
    const LOC_REQ_BBOX = 'bboxLocationRequest';
    const LOC_REQ_PAN = 'panLocationRequest';
    const LOC_REQ_ZOOM_POINT = 'zoomPointLocationRequest';
    const LOC_REQ_RECENTER = 'recenterLocationRequest';

    /**
     * @var string
     */
    public $locationType;

    /**
     * @var BboxLocationRequest
     */
    public $bboxLocationRequest;
    
    /**
     * @var PanLocationRequest
     */
    public $panLocationRequest;
    
    /**
     * @var ZoomPointLocationRequest
     */
    public $zoomPointLocationRequest;
        
    /**
     * @var RecenterLocationRequest
     */
    public $recenterLocationRequest;

    /**
     * @var LocationConstraint
     */
    public $locationConstraint;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->locationType = self::unserializeValue($struct, 'locationType');

        $this->bboxLocationRequest = self::unserializeObject($struct, 
                    'bboxLocationRequest', 'BboxLocationRequest');
        $this->panLocationRequest = self::unserializeObject($struct, 
                    'panLocationRequest', 'PanLocationRequest');
        $this->zoomPointLocationRequest = self::unserializeObject($struct, 
                    'zoomPointLocationRequest', 'ZoomPointLocationRequest');
        $this->recenterLocationRequest = self::unserializeObject($struct, 
                    'recenterLocationRequest', 'recenterLocationRequest');
        $this->locationConstraint = self::unserializeObject($struct, 
                    'locationConstraint', 'LocationConstraint');
    }
}

/**
 * Constraints for a location
 *
 * TODO: add contraints for minScale, maxScale and others
 * @package CorePlugins
 */
class LocationConstraint extends Serializable {
    
    /**
     * Maximum bbox authorized to be viewed. Requests wanting a greater bbox
     * will be reduced to this bbox.
     * @var Bbox
     */
    public $maxBbox;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->maxBbox = self::unserializeObject($struct, 'maxBbox', 'Bbox');
    }
}

/**
 * Result information for plugin Location
 * @package CorePlugins
 */
class LocationResult extends Serializable {
    
    /**
     * @var Bbox
     */
    public $bbox;
    
    /**
     * @var double
     */
    public $scale;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        $this->scale = (double)$struct->scale;
    }
}

/**
 * Predefined scale for display in scales dropdown box
 * @package CorePlugins
 */
class LocationScale extends Serializable {

    /**
     * @var string
     */
    public $label;
    
    /**
     * @var double
     */ 
    public $value;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->label = self::unserializeValue($struct, 'label');
        $this->value = self::unserializeValue($struct, 'value', 'double');
    }
}

/**
 * Predefined shortcut for display in shortcuts dropdown box
 * @package CorePlugins
 */
class LocationShortcut extends Serializable {

    /**
     * @var string
     */
    public $label;
    
    /**
     * @var Bbox
     */
    public $bbox;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->label = self::unserializeValue($struct, 'label');
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * Location initialization information
 * @package CorePlugins
 */
class LocationInit extends Serializable {

    /**
     * @var array
     */
    public $scales;
    
    /**
     * @var double
     */
    public $minScale;
    
    /** 
     *  @var double
     */
    public $maxScale;

    /**
     * @var array
     */
    public $shortcuts;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->scales = self::unserializeObjectMap($struct, 'scales', 
                                                   'LocationScale');
        $this->minScale = self::unserializeValue($struct, 'minScale', 'float');
        $this->maxScale = self::unserializeValue($struct, 'maxScale', 'float');

        $this->shortcuts = self::unserializeObjectMap($struct, 'shortcuts',
                                                      'LocationShortcut');
    }
}

/**
 * Basic location request
 * @package CorePlugins
 */
abstract class RelativeLocationRequest extends Serializable {
    
    /**
     * @var Bbox
     */
    public $bbox;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * Location request to recenter on a Bbox
 * @package CorePlugins
 */
class BboxLocationRequest extends RelativeLocationRequest {

    /**
     * @var string
     */
    public $type = LocationRequest::LOC_REQ_BBOX;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->type = self::unserializeValue($struct, 'type');
        parent::unserialize($struct);        
    }
}

/**
 * Contains directions information for panning
 * @package CorePlugins
 */
class PanDirection {

    /**
     * Vertical direction constants
     */
    const VERTICAL_PAN_NORTH = 'VERTICAL_PAN_NORTH';
    const VERTICAL_PAN_NONE  = 'VERTICAL_PAN_NONE';
    const VERTICAL_PAN_SOUTH = 'VERTICAL_PAN_SOUTH';

    /**
     * Horizontal direction constants
     */
    const HORIZONTAL_PAN_WEST = 'HORIZONTAL_PAN_WEST';
    const HORIZONTAL_PAN_NONE = 'HORIZONTAL_PAN_NONE';
    const HORIZONTAL_PAN_EAST = 'HORIZONTAL_PAN_EAST';

    /**
     * @var string
     */
    public $verticalPan;
    
    /**
     * @var string
     */
    public $horizontalPan;

    /**
     * @return string
     */
    public function __toString() {
        return "$this->verticalPan - $this->horizontalPan";
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->verticalPan = self::unserializeValue($struct, 'verticalPan');
        $this->horizontalPan = self::unserializeValue($struct, 'horizontalPan');
        parent::unserialize($struct);        
    }
}

/**
 * Location request for panning
 * @package CorePlugins
 */
class PanLocationRequest extends RelativeLocationRequest {

    /**
     * @var string
     */
    public $type = LocationRequest::LOC_REQ_PAN;
    
    /**
     * @var PanDirection
     */
    public $panDirection;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->type = self::unserializeValue($struct, 'type');
        $this->panDirection = self::unserializeObject($struct, 'panDirection',
                            'PanDirection');
        parent::unserialize($struct);        
    }
}

/**
 * Location request for zooming
 * @package CorePlugins
 */
abstract class ZoomLocationRequest extends RelativeLocationRequest {

}

/**
 * Location request for recentering, zooming and rescaling
 * @package CorePlugins
 */
class ZoomPointLocationRequest extends ZoomLocationRequest {

    /**
     * @var string
     */
    public $type = LocationRequest::LOC_REQ_ZOOM_POINT;

    /**
     * ZoomType constants
     */
    const ZOOM_DIRECTION_IN   = 'ZOOM_DIRECTION_IN';
    const ZOOM_DIRECTION_OUT  = 'ZOOM_DIRECTION_OUT';
    const ZOOM_DIRECTION_NONE = 'ZOOM_DIRECTION_NONE';
    const ZOOM_FACTOR         = 'ZOOM_FACTOR';
    const ZOOM_SCALE          = 'ZOOM_SCALE';
    
    /**
     * @var string
     */
    public $zoomType;
    
    /**
     * @var Point
     */
    public $point;

    /** 
     * @var float
     */
    public $zoomFactor;
    
    /**
     * @var float
     */
    public $scale;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->zoomType = self::unserializeValue($struct, 'zoomType');
        $this->point = self::unserializeObject($struct, 'point', 'Point');
        $this->zoomFactor = self::unserializeValue($struct, 'zoomFactor', 'float');
        $this->scale = self::unserializeValue($struct, 'scale', 'float');

        parent::unserialize($struct);        
    }
}

/**
 * Describes a selection of a set of objects identified by their id's
 * 
 * This object is used by other plugins, like the Selection plugin.
 * @package CorePlugins
 */
class IdSelection extends Serializable {
    
    /**
     * @var string
     */
    public $layerId;
    
    /**
     * @var string
     */
    public $idAttribute;
    
    /**
     * @var mixed
     */
    public $idType;
    
    /** 
     * @var array
     */
    public $selectedIds;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layerId = self::unserializeValue($struct, 'layerId');
        $this->idAttribute = self::unserializeValue($struct, 'idAttribute');
        $this->idType = self::unserializeValue($struct, 'idType');
        $this->selectedIds = self::unserializeArray($struct, 'selectedIds');
    }
}

/**
 * Location request for recentering on Ids
 * @package CorePlugins
 */
class RecenterLocationRequest extends Serializable {

    /**
     * @var string
     */
    public $type = LocationRequest::LOC_REQ_RECENTER;
 
    /** 
     * @var array
     */
    public $idSelections;
 
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->idSelections = self::unserializeObjectMap($struct, 'idSelections', 
                                                         'IdSelection'); 
    }
}

?>
