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
    private $maxExtent;
    private $scaleModeDiscrete;
    private $minScale;
    private $maxScale;
    private $scales;
    
    function __construct($maxExtent = NULL,
                         $minScale = -1, $maxScale = -1, $scales = NULL) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->maxExtent = $maxExtent;
        $this->minScale = $minScale;
        $this->maxScale = $maxScale;
        $this->scales = $scales;
    }

    function limitBbox($oldBbox) {
        $newBbox = $oldBbox;
        if ($newBbox->minx < $this->maxExtent->minx) {
            $newBbox->maxx = $newBbox->maxx + $this->maxExtent->minx - $newBbox->minx;
            $newBbox->minx = $this->maxExtent->minx;
        } else if ($newBbox->maxx > $this->maxExtent->maxx) {
            $newBbox->minx = $newBbox->minx + $this->maxExtent->maxx - $newBbox->maxx;
            $newBbox->maxx = $this->maxExtent->maxx;
        }
        if ($newBbox->miny < $this->maxExtent->miny) {
            $newBbox->maxy = $newBbox->maxy + $this->maxExtent->miny - $newBbox->miny;
            $newBbox->miny = $this->maxExtent->miny;
        } else if ($newBbox->maxy > $this->maxExtent->maxy) {
            $newBbox->miny = $newBbox->miny + $this->maxExtent->maxy - $newBbox->maxy;
            $newBbox->maxy = $this->maxExtent->maxy;
        }
        return $newBbox;
    }
    
    function limitScale($oldScale) {
        $newScale = $oldScale;
        if ($this->minScale && $newScale < $this->minScale) {
            $newScale = $this->minScale;
        } else if ($this->maxScale && $newScale > $this->maxScale) {
            $newScale = $this->maxScale;
        }
        return $newScale;
    }
    
    function getPreviousScale($oldScale) {
        $newScale = 0;
        foreach ($this->scales as $scale) {
            if ($scale->value >= $oldScale) {
                break;
            }
            $newScale = $scale->value;
        }
        if ($newScale == 0) {
            $newScale = $oldScale;
        }
        return $newScale; 
    }
    
    function getNextScale($oldScale) {
        $newScale = 0;
        foreach ($this->scales as $scale) {
            $newScale = $scale->value;
            if ($newScale > $oldScale) {
                break;
            }
        }
        if ($newScale == 0) {
            $newScale = $oldScale;
        }
        return $newScale;
    }
    
    function getNearestScale($oldScale) {
        $newScale = 0;
        $min = -1;
        foreach ($this->scales as $scale) {
            $diff = abs($oldScale - $scale->value);
            if ($diff < $min || $min == -1) {
                $min = $diff;
                $newScale = $scale->value;
            }
        }         
        if ($newScale == 0) {
            $newScale = $oldScale;
        }
        return $newScale;
    }
    
    abstract function getBbox();
    abstract function getScale();
}

/**
 * @package CorePlugins
 */
class NoopLocationCalculator extends LocationCalculator {
    private $log;
    private $oldBbox;

    function __construct($oldBbox) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
        $this->oldBbox = $oldBbox;
    }

    function getBbox() {
        return $this->oldBbox;
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
    private $requ;

    function __construct($requ, $maxExtent) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($maxExtent);
        $this->requ = $requ;
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

        $xOffset = $this->requ->bbox->getWidth() * $panRatio * 
            $this->panDirectionToFactor($this->requ->panDirection->horizontalPan);
        $yOffset = $this->requ->bbox->getHeight() * $panRatio *
            $this->panDirectionToFactor($this->requ->panDirection->verticalPan);
        $bbox = new Bbox();
        $bbox->setFromBbox($this->requ->bbox->minx + $xOffset, 
                           $this->requ->bbox->miny + $yOffset,
                           $this->requ->bbox->maxx + $xOffset,
                           $this->requ->bbox->maxy + $yOffset);
        return $this->limitBbox($bbox);
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
    private $requ;
    private $oldScale;
    private $scaleModeDiscrete;

    function __construct($requ, $oldScale, $scaleModeDiscrete, $maxExtent,
                         $minScale, $maxScale, $scales) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($maxExtent, $minScale, $maxScale, $scales);
        $this->requ = $requ;
        $this->oldScale = round($oldScale, 2);  // round
        $this->scaleModeDiscrete = $scaleModeDiscrete;
    }

    function getBbox() {
        $xHalf = $this->requ->bbox->getWidth() / 2;
        $yHalf = $this->requ->bbox->getHeight() / 2;
        
        $bbox = new Bbox();
        $bbox->setFromBbox($this->requ->point->x - $xHalf,
                           $this->requ->point->y - $yHalf,
                           $this->requ->point->x + $xHalf,
                           $this->requ->point->y + $yHalf);
        return $this->limitBbox($bbox);
    }

    function getScale() {
    
        $scale = 0;
        switch ($this->requ->zoomType) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            if ($this->scaleModeDiscrete) {
                $scale = $this->getPreviousScale($this->oldScale);
            } else {
                $scale = $this->oldScale / 2.0;//TODO: read config
            }
            break;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            return NULL;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            if ($this->scaleModeDiscrete) {
                $scale = $this->getNextScale($this->oldScale);
            } else {
                $scale = $this->oldScale * 2.0;//TODO: read config
            }
            break;
        case ZoomPointLocationRequest::ZOOM_FACTOR:
            $zoom = $this->requ->zoomFactor;
            if ($zoom > 1)
                $zoom = (1.0 / $zoom);
            else if ($zoom < 0)
                $zoom = abs($zoom);
            $contScale = $this->oldScale * $zoom;           
            if ($this->scaleModeDiscrete) {
                $scale = $this->getNearestScale($contScale);
            } else {
                $scale = $contScale;
            }
            break;
        case ZoomPointLocationRequest::ZOOM_SCALE:
            $scale = $this->requ->scale;
            break;
        default:
            throw new CartoserverException("unknown zoom type " .
                                           $this->requ->zoomType);
        }
        return $this->limitScale($scale);
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
                                        $requ->bboxLocationRequest->bbox);
            break;
        case LocationRequest::LOC_REQ_PAN:
            $locCalculator = new PanLocationCalculator(
                                        $requ->panLocationRequest,
                                        $this->serverContext->maxExtent);
            break;
        case LocationRequest::LOC_REQ_ZOOM_POINT:
            $oldBbox = $requ->zoomPointLocationRequest->bbox;
            $msMapObj->setExtent($oldBbox->minx, $oldBbox->miny, 
                                 $oldBbox->maxx, $oldBbox->maxy);
            $locCalculator = new ZoomPointLocationCalculator(
                                        $requ->zoomPointLocationRequest,
                                        $msMapObj->scale,
                                        $this->getConfig()->scaleModeDiscrete,
                                        $this->serverContext->maxExtent,
                                        $this->getConfig()->minScale,
                                        $this->getConfig()->maxScale,
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