<?
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * @package CorePlugins
 */
class LocationRequest extends Serializable {
    const LOC_REQ_BBOX = 'bboxLocationRequest';
    const LOC_REQ_PAN = 'panLocationRequest';
    const LOC_REQ_ZOOM_POINT = 'zoomPointLocationRequest';
    const LOC_REQ_RECENTER = 'recenterLocationRequest';

    public $locationType;

    public $bboxLocationRequest;
    public $panLocationRequest;
    public $zoomPointLocationRequest;
    public $zoomRectangleLocationRequest;
    public $recenterLocationRequest;

    function unserialize($struct) {
        $this->locationType = self::unserializeValue($struct, 'locationType');

        $this->bboxLocationRequest = self::unserializeObject($struct, 
                    'bboxLocationRequest', 'BboxLocationRequest');
        $this->panLocationRequest = self::unserializeObject($struct, 
                    'panLocationRequest', 'PanLocationRequest');
        $this->zoomPointLocationRequest = self::unserializeObject($struct, 
                    'zoomPointLocationRequest', 'ZoomPointLocationRequest');
        $this->recenterLocationRequest = self::unserializeObject($struct, 
                    'recenterLocationRequest', 'recenterLocationRequest');
    }
}

/**
 * @package CorePlugins
 */
class LocationResult extends Serializable {
    public $bbox;
    public $scale;

    public function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        $this->scale = (double)$struct->scale;
    }
}

/**
 * @package CorePlugins
 */
class LocationScale extends Serializable {
    public $label;
    public $value;

    public function unserialize($struct) {
        $this->label = self::unserializeValue($struct, 'label');
        $this->value = self::unserializeValue($struct, 'value', 'double');
    }
}

/**
 * @package CorePlugins
 */
class LocationInit extends Serializable {
    public $scales;
    public $minScale;
    public $maxScale;

    public function unserialize($struct) {
        $this->scales = self::unserializeObjectMap($struct, 'scales', 'LocationScale');
        $this->minScale = self::unserializeValue($struct, 'minScale', 'float');
        $this->maxScale = self::unserializeValue($struct, 'maxScale', 'float');
    }
}

/**
 * @package CorePlugins
 */
// FIXME: this should not extend LocationRequest
abstract class RelativeLocationRequest extends Serializable {
    public $bbox;

    public function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        //parent::unserialize($struct);        
    }
}

/**
 * @package CorePlugins
 */
class BboxLocationRequest extends RelativeLocationRequest {
    public $type = LocationRequest::LOC_REQ_BBOX;

    public function unserialize($struct) {
        $this->type = self::unserializeValue($struct, 'type');
        parent::unserialize($struct);        
    }
}

/**
 * @package CorePlugins
 */
class PanDirection {

    const VERTICAL_PAN_NORTH = 'VERTICAL_PAN_NORTH';
    const VERTICAL_PAN_NONE = 'VERTICAL_PAN_NONE';
    const VERTICAL_PAN_SOUTH = 'VERTICAL_PAN_SOUTH';

    const HORIZONTAL_PAN_WEST = 'HORIZONTAL_PAN_WEST';
    const HORIZONTAL_PAN_NONE = 'HORIZONTAL_PAN_NONE';
    const HORIZONTAL_PAN_EAST = 'HORIZONTAL_PAN_EAST';

    public $verticalPan;
    public $horizontalPan;

    function __toString() {
        return "$this->verticalPan - $this->horizontalPan";
    }

    public function unserialize($struct) {
        $this->verticalPan = self::unserializeValue($struct, 'verticalPan');
        $this->horizontalPan = self::unserializeValue($struct, 'horizontalPan');
        parent::unserialize($struct);        
    }
}

/**
 * @package CorePlugins
 */
class PanLocationRequest extends RelativeLocationRequest {
    public $type = LocationRequest::LOC_REQ_PAN;
    public $panDirection;

    public function unserialize($struct) {
        $this->type = self::unserializeValue($struct, 'type');
        $this->panDirection = self::unserializeObject($struct, 'panDirection',
                            'PanDirection');
        parent::unserialize($struct);        
    }
}

/**
 * @package CorePlugins
 */
abstract class ZoomLocationRequest extends RelativeLocationRequest {

}

/**
 * @package CorePlugins
 */
class ZoomPointLocationRequest extends ZoomLocationRequest {
    public $type = LocationRequest::LOC_REQ_ZOOM_POINT;

    const ZOOM_DIRECTION_IN = 'ZOOM_DIRECTION_IN';
    const ZOOM_DIRECTION_OUT = 'ZOOM_DIRECTION_OUT';
    const ZOOM_DIRECTION_NONE = 'ZOOM_DIRECTION_NONE';
    const ZOOM_FACTOR = 'ZOOM_FACTOR';
    const ZOOM_SCALE = 'ZOOM_SCALE';
    
    public $zoomType;
    
    public $point;

    public $zoomFactor;
    public $scale;

    public function unserialize($struct) {
        $this->zoomType = self::unserializeValue($struct, 'zoomType');
        $this->point = self::unserializeObject($struct, 'point', 'Point');
        $this->zoomFactor = self::unserializeValue($struct, 'zoomFactor', 'float');
        $this->scale = self::unserializeValue($struct, 'scale', 'float');

        parent::unserialize($struct);        
    }
}

// TODO: maybe make this type common, and reuse it elsewhere, like in HilightRequest
//  and SelectionRequest
class IdSelection extends Serializable {
    public $layerId;
    public $idAttribute;
    public $idType; // (string|integer) 
    public $selectedIds;

    public function unserialize($struct) {
        $this->layerId = self::unserializeValue($struct, 'layerId');
        $this->idAttribute = self::unserializeValue($struct, 'idAttribute');
        $this->idType = self::unserializeValue($struct, 'idType');
        $this->selectedIds = self::unserializeArray($struct, 'selectedIds');
    }
}

class RecenterLocationRequest extends Serializable {
    public $type = LocationRequest::LOC_REQ_RECENTER;
 
    public $idSelections;
 
    public function unserialize($struct) {
        $this->idSelections = self::unserializeObjectMap($struct, 'idSelections', 
                                                         'IdSelection'); 
    }
}

?>