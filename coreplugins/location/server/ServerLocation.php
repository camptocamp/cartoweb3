<?
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Basic types
 */
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Log4php
 */
require_once('log4php/LoggerManager.php');

/**
 * Base abstract class for classes used to calculate bbox's and scales
 *
 * There is a one to one mapping between LocationRequests and these
 * LocationCalculator's. 
 * @package CorePlugins
 */
abstract class LocationCalculator {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var ServerLocation
     */
    public $locationPlugin;
    
    /**
     * @var LocationRequest
     */
    public $requ;
    
    /**
     * @param ServerLocation
     * @param LocationRequest
     */
    public function __construct($locationPlugin, $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->locationPlugin = $locationPlugin;
        $this->requ = $requ;
    }
    
    /**
     * Computes new Bbox
     * @return Bbox
     */
    abstract function getBbox();
    
    /**
     * Computes new scale
     * @return double
     */
    abstract function getScale();
}

/**
 * Location calculator for {@link BboxLocationRequest}
 * @package CorePlugins
 */
class BboxLocationCalculator extends LocationCalculator {
    
    /**
     * @var Logger
     */
    private $log;

    /**
     * @param ServerLocation
     * @param BboxLocationRequest
     */
    public function __construct($locationPlugin, BboxLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
    }
    
    /**
     * @see LocationCalculator::getBbox()
     */
    public function getBbox() {
        return $this->requ->bbox;
    }

    /**
     * @see LocationCalculator::getScale()
     */
    public function getScale() {
        return NULL;
    }
}

/**
 * Location calculator for {@link PanLocationRequest}
 * @package CorePlugins
 */
class PanLocationCalculator extends LocationCalculator {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var double
     */
    private $panRatio;

    /**
     * @param ServerLocation
     * @param PanLocationRequest
     */
    public function __construct($locationPlugin, PanLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
        
        $this->panRatio = $this->locationPlugin->getConfig()->panRatio;
    }

    /**
     * Transforms {@link PanDirection} orientation to increments
     * @param string
     * @return int
     */
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
    
    /**
     * @see LocationCalculator::getBbox()
     */
    public function getBbox() {
        $oldBbox = $this->requ->bbox;
        
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
        return $bbox;
    }

    /**
     * @see LocationCalculator::getScale()
     */
    public function getScale() {
        return NULL;
    }
}

/**
 * Location calculator for {@link ZoomPointLocationRequest}
 * @package CorePlugins
 */
class ZoomPointLocationCalculator extends LocationCalculator {
    
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var boolean
     */
    private $scaleModeDiscrete;

    /**
     * @param ServerLocation
     * @param ZoomPointLocationRequest
     */
    public function __construct($locationPlugin, ZoomPointLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
        
        $this->scaleModeDiscrete = $this->locationPlugin->getConfig()->scaleModeDiscrete;
        $this->zoomFactor = $this->locationPlugin->getConfig()->zoomFactor;
        if (!$this->zoomFactor)
            $this->zoomFactor = 2.0;
        $this->scales = $this->locationPlugin->getScales();
    }

    /**
     * @see LocationCalculator::getBbox()
     */
    public function getBbox() {
        $oldBbox = $this->requ->bbox;
        
        $xHalf = $oldBbox->getWidth() / 2;
        $yHalf = $oldBbox->getHeight() / 2;
        
        $bbox = new Bbox();
        $bbox->setFromBbox($this->requ->point->x - $xHalf,
                           $this->requ->point->y - $yHalf,
                           $this->requ->point->x + $xHalf,
                           $this->requ->point->y + $yHalf);
        return $bbox;
    }

    /**
     * Finds the previous scale in the scales list
     * @param double
     * @return double
     */
    private function getPreviousScale($oldScale) {
        $newScale = 0;
        $oldScale = $this->getNearestScale($oldScale);
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
    
    /**
     * Finds the next scale in the scales list
     * @param double
     * @return double
     */
    private function getNextScale($oldScale) {
        $newScale = 0;
        $oldScale = $this->getNearestScale($oldScale);
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

    /** 
     * Finds the nearest scale in the scales list
     * @param double
     * @return double
     */
    private function getNearestScale($oldScale) {
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

    /**
     * Computes scale from current MapServer extent
     * @return double
     */
    private function calculateOldScale() {
        $msMapObj = $this->locationPlugin->getServerContext()->getMapObj();
        
        $oldBbox = $this->requ->bbox;
        $msMapObj->setExtent($oldBbox->minx, $oldBbox->miny, 
                             $oldBbox->maxx, $oldBbox->maxy);
        $oldScale = $msMapObj->scale;
        return $oldScale;
    }

    /**
     * @see LocationCalculator::getScale()
     */
    public function getScale() {
        
        $oldScale = $this->calculateOldScale();
        $this->log->debug("old scale is $oldScale");
        
        $scale = 0;
        switch ($this->requ->zoomType) {
        case ZoomPointLocationRequest::ZOOM_DIRECTION_IN:
            if ($this->scaleModeDiscrete) {
                $scale = $this->getPreviousScale($oldScale);
            } else {
                $scale = $oldScale / $this->zoomFactor;
            }
            break;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_NONE:
            /* TODO: implement strict mode, and go to nearest scale if true */
            return NULL;
        case ZoomPointLocationRequest::ZOOM_DIRECTION_OUT:
            if ($this->scaleModeDiscrete) {
                $scale = $this->getNextScale($oldScale);
            } else {
                $scale = $oldScale * $this->zoomFactor;
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
        $this->log->debug("new scale is $scale");
        return $scale;
    }
}

/**
 * Location calculator for {@link RecenterLocationRequest}
 * @package CorePlugins
 */
class RecenterLocationCalculator extends LocationCalculator {
    
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var boolean
     */
    private $useDefaultScale;

    /**
     * @param ServerLocation
     * @param RecenterLocationRequest
     */
    public function __construct($locationPlugin, RecenterLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
    }

    /**
     * Performs queries in MapServer ans returns resulting Bbox
     * @param IdSelection
     * @return Bbox
     */
    private function getIdSelectionBbox(IdSelection $idSelection) {
    
        $pluginManager = $this->locationPlugin->
                                        getServerContext()->getPluginManager();
        if (!empty($pluginManager->mapquery)) {
            $results = $pluginManager->mapquery->queryByIdSelection($idSelection);
        }
        if (is_null($results) || count($results) == 0)
            throw new CartoserverException("Could not fetch recentering bbox");        

        $bboxes = array();
        foreach ($results as $result) {
            $bbox = new Bbox();
            $bbox->setFromMsExtent($result->bounds);
            $bboxes[] = $bbox;
        }
        return $this->mergeBboxes($bboxes);
    }

    /**
     * Merges Bbox in one large Bbox
     * @param array
     * @return Bbox
     */
    public function mergeBboxes($bboxes) {
        if (empty($bboxes))
            throw new CartoserverException("trying to merge empty bboxes");
        if (count($bboxes) == 1)
            return $bboxes[0]; 
        $mergedBbox = $bboxes[0];
        foreach(array_slice($bboxes, 1) as $bbox) { 
            $mergedBbox->minx = min($bbox->minx, $mergedBbox->minx);
            $mergedBbox->miny = min($bbox->miny, $mergedBbox->miny);
            $mergedBbox->maxx = max($bbox->maxx, $mergedBbox->maxx);
            $mergedBbox->maxy = max($bbox->maxy, $mergedBbox->maxy);
        }
        return $mergedBbox;        
    }

    /**
     * Adds a margin to a Bbox
     * @param Bbox
     * @param double
     * @return Bbox
     */
    private function addMargin(Bbox $bbox, $margin) {
        
        $width = $bbox->getWidth();
        $xDelta = $width * ($margin / 100);
        $height = $bbox->getHeight();
        $yDelta = $width * ($margin / 100);
        return new Bbox($bbox->minx - $xDelta, $bbox->miny - $yDelta,
                         $bbox->maxx + $xDelta, $bbox->maxy + $yDelta);
    }

    /**
     * Adds a border to a bbox
     * 
     * Used to transform zero sized (width or height is zero) bboxes 
     * to non zero sized ones.
     * @param Bbox
     * @return Bbox
     */
    private function addBboxBorders($bbox) {
     
        // FIXME: is there a better way than using this constant ? 
        $border = 1.0;
        $bbox = new Bbox($bbox->minx - $border, $bbox->miny - $border,
                         $bbox->minx + $border, $bbox->miny + $border);
        return $bbox;
    }

    /**
     * @see LocationCalculator::getBbox()
     */
    public function getBbox() {

        $bboxes = array();
        foreach($this->requ->idSelections as $idSelection) {
            $bbox = $this->getIdSelectionBbox($idSelection);
            if (!is_null($bbox))
                $bboxes[] = $bbox; 
        }
        if (empty($bboxes))
            throw new CartoserverException("no bbox found where to center");

        $bbox = $this->mergeBboxes($bboxes);

        $margin = $this->locationPlugin->getConfig()->recenterMargin;
        if (is_null($margin))
            $margin = 0;
        if ($margin != 0)
            $bbox = $this->addMargin($bbox, $margin);        

        $emptyBbox =  $bbox->getWidth() == 0 && $bbox->getHeight() == 0;
        
        // in case of an empty bbox, use the scale from configuration
        $this->useDefaultScale = $emptyBbox;
        if ($emptyBbox) {
            $bbox = $this->addBboxBorders($bbox);        
        }
        return $bbox;
    }

    /**
     * @see LocationCalculator::getScale()
     */
    public function getScale() {
        if (!$this->useDefaultScale)
            return NULL;
        
        $defaultScale = $this->locationPlugin->getConfig()->recenterDefaultScale;
        
        /* TODO: override the default scale from layers metadata */

        if (is_null($defaultScale) || $defaultScale < 0)
            throw new CartoserverException('you need to set a recenterDefaultScale ' .
                    'parameter in the server location.ini');
        
        return $defaultScale;
    }
}

/**
 * Server part of Location plugin
 * @package CorePlugins
 */
class ServerLocation extends ServerPlugin 
                     implements CoreProvider, InitProvider {
    
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * Possible scales in discrete mode (some may be hidden)
     * @var array
     */
    private $scales;
    
    /**
     * Scales to be displayed in dropdown box
     * @var array 
     */
    private $visibleScales;

    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Initializes scales from location.ini
     */
    private function initScales() {
        $this->scales = array();
        $this->visibleScales = array();

        $scales = ConfigParser::parseObjectArray($this->getConfig(), 'scales',
                                        array('value', 'label', 'visible'));
        foreach($scales as $scale) {
            $locScale = new LocationScale();
            $locScale->value = $scale->value;
            $locScale->label = $scale->label;
            if ($scale->visible || is_null($scale->visible)) {
                $this->visibleScales[] = $locScale;
            } 
            $this->scales[] = $locScale;
        }                                        
    }
    
    /**
     * @return array
     */
    public function getScales() {
        
        if (is_null($this->scales))
            throw new CartoserverException("scales not initialized");
        return $this->scales;
    }

    /**
     * Computes scale from a Bbox using MapServer
     * @param Bbox
     * @return double
     */
    private function getScaleFromBbox($bbox) {
        $msMapObj = $this->getServerContext()->getMapObj();
        
        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);
        $scale = $msMapObj->scale;
        return $scale;
    }
    
    /**
     * Adjusts scale using min/max set in configuration
     * @param double
     * @return double
     */
    private function adjustScale($scale) {
        if (is_null($scale))
            throw new CartoserverException("scale to adjust is null");
        if ($scale < 0)
            throw new CartoserverException("scale to adjust is negative");
        $newScale = $scale;
        $minScale = $this->getConfig()->minScale;
        $maxScale = $this->getConfig()->maxScale;
        if ($minScale && $newScale < $minScale) {
            $newScale = $minScale;
        } else if ($maxScale && $newScale > $maxScale) {
            $newScale = $maxScale;
        }
        return $newScale;
    }

    /**
     * Adjusts Bbox using a maximum extent
     * @param Bbox
     * @param msExtent
     * @return Bbox
     */
    private function adjustBbox($oldBbox, $maxExtent = NULL) {
     
        $newBbox = $oldBbox;
        
        // Old ratio so we can check ratios
        $oldRatio = $oldBbox->getWidth() / $oldBbox->getHeight();
        
        if (is_null($maxExtent))
            $maxExtent = $this->serverContext->getMaxExtent();
        
        // Horizontal 
        if ($newBbox->minx < $maxExtent->minx) {
            $newBbox->maxx = $newBbox->maxx + $maxExtent->minx 
                             - $newBbox->minx;
            $newBbox->minx = $maxExtent->minx;            
            if ($newBbox->maxx > $maxExtent->maxx) {
                // Bbox was too large to fit
                $newBbox->maxx = $maxExtent->maxx;
            }
        } else if ($newBbox->maxx > $maxExtent->maxx) {
            $newBbox->minx = $newBbox->minx + $maxExtent->maxx 
                             - $newBbox->maxx;
            $newBbox->maxx = $maxExtent->maxx;
            if ($newBbox->minx < $maxExtent->minx) {
                // Bbox was too large to fit
                $newBbox->minx = $maxExtent->minx;
            }
        }

        // Vertical
        if ($newBbox->miny < $maxExtent->miny) {
            $newBbox->maxy = $newBbox->maxy + $maxExtent->miny 
                             - $newBbox->miny;
            $newBbox->miny = $maxExtent->miny;
            if ($newBbox->maxy > $maxExtent->maxy) {
                // Bbox was too high to fit
                $newBbox->maxy = $maxExtent->maxy;
            }
        } else if ($newBbox->maxy > $maxExtent->maxy) {
            $newBbox->miny = $newBbox->miny + $maxExtent->maxy 
                             - $newBbox->maxy;
            $newBbox->maxy = $maxExtent->maxy;
            if ($newBbox->miny < $maxExtent->miny) {
                // Bbox was too high to fit
                $newBbox->miny = $maxExtent->miny;
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

    /** 
     * Applies location changes to MapServer
     * @param Bbox
     * @param double
     */
    private function doLocation($bbox, $scale) {
        $msMapObj = $this->serverContext->getMapObj();

        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);
        
        if ($scale) {            
            $center = ms_newPointObj();
            $center->setXY($msMapObj->width/2, $msMapObj->height/2); 
            $msMapObj->zoomscale($scale, $center,
                        $msMapObj->width, $msMapObj->height, $msMapObj->extent);
        }
    }

    /**
     * Adjusts Bbox
     * @param msExtent
     */
    private function doBboxAdjusting($maxExtent = NULL) {
        $msMapObj = $this->serverContext->getMapObj();

        $bbox = new Bbox();
        $bbox->setFromMsExtent($msMapObj->extent);

        $this->log->debug("bbox before adjusting is");
        $this->log->debug($bbox);

        $newBbox = $this->adjustBbox($bbox, $maxExtent);

        $this->log->debug("bbox after adjusting is");
        $this->log->debug($newBbox);

        // TODO: do not setExtent if the bbox is the same
        $msMapObj->setExtent($newBbox->minx, $newBbox->miny, 
                             $newBbox->maxx, $newBbox->maxy);
    }

    /**
     * Prepares result that will be sent to client
     * @return LocationResult
     */
    private function getLocationResult() {
        $msMapObj = $this->serverContext->getMapObj();
        
        $locationResult = new LocationResult();
        $locationResult->bbox = new Bbox();
        $locationResult->bbox->setFromMsExtent($msMapObj->extent);
        
        $locationResult->scale = round($msMapObj->scale, 4);
        return $locationResult;
    }

    /**
     * @see CoreProvider::handleCorePlugin()
     */
    public function handleCorePlugin($requ) {

        $this->log->debug('handleCorePlugin: ');
        $this->log->debug($requ);

        $this->initScales();
        
        // get calculator from request:
       
        $locationType = $requ->locationType;
        $classPrefix = substr($locationType, 0, -strlen('LocationRequest'));
        $classPrefix = ucfirst($classPrefix);
        $locationCalculatorClass = $classPrefix . 'LocationCalculator';
        if (!class_exists($locationCalculatorClass))
            throw new CartoserverException("Unknown location request: $requ->locationType");
        
        $calculator = new $locationCalculatorClass($this, $requ->$locationType);
        
        $bbox = $calculator->getBbox();
        if (is_null($bbox))
            throw new CartoserverException("null bbox returned from location calculator");
        $this->log->debug("bbox is");
        $this->log->debug($bbox);

        $scale = $calculator->getScale();
        if (is_null($scale)) {
            $scale = $this->getScaleFromBbox($bbox);
        }
        $this->log->debug("scale before adjust is");
        $this->log->debug($scale);
        $scale = $this->adjustScale($scale);
        
        $this->log->debug("scale after adjust is");
        $this->log->debug($scale);

        $this->doLocation($bbox, $scale);

        $maxBbox = NULL;
        if (isset($requ->locationConstraint->maxBbox))
            $maxBbox = $requ->locationConstraint->maxBbox;
        $this->doBboxAdjusting($maxBbox);

        return $this->getLocationResult();
    }
       
    /**
     * @see InitProvider::getInit()
     */
    public function getInit() {

        $this->initScales();

        $shortcuts = ConfigParser::parseObjectArray($this->getConfig(),
                                                    'shortcuts',
                                                    array('label', 'bbox'));
        $locShortcuts = array();
        foreach($shortcuts as $shortcut) {
            $locShortcut = new LocationShortcut();
            $locShortcut->label = $shortcut->label;
            $locShortcut->bbox = new Bbox();
            $locShortcut->bbox->setFromString($shortcut->bbox);
            $locShortcuts[] = $locShortcut;
        }
                                                    
        $init = new LocationInit();
        $init->scales = $this->visibleScales;
        $init->minScale = $this->getConfig()->minScale;
        $init->maxScale = $this->getConfig()->maxScale;
        $init->shortcuts = $locShortcuts;
        return $init;
    }
}

?>
