<?

abstract class BboxCalculator {
    private $log;
    protected $requ;

    function __construct($requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->requ = $requ;
    }

    abstract function getBbox();
}

class NoopBboxCalculator extends BboxCalculator {
    private $log;

    function __construct($requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($requ);
    }

    function getBbox() {
        return $this->requ->bbox;
    }
}

abstract class RelativeBboxCalculator extends BboxCalculator {
    private $log;

    protected $requ;
    protected $imageDimension;
    protected $mapExtent;

    function __construct($requ, $imageDimension, $mapExtent) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->requ = $requ;
        $this->imageDimension = $imageDimension;
        $this->mapExtent = $mapExtent;
    }
    
    abstract function getZoomFactor();
    abstract function getNewImageCenter();

    function pix2geo($pixPos, $pixMax, $geoMin, $geoMax, $isYOrig=false) {

        $widthGeo = $geoMax - $geoMin;
        $pix2geoFactor = $widthGeo / (double)$pixMax;

        if ($isYOrig)
            $pixPos = $pixMax - $pixPos;

        return $geoMin + ($pixPos * $pix2geoFactor);

    }

    function getBbox() {
        
        $geoHalfX = ($this->mapExtent->maxx - $this->mapExtent->minx) / 2.0;
        $geoHalfY = ($this->mapExtent->maxy - $this->mapExtent->miny) / 2.0;

        $newImageCenter = $this->getNewImageCenter();
        $this->log->debug("new center");
        $this->log->debug($newImageCenter);
        
        $newGeoX = $this->pix2geo($newImageCenter->x, $this->imageDimension->width, 
                           $this->mapExtent->minx, $this->mapExtent->maxx);
        $newGeoY = $this->pix2geo($newImageCenter->y, $this->imageDimension->height, 
                                  $this->mapExtent->miny, $this->mapExtent->maxy, true);

        $zoomFactor = $this->getZoomFactor();

        if ($zoomFactor > 1)
            $zoomFactor = (1.0 / $zoomFactor);
        else if ($zoomFactor < 0)
            $zoomFactor = abs($zoomFactor);

        $geoHalfX *= $zoomFactor;
        $geoHalfY *= $zoomFactor;

        $newBbox = new Bbox();
        $newBbox->setFromBbox($newGeoX - $geoHalfX, $newGeoY - $geoHalfY, 
                              $newGeoX + $geoHalfX, $newGeoY + $geoHalfY);

        return $newBbox;
    }
}

abstract class XRelativeBboxCalculator extends BboxCalculator {
    private $log;

    protected $requ;
    protected $mapExtent;

    function __construct($requ, $mapExtent) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->requ = $requ;
        $this->mapExtent = $mapExtent;
    }
    
    abstract function getZoomFactor();
    abstract function getNewImageCenter();

    function pix2geo($pixPos, $pixMax, $geoMin, $geoMax, $isYOrig=false) {

        $widthGeo = $geoMax - $geoMin;
        $pix2geoFactor = $widthGeo / (double)$pixMax;

        if ($isYOrig)
            $pixPos = $pixMax - $pixPos;

        return $geoMin + ($pixPos * $pix2geoFactor);

    }

    function getBbox() {
        
        $geoHalfX = ($this->mapExtent->maxx - $this->mapExtent->minx) / 2.0;
        $geoHalfY = ($this->mapExtent->maxy - $this->mapExtent->miny) / 2.0;

        /*
        $newImageCenter = $this->getNewImageCenter();
        $this->log->debug("new center");
        $this->log->debug($newImageCenter);
        
        $newGeoX = $this->pix2geo($newImageCenter->x, $this->imageDimension->width, 
                           $this->mapExtent->minx, $this->mapExtent->maxx);
        $newGeoY = $this->pix2geo($newImageCenter->y, $this->imageDimension->height, 
                                  $this->mapExtent->miny, $this->mapExtent->maxy, true);
        */
        
        $newCenter = $this->getNewCenter();
        $this->log->debug("new center");
        $this->log->debug($newCenter);
        $newGeoX = $newCenter->x;
        $newGeoY = $newCenter->y;

        $zoomFactor = $this->getZoomFactor();

        if ($zoomFactor > 1)
            $zoomFactor = (1.0 / $zoomFactor);
        else if ($zoomFactor < 0)
            $zoomFactor = abs($zoomFactor);

        $geoHalfX *= $zoomFactor;
        $geoHalfY *= $zoomFactor;

        $newBbox = new Bbox();
        $newBbox->setFromBbox($newGeoX - $geoHalfX, $newGeoY - $geoHalfY, 
                              $newGeoX + $geoHalfX, $newGeoY + $geoHalfY);

        return $newBbox;
    }
}


class PanBboxCalculator extends RelativeBboxCalculator {
    private $log;

    function __construct($requ, $imageDimension, $mapExtent) {
        parent::__construct($requ, $imageDimension, $mapExtent);
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getZoomFactor() {
        return 1.0;
    }
    private function panDirectionToFactor($panDirection) {
        switch ($panDirection) {
        case PanDirection::VERTICAL_PAN_NORTH:
        case PanDirection::HORIZONTAL_PAN_EAST:
            return 1; break;
        case PanDirection::VERTICAL_PAN_NONE:
        case PanDirection::HORIZONTAL_PAN_NONE:
            return 0; break;
        case PanDirection::VERTICAL_PAN_SOUTH:
        case PanDirection::HORIZONTAL_PAN_WEST:
            return -1; break;
        default:
            throw new CartoserverException("unknown pan direction $panDirection");
        }
    }
    function getNewImageCenter() {

        // TODO: read from config
        $panRatio = 1.0;
        
        $centerX = $this->imageDimension->width / 2.0;
        $centerY = $this->imageDimension->height / 2.0;

        $xOffset = $this->imageDimension->width * $panRatio * 
            $this->panDirectionToFactor($this->requ->panDirection->horizontalPan);
        $yOffset = $this->imageDimension->height * $panRatio *
            $this->panDirectionToFactor($this->requ->panDirection->verticalPan);
        return new Point($centerX + $xOffset,
                         $centerY - $yOffset);

    }
}

class XPanBboxCalculator extends XRelativeBboxCalculator {
    private $log;

    function __construct($requ, $mapExtent) {
        parent::__construct($requ, $mapExtent);
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    function getZoomFactor() {
        return 1.0;
    }
    private function panDirectionToFactor($panDirection) {
        switch ($panDirection) {
        case PanDirection::VERTICAL_PAN_NORTH:
        case PanDirection::HORIZONTAL_PAN_EAST:
            return 1; break;
        case PanDirection::VERTICAL_PAN_NONE:
        case PanDirection::HORIZONTAL_PAN_NONE:
            return 0; break;
        case PanDirection::VERTICAL_PAN_SOUTH:
        case PanDirection::HORIZONTAL_PAN_WEST:
            return -1; break;
        default:
            throw new CartoserverException("unknown pan direction $panDirection");
        }
    }
    function getNewCenter() {

//         // TODO: read from config
//         $panRatio = 1.0;
        
//         $width = $this->mapExtent->maxx - $this->mapExtent->minx;
//         $centerY = $this->mapExtent->maxy - $this->mapExtent->miny;

//         $centerX = $this->mapExtent->maxx - $this->mapExtent->minx;
//         $centerY = $this->mapExtent->maxy - $this->mapExtent->miny;

        $center = $this->mapExtent->getCetner();

        $xOffset = $this->mapExtent->getWidth * $panRatio * 
            $this->panDirectionToFactor($this->requ->panDirection->horizontalPan);
        $yOffset = $this->mapExtent->getHeight() * $panRatio *
            $this->panDirectionToFactor($this->requ->panDirection->verticalPan);
        return new Point($center->x + $xOffset,
                         $center->y - $yOffset); // IS NOT + ????

    }
}


class ZoomPointBboxCalculator extends RelativeBboxCalculator {
    private $log;

    function __construct($requ, $imageDimension, $mapExtent) {
        parent::__construct($requ, $imageDimension, $mapExtent);
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getZoomFactor() {
        switch ($this->requ->zoomDirection) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            return 2.0;//TODO: read config
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            return 1.0;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            return -2.0;//TODO: read config
        }
        throw new CartoclientException("unknown zoom direction " .
                                       $this->requ->zoomDirection);
    }

    function getNewImageCenter() {
        return $this->requ->imagePoint;
    }
}


class XZoomPointBboxCalculator extends XRelativeBboxCalculator {
    private $log;

    function __construct($requ, $mapExtent) {
        parent::__construct($requ, $mapExtent);
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getZoomFactor() {
        switch ($this->requ->zoomDirection) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            return 2.0;//TODO: read config
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            return 1.0;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            return -2.0;//TODO: read config
        }
        throw new CartoclientException("unknown zoom direction " .
                                       $this->requ->zoomDirection);
    }

    function getNewImageCenter() {
        // TODO !!
        x('todo_zoom_bbox');
        return $this->requ->imagePoint;
    }
}


class ZoomRectBboxCalculator extends RelativeBboxCalculator {
    private $log;

    function __construct($requ, $imageDimension, $mapExtent) {
        parent::__construct($requ, $imageDimension, $mapExtent);
        $this->log =& LoggerManager::getLogger(__CLASS__);

    }

    function getZoomFactor() {
        // TODO
        return 1.0;
    }

    function getNewImageCenter() {
        // TODO
    }
}

//class LocationPlugin extends CorePlugin {
class ServerLocation extends ServerCorePlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getRequestName() {
        return 'locationRequest';
    }

    function getResultName() {
        return 'location';
    }

    function getResultFromRequest($requ) {
        $this->log->debug("Get result from request: ");
        $this->log->debug($requ);

        $msMapObj = $this->serverContext->msMapObj;
        $location = new LocationResult();
        $imageDimension = new Dimension($msMapObj->width, 
                                        $msMapObj->height); 


        //TODO in a class/function
        switch($requ->locationType) {
        case LocationRequest::LOC_REQ_BBOX:
            $oldBbox = $requ->bboxLocationRequest->bbox;
            $bboxCalculator = new NoopBboxCalculator($requ->bboxLocationRequest);
            break;
        case LocationRequest::LOC_REQ_PAN:
            $oldBbox = $requ->panLocationRequest->bbox;
            $bboxCalculator = new PanBboxCalculator(
                $requ->panLocationRequest,
                $imageDimension,
                $oldBbox);
            break;
        case LocationRequest::LOC_REQ_ZOOM_POINT:
            $oldBbox = $requ->zoomPointLocationRequest->bbox;
            $bboxCalculator = new ZoomPointBboxCalculator(
                $requ->zoomPointLocationRequest,
                $imageDimension,
                $oldBbox);
            break;
        default:
            throw new CartoserverException('unknown location request type: ' . $requ->locationType);
        }

        $bbox = $bboxCalculator->getBbox();
        if (!$bbox)
            throw new CartoserverException("Location plugin could not calculate bbox");

        $this->log->debug("old bbox:");
        $this->log->debug($oldBbox);
        $this->log->debug("new bbox:");
        $this->log->debug($bbox);

        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);

        $location->bbox = new Bbox();
        $location->bbox->setFromMsExtent($msMapObj->extent);
        $location->scale = $msMapObj->scale;
        return $location;
    }    
}
?>