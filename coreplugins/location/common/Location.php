<?

require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

class LocationRequest {
    public $locationType;

    const LOC_REQ_BBOX = 'bboxLocationRequest';
    const LOC_REQ_PAN = 'panLocationRequest';
    const LOC_REQ_ZOOM_POINT = 'zoomPointLocationRequest';
    const LOC_REQ_ZOOM_RECTANGLE = 'zoomRectangleLocationRequest';

    public $bboxLocationRequest;
    public $panLocationRequest;
    public $zoomPointLocationRequest;
    public $zoomRectangleLocationRequest;
}

class LocationResult extends Serializable {
    public $bbox;
    public $scale;

    public function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        $this->scale = (double)$struct->scale;
    }
}

abstract class RelativeLocationRequest extends LocationRequest {
    public $bbox;
}

class BboxLocationRequest extends RelativeLocationRequest {
    public $type = LocationRequest::LOC_REQ_BBOX;
}

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
}

class PanLocationRequest extends RelativeLocationRequest {
    public $type = LocationRequest::LOC_REQ_PAN;
    public $panDirection;
}

abstract class ZoomLocationRequest extends RelativeLocationRequest {

}

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
}

/* This may go away. Can be replaced by ZoomPointLocationRequest */
class ZoomRectangleLocationRequest extends ZoomLocationRequest {
    public $type = LocationRequest::LOC_REQ_ZOOM_RECTANGLE;
    public $rectangle;
}

?>