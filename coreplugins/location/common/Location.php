<?

class LocationRequest {
    public $locationType;

    const LOC_REQ_BBOX = 'bboxLocationRequest';
    const LOC_REQ_PAN = 'panLocationRequest';
    const LOC_REQ_ZOOM_POINT = 'zoomPointLocationRequest';
    const LOC_REQ_ZOOM_RECTANGLE = 'zoomRectangleLocationRequest';
/*    
    public $bboxLocationRequest;
    public $panLocationRequest;
    public $zoomPointLocationRequest;
    public $zoomRectangleLocationRequest;
*/
}

class LocationResult {
    public $bbox;
    public $scale;

    function getVarInfo() {
        return array(
            'bbox' => 'obj,Bbox',
            );
    }
}

abstract class RelativeLocationRequest extends LocationRequest {
    public $bbox;
}

class BboxLocationRequest extends RelativeLocationRequest {
    
    public $type = LocationRequest::LOC_REQ_BBOX;
    //public $bbox;
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
    const ZOOM_DIRECTION_NONE = 'ZOOM_DIRECTION_NONE';
    const ZOOM_DIRECTION_OUT = 'ZOOM_DIRECTION_OUT';

    public $imagePoint;
    public $zoomDirection;
}

class ZoomRectangleLocationRequest extends ZoomLocationRequest {
    public $type = LocationRequest::LOC_REQ_ZOOM_RECTANGLE;
    public $imageRectangle;
}

/*
class ZoomInLocationRequest extends ZoomLocationRequest {
}

class ZoomOutLocationRequest extends ZoomLocationRequest {
}
*/

?>