<?
/**
 * @package CorePlugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');

/**
 * @package CorePlugins
 */
abstract class LocationCalculator {
    private $log;
    protected $requ;

    function __construct($requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->requ = $requ;
    }

    abstract function getBbox();
    abstract function getScale();
}

/**
 * @package CorePlugins
 */
class NoopLocationCalculator extends LocationCalculator {
    private $log;

    function __construct($requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($requ);
    }

    function getBbox() {
        return $this->requ->bbox;
    }
    
    function getScale() {
        return NULL;
    }
}

/**
 * @package CorePlugins
 */
class PanLocationCalculator extends LocationCalculator {
    private $log;
    private $oldBbox;

    function __construct($requ, $oldBbox) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($requ);
        $this->oldBbox = $oldBbox;
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
    
    function getBbox() {

        // TODO: read from config
        $panRatio = 0.75;

        $xOffset = $this->oldBbox->getWidth() * $panRatio * 
            $this->panDirectionToFactor($this->requ->panDirection->horizontalPan);
        $yOffset = $this->oldBbox->getHeight() * $panRatio *
            $this->panDirectionToFactor($this->requ->panDirection->verticalPan);
        $bbox = new Bbox();
        $bbox->setFromBbox($this->oldBbox->minx + $xOffset, 
                           $this->oldBbox->miny + $yOffset,
                           $this->oldBbox->maxx + $xOffset,
                           $this->oldBbox->maxy + $yOffset);
        return $bbox;
    }

    function getScale() {
        return NULL;
    }
}

/**
 * @package CorePlugins
 */
class ZoomPointLocationCalculator extends LocationCalculator {
    private $log;
    private $oldBbox;
    private $oldScale;
    private $scaleModeDiscrete;
    private $scales;

    function __construct($requ, $oldBbox, $oldScale,
                         $scaleModeDiscrete, $scales) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($requ);
        $this->oldBbox = $oldBbox;
        $this->oldScale = round($oldScale, 2);  // round
        $this->scaleModeDiscrete = $scaleModeDiscrete;
        $this->scales = $scales;
    }

    function getBbox() {
        $xHalf = $this->oldBbox->getWidth() / 2;
        $yHalf = $this->oldBbox->getHeight() / 2;
        
        $bbox = new Bbox();
        $bbox->setFromBbox($this->requ->point->x - $xHalf,
                           $this->requ->point->y - $yHalf,
                           $this->requ->point->x + $xHalf,
                           $this->requ->point->y + $yHalf);
        return $bbox;
    }

    function getScale() {
    
        $contScale = 0;
        switch ($this->requ->zoomType) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            if ($this->scaleModeDiscrete) {
                $newScale = 0;
                foreach ($this->scales as $scale) {
                    if ($scale->value >= $this->oldScale) {
                        break;
                    }
                    $newScale = $scale->value;
                }
                if ($newScale == 0) {
                    $newScale = $this->oldScale;
                }
                return $newScale;
            } else {
                return $this->oldScale / 2.0;//TODO: read config
            }
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            return NULL;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            if ($this->scaleModeDiscrete) {
                $newScale = 0;
                foreach ($this->scales as $scale) {
                    $newScale = $scale->value;
                    if ($newScale > $this->oldScale) {
                        break;
                    }
                }
                if ($newScale == 0) {
                    $newScale = $this->oldScale;
                }
                return $newScale;
            } else {
                return $this->oldScale * 2.0;//TODO: read config
            }
        case ZoomPointLocationRequest::ZOOM_FACTOR:
            $zoom = $this->requ->zoomFactor;
            if ($zoom > 1)
                $zoom = (1.0 / $zoom);
            else if ($zoom < 0)
                $zoom = abs($zoom);
            $contScale = $this->oldScale * $zoom;
            break;
        case ZoomPointLocationRequest::ZOOM_SCALE:
            $contScale = $this->requ->scale;
            break;
        default:
            throw new CartoserverException("unknown zoom type " .
                                           $this->requ->zoomType);
        }
        $newScale = 0;
        if ($this->scaleModeDiscrete) {
            $min = -1;
            foreach ($this->scales as $scale) {
                $diff = abs($contScale - $scale->value);
                if ($diff < $min || $min == -1) {
                    $min = $diff;
                    $newScale = $scale->value;
                }
            }         
        }
        if ($newScale == 0) {
            $newScale = $contScale;
        }
        return $newScale;

    }
}

/**
 * @package CorePlugins
 */
class ServerLocation extends ServerCorePlugin {
    private $log;
    
    private $scales;
    private $visibleScales;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    private function initScales() {
        $this->scales = array();
        $this->visibleScales = array();

        $config = $this->getConfig();

        for ($i = 0; ; $i++) {
            $key = 'scales.' . $i . '.value';
            if (!$config->$key) {
                break;
            }
            $scale = new LocationScale();
            $scale->value = $config->$key;
            
            $key = 'scales.' . $i . '.label';
            $scale->label = $config->$key;
            
            $key = 'scales.' . $i . '.visible';
            if ($config->$key) {
                $this->visibleScales[] = $scale;
            } 
            $this->scales[] = $scale;
        }
    }
    
    function getResultFromRequest($requ) {
        $this->log->debug("Get result from request: ");
        $this->log->debug($requ);

        $this->initScales();

        $msMapObj = $this->serverContext->msMapObj;
        $location = new LocationResult();
        $imageDimension = new Dimension($msMapObj->width, 
                                        $msMapObj->height); 

        switch($requ->locationType) {
        case LocationRequest::LOC_REQ_BBOX:
            $locCalculator = new NoopLocationCalculator(
                                        $requ->bboxLocationRequest);
            break;
        case LocationRequest::LOC_REQ_PAN:
            $oldBbox = $requ->panLocationRequest->bbox;
            $locCalculator = new PanLocationCalculator(
                                        $requ->panLocationRequest, $oldBbox);
            break;
        case LocationRequest::LOC_REQ_ZOOM_POINT:
            $oldBbox = $requ->zoomPointLocationRequest->bbox;
            $msMapObj->setExtent($oldBbox->minx, $oldBbox->miny, 
                                 $oldBbox->maxx, $oldBbox->maxy);
            $oldScale = $msMapObj->scale;
            
            $locCalculator = new ZoomPointLocationCalculator(
                                        $requ->zoomPointLocationRequest,
                                        $oldBbox, $oldScale, 
                                        $this->getConfig()->scaleModeDiscrete,
                                        $this->scales);
            break;
        default:
            throw new CartoserverException('unknown location request type: ' . $requ->locationType);
        }

        // Centering
        $bbox = $locCalculator->getBbox();
        if (!$bbox)
            throw new CartoserverException("Location plugin could not calculate bbox");

        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);

        // Scaling
        $scale = $locCalculator->getScale();
        
        if ($scale) {
            $center = ms_newPointObj();
            $center->setXY($msMapObj->width/2, $msMapObj->height/2); 
            $msMapObj->zoomscale($scale, $center,
                        $msMapObj->width, $msMapObj->height, $msMapObj->extent);
            $location->scale = $scale;
        }

        $location->bbox = new Bbox();
        $location->bbox->setFromMsExtent($msMapObj->extent);
        
        if (!$scale) {
            $location->scale = $msMapObj->scale;            
        }
        return $location;
    }    
    
    function getInitValues() {

        $this->initScales();

        $init = new LocationInit();
        $init->scales = $this->visibleScales;
        return $init;
    }
}
?>