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
        
        // Old ratio so we can check ratios
        $oldRatio = $oldBbox->getWidth() / $oldBbox->getHeight();
        
        // Horizontal 
        if ($newBbox->minx < $this->maxExtent->minx) {
            $newBbox->maxx = $newBbox->maxx + $this->maxExtent->minx 
                             - $newBbox->minx;
            $newBbox->minx = $this->maxExtent->minx;            
            if ($newBbox->maxx > $this->maxExtent->maxx) {
                // Bbox was too large to fit
                $newBbox->maxx = $this->maxExtent->maxx;
            }
        } else if ($newBbox->maxx > $this->maxExtent->maxx) {
            $newBbox->minx = $newBbox->minx + $this->maxExtent->maxx 
                             - $newBbox->maxx;
            $newBbox->maxx = $this->maxExtent->maxx;
            if ($newBbox->minx < $this->maxExtent->minx) {
                // Bbox was too large to fit
                $newBbox->minx = $this->maxExtent->minx;
            }
        }

        // Vertical
        if ($newBbox->miny < $this->maxExtent->miny) {
            $newBbox->maxy = $newBbox->maxy + $this->maxExtent->miny 
                             - $newBbox->miny;
            $newBbox->miny = $this->maxExtent->miny;
            if ($newBbox->maxy > $this->maxExtent->maxy) {
                // Bbox was too high to fit
                $newBbox->maxy = $this->maxExtent->maxy;
            }
        } else if ($newBbox->maxy > $this->maxExtent->maxy) {
            $newBbox->miny = $newBbox->miny + $this->maxExtent->maxy 
                             - $newBbox->maxy;
            $newBbox->maxy = $this->maxExtent->maxy;
            if ($newBbox->miny < $this->maxExtent->miny) {
                // Bbox was too high to fit
                $newBbox->miny = $this->maxExtent->miny;
            }
        }
        
        // Checking ratios
        $newRatio = $newBbox->getWidth() / $newBbox->getHeight();
        if ($oldRatio > $newRatio) {
            // Too high
            $newHeightDiff = ($newBbox->getHeight() - 
                              ($newBbox->getWidth() / $oldRatio)) / 2.0;
            $newBbox->miny += $newHeightDiff;
            $newBbox->maxy -= $newHeightDiff;
        } else if ($oldRatio < $newRatio) {
            // Too large
            $newWidthDiff = ($newBbox->getWidth() - 
                             ($newBbox->getHeight() * $oldRatio)) / 2.0;
            $newBbox->minx += $newWidthDiff;
            $newBbox->maxx -= $newWidthDiff;
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
    
    abstract function getBbox($oldBbox);
    abstract function getScale($oldScale);
}

/**
 * @package CorePlugins
 */
class NoopLocationCalculator extends LocationCalculator {
    private $log;

    function __construct($maxExtent) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($maxExtent);
    }

    function getBbox($oldBbox) {
        return $this->limitBbox($oldBbox);
    }
    
    function getScale($oldScale) {
        return NULL;
    }
}

/**
 * @package CorePlugins
 */
class PanLocationCalculator extends LocationCalculator {
    private $log;
    private $requ;
    private $panRatio;

    function __construct($requ, $panRatio, $maxExtent) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($maxExtent);
        $this->requ = $requ;
        $this->panRatio = $panRatio;
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
    
    function getBbox($oldBbox) {
        $panRatio = $this->panRatio;
        if (!$panRatio) {                
            $panRatio = 1.0;
        }

        $xOffset = $oldBbox->getWidth() * $panRatio * 
            $this->panDirectionToFactor($this->requ->panDirection->horizontalPan);
        $yOffset = $oldBbox->getHeight() * $panRatio *
            $this->panDirectionToFactor($this->requ->panDirection->verticalPan);
        $bbox = new Bbox();
        $bbox->setFromBbox($oldBbox->minx + $xOffset, 
                           $oldBbox->miny + $yOffset,
                           $oldBbox->maxx + $xOffset,
                           $oldBbox->maxy + $yOffset);
        return $this->limitBbox($bbox);
    }

    function getScale($oldScale) {
        return NULL;
    }
}

/**
 * @package CorePlugins
 */
class ZoomPointLocationCalculator extends LocationCalculator {
    private $log;
    private $requ;
    private $scaleModeDiscrete;

    function __construct($requ, $scaleModeDiscrete, $maxExtent,
                         $minScale, $maxScale, $scales) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($maxExtent, $minScale, $maxScale, $scales);
        $this->requ = $requ;
        $this->scaleModeDiscrete = $scaleModeDiscrete;
    }

    function getBbox($oldBbox) {
        $xHalf = $oldBbox->getWidth() / 2;
        $yHalf = $oldBbox->getHeight() / 2;
        
        $bbox = new Bbox();
        $bbox->setFromBbox($this->requ->point->x - $xHalf,
                           $this->requ->point->y - $yHalf,
                           $this->requ->point->x + $xHalf,
                           $this->requ->point->y + $yHalf);
        return $this->limitBbox($bbox);
    }

    function getScale($oldScale) {
    
        $scale = 0;
        switch ($this->requ->zoomType) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            if ($this->scaleModeDiscrete) {
                $scale = $this->getPreviousScale($oldScale);
            } else {
                $scale = $oldScale / 2.0;//TODO: read config
            }
            break;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            return NULL;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            if ($this->scaleModeDiscrete) {
                $scale = $this->getNextScale($oldScale);
            } else {
                $scale = $oldScale * 2.0;//TODO: read config
            }
            break;
        case ZoomPointLocationRequest::ZOOM_FACTOR:
            $zoom = $this->requ->zoomFactor;
            if ($zoom > 1)
                $zoom = (1.0 / $zoom);
            else if ($zoom < 0)
                $zoom = abs($zoom);
            $contScale = $oldScale * $zoom;           
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
            throw new CartoserverException('unknown zoom type ' .
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
       
            if ($config->$key || is_null($config->$key)) {
                $this->visibleScales[] = $scale;
            } 
            $this->scales[] = $scale;
        }
    }
    
    function getResultFromRequest($requ) {
        $this->log->debug('Get result from request: ');
        $this->log->debug($requ);

        $this->initScales();

        $msMapObj = $this->serverContext->msMapObj;
        $location = new LocationResult();
        $imageDimension = new Dimension($msMapObj->width, 
                                        $msMapObj->height); 

        $oldScale = 0;
        switch($requ->locationType) {
        case LocationRequest::LOC_REQ_BBOX:
            $oldBbox = $requ->bboxLocationRequest->bbox;
            $locCalculator = new NoopLocationCalculator(
                                        $this->serverContext->maxExtent);
            break;
        case LocationRequest::LOC_REQ_PAN:
            $oldBbox = $requ->panLocationRequest->bbox;
            $locCalculator = new PanLocationCalculator(
                                        $requ->panLocationRequest,
                                        $this->getConfig()->panRatio,
                                        $this->serverContext->maxExtent);
            break;
        case LocationRequest::LOC_REQ_ZOOM_POINT:
            $oldBbox = $requ->zoomPointLocationRequest->bbox;
            $msMapObj->setExtent($oldBbox->minx, $oldBbox->miny, 
                                 $oldBbox->maxx, $oldBbox->maxy);
            $oldScale = round($msMapObj->scale, 2);
            $locCalculator = new ZoomPointLocationCalculator(
                                        $requ->zoomPointLocationRequest,
                                        $this->getConfig()->scaleModeDiscrete,
                                        $this->serverContext->maxExtent,
                                        $this->getConfig()->minScale,
                                        $this->getConfig()->maxScale,
                                        $this->scales);
            break;
        default:
            throw new CartoserverException('unknown location request type: ' . 
                                           $requ->locationType);
        }

        // Scaling
        $scale = $locCalculator->getScale($oldScale);

        if ($scale) {            
            $center = ms_newPointObj();
            $center->setXY($msMapObj->width/2, $msMapObj->height/2); 
            $msMapObj->zoomscale($scale, $center,
                        $msMapObj->width, $msMapObj->height, $msMapObj->extent);
            
            $bbox = new Bbox();
            $bbox->setFromMsExtent($msMapObj->extent);

            $bbox = $locCalculator->getBbox($bbox);       
        } else {
            $bbox = $locCalculator->getBbox($oldBbox);            
        }
                
        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);

        $location->bbox = new Bbox();
        $location->bbox->setFromMsExtent($msMapObj->extent);
        
        $location->scale = round($msMapObj->scale, 2);            
        return $location;
    }    
    
    function getInitValues() {

        $this->initScales();

        $init = new LocationInit();
        $init->scales = $this->visibleScales;
        $init->minScale = $this->getConfig()->minScale;
        $init->maxScale = $this->getConfig()->maxScale;
        return $init;
    }
}
?>
