<?
/**
 * @package CorePlugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');

/**
 * @package CorePlugins
 */
abstract class BboxCalculator {
    private $log;
    protected $requ;

    function __construct($requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->requ = $requ;
    }

    abstract function getBbox();
}

/**
 * @package CorePlugins
 */
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

/**
 * @package CorePlugins
 */
abstract class RelativeBboxCalculator extends BboxCalculator {
    private $log;

    protected $requ;
    protected $mapExtent;

    function __construct($requ, $mapExtent) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->requ = $requ;
        $this->mapExtent = $mapExtent;
    }
    
    abstract function getZoomFactor();
    abstract function getNewCenter();

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

/**
 * @package CorePlugins
 */
class PanBboxCalculator extends RelativeBboxCalculator {
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

        // TODO: read from config
        $panRatio = 0.75;

        $center = $this->mapExtent->getCenter();

        $xOffset = $this->mapExtent->getWidth() * $panRatio * 
            $this->panDirectionToFactor($this->requ->panDirection->horizontalPan);
        $yOffset = $this->mapExtent->getHeight() * $panRatio *
            $this->panDirectionToFactor($this->requ->panDirection->verticalPan);
        return new Point($center->x + $xOffset,
                         $center->y + $yOffset);
    }
}

/**
 * @package CorePlugins
 */
class ZoomPointBboxCalculator extends RelativeBboxCalculator {
    private $log;

    function __construct($requ, $mapExtent) {
        parent::__construct($requ, $mapExtent);
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getZoomFactor() {
        switch ($this->requ->zoomType) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            return 2.0;//TODO: read config
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            return 1.0;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            return -2.0;//TODO: read config
        case ZoomPointLocationRequest::ZOOM_FACTOR:
            return $this->requ->zoomFactor;
        case ZoomPointLocationRequest::ZOOM_SCALE:
            return 1;
        }
        throw new CartoclientException("unknown zoom type " .
                                       $this->requ->zoomType);
    }

    function getNewCenter() {
        return $this->requ->point;
    }
}

/*
FIXME: this is not used.

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
*/

/**
 * @package CorePlugins
 */
class ServerLocation extends ServerCorePlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getResultFromRequest($requ) {
        $this->log->debug("Get result from request: ");
        $this->log->debug($requ);

        $msMapObj = $this->serverContext->msMapObj;
        $location = new LocationResult();
        $imageDimension = new Dimension($msMapObj->width, 
                                        $msMapObj->height); 

        switch($requ->locationType) {
        case LocationRequest::LOC_REQ_BBOX:
            $oldBbox = $requ->bboxLocationRequest->bbox;
            $bboxCalculator = new NoopBboxCalculator($requ->bboxLocationRequest);
            break;
        case LocationRequest::LOC_REQ_PAN:
            $oldBbox = $requ->panLocationRequest->bbox;
            $bboxCalculator = new PanBboxCalculator(
                $requ->panLocationRequest,
                $oldBbox);
            break;
        case LocationRequest::LOC_REQ_ZOOM_POINT:
            $oldBbox = $requ->zoomPointLocationRequest->bbox;
            $bboxCalculator = new ZoomPointBboxCalculator(
                $requ->zoomPointLocationRequest,
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

        // FIXME: Should be replaced by a more general scales management 
        if ($requ->locationType == LocationRequest::LOC_REQ_ZOOM_POINT) {
            if ($requ->zoomPointLocationRequest->zoomType == ZoomPointLocationRequest::ZOOM_SCALE) {
                $center = ms_newPointObj();
                $center->setXY($msMapObj->width/2, $msMapObj->height/2); 
                $msMapObj->zoomscale($requ->zoomPointLocationRequest->scale,
                            $center, $msMapObj->width, $msMapObj->height, $msMapObj->extent);
            }
        }
        
        $location->bbox = new Bbox();
        $location->bbox->setFromMsExtent($msMapObj->extent);
        $location->scale = $msMapObj->scale;
        return $location;
    }    
}
?>