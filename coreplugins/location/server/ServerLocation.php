<?
/**
 * @package CorePlugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');
require_once('log4php/LoggerManager.php');

/**
 * Base abstract class for classes used to calculate bboxe's and scales.
 * There is a one to one mapping between LocationRequests and these
 * LocationCalculator's.
 * 
 * @package CorePlugins
 */
abstract class LocationCalculator {
    private $log;
    public $locationPlugin;
    public $requ;
    function __construct($locationPlugin, $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->locationPlugin = $locationPlugin;
        $this->requ = $requ;
    }
    abstract function getBbox();
    abstract function getScale();
}

/**
 * @package CorePlugins
 */
class BboxLocationCalculator extends LocationCalculator {
    private $log;

    function __construct($locationPlugin, BboxLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
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
    private $panRatio;

    function __construct($locationPlugin, PanLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
        
        $this->panRatio = $this->locationPlugin->getConfig()->panRatio;
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

    function getScale() {
        return NULL;
    }
}

/**
 * @package CorePlugins
 */
class ZoomPointLocationCalculator extends LocationCalculator {
    private $log;
    private $scaleModeDiscrete;

    function __construct($locationPlugin, ZoomPointLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
        
        $this->scaleModeDiscrete = $this->locationPlugin->getConfig()->scaleModeDiscrete;
        $this->zoomFactor = $this->locationPlugin->getConfig()->zoomFactor;
        if (!$this->zoomFactor)
            $this->zoomFactor = 2.0;
        $this->scales = $this->locationPlugin->getScales();
    }

    function getBbox() {
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

    function getPreviousScale($oldScale) {
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
    
    function getNextScale($oldScale) {
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

    private function calculateOldScale() {
        $msMapObj = $this->locationPlugin->getServerContext()->msMapObj;
        
        $oldBbox = $this->requ->bbox;
        $msMapObj->setExtent($oldBbox->minx, $oldBbox->miny, 
                             $oldBbox->maxx, $oldBbox->maxy);
        $oldScale = $msMapObj->scale;
        return $oldScale;
    }

    function getScale() {
        
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
 * @package CorePlugins
 */
// TODO: maybe put in a file of it's own
class RecenterLocationCalculator extends LocationCalculator {
    private $log;
    private $useDefaultScale;

    function __construct($locationPlugin, RecenterLocationRequest $requ) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct($locationPlugin, $requ);
    }

    private function genericQueryString($idAttribute, $idType, $selectedIds) {
        // FIXME: does queryByAttributes support multiple id's for dbf ?
        $queryString = array();
        foreach($selectedIds as $id) {
            if ($idType == 'string')
                $queryString[] = "'$id'";
            else
                /* TODO */ x('todo_int_query_string');
        } 
        return $queryString;
    }
    
    private function databaseQueryString($idAttribute, $idType, $selectedIds) {
        if ($idType != 'string')
            x('todo_database_int_query_string');
        $queryString = implode("','", $selectedIds);
        return "$idAttribute in ('$queryString')";
    }

    private function isDatabaseLayer($msLayer) {
        /* TODO */
        return false;
    }

    private function queryLayerByAttributes($msLayer, $idAttribute, $query) { 
        
        $this->log->debug("queryLayerByAttributes $msLayer->name $idAttribute $query");
        $ret = @$msLayer->queryByAttributes($idAttribute, $query, MS_MULTIPLE);
        if ($ret == MS_FAILURE) {
                $this->log->warn("no record found on ms layer $msLayer->name, or " .
                    "an error happened");
            return NULL;   
        }

        $this->locationPlugin->getServerContext()->checkMsErrors();
        $msLayer->open();
        $bboxes = array();
        for ($i = 0; $i < $msLayer->getNumResults(); $i++) {
            $result = $msLayer->getResult($i);
            $shape = $msLayer->getShape($result->tileindex, $result->shapeindex);

            $bbox = new Bbox();
            $bbox->setFromMsExtent($shape->bounds);
            $bboxes[] = $bbox;
        }
        if (empty($bboxes)) {
            $this->log->warn('no bbox found in query results');
            $msLayer->close();            
            return NULL;
        }
        $this->log->debug('bboxes');
        $this->log->debug($bboxes);
        $bbox = $this->mergeBboxes($bboxes);
        $msLayer->close();
        return $bbox;       
    }

    private function getIdSelectionBbox(IdSelection $idSelection) {

        $serverContext = $this->locationPlugin->getServerContext();
        $mapInfo = $this->locationPlugin->getServerContext()->getMapInfo();
        $msLayer = $mapInfo->getMsLayerById($serverContext->msMapObj, 
                                            $idSelection->layerId);
        
        $idAttribute = $idSelection->idAttribute;
        if (is_null($idAttribute)) {
            $idAttribute = $serverContext->getIdAttribute($idSelection->layerId);
        }
        if (is_null($idAttribute)) {
            throw new CartoserverException("can't find idAttribute for layer " .
                    "$idSelection->layerId");
        }
        $idType = $idSelection->idType;
        if (is_null($idType)) {
            $idType = $serverContext->getIdAttributeType($idSelection->layerId);
        }

        $queryStringFunction = ($this->isDatabaseLayer($msLayer)) ?
            'databaseQueryString' : 'genericQueryString';

        // FIXME: can shapefiles support queryString for multiple id's ?
        //  if yes, then improve this handling. 

        $queryString = $this->$queryStringFunction($idAttribute, $idType, 
                                                $idSelection->selectedIds);
        $bboxes = array();
        foreach($queryString as $query) {
            $bbox = $this->queryLayerByAttributes($msLayer, $idAttribute, $query);
            if (!is_null($bbox))
                $bboxes[] = $bbox; 
        }
        if (empty($bboxes))
            return NULL;
        return $this->mergeBboxes($bboxes);
    }

    function mergeBboxes($bboxes) {
        if (empty($bboxes))
            throw new CartoserverException("trying to merge empty bboxes");
        if (count($bboxes) == 1)
            return $bboxes[0]; 
        $mergedBbox = $bboxes[0];
        foreach(array_slice($bboxes, 1) as $bbox) { 
            $mergedBbox->minx = min($bbox->minx, $mergedBbox->minx);
            $mergedBbox->maxy = max($bbox->maxy, $mergedBbox->maxy);
            $mergedBbox->miny = min($bbox->miny, $mergedBbox->miny);
            $mergedBbox->maxy = max($bbox->maxy, $mergedBbox->maxy);
        }
        return $mergedBbox;        
    }

    function addMargin(Bbox $bbox, $margin) {
        
        $width = $bbox->getWidth();
        $xDelta = $width * ($margin / 100);
        $height = $bbox->getHeight();
        $yDelta = $width * ($margin / 100);
        return new Bbox($bbox->minx - $xDelta, $bbox->miny - $yDelta,
                         $bbox->maxx + $xDelta, $bbox->maxy + $yDelta);
    }

    /**
     * Adds a border to a bbox. 
     * Used to transform zero sized (width or height is zero) bboxes 
     *  to non zero sized ones.
     */
    private function addBboxBorders($bbox) {
     
        // FIXME: is there a better way than using this constant ? 
        $border = 1.0;
        $bbox = new Bbox($bbox->minx - $border, $bbox->miny - $border,
                         $bbox->minx + $border, $bbox->miny + $border);
        return $bbox;
    }

    function getBbox() {

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

    function getScale() {
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
    
    public function getScales() {
        
        if (is_null($this->scales))
            throw new CartoserverException("scales not initialized");
        return $this->scales;
    }

    
    private function getScaleFromBbox($bbox) {
        $msMapObj = $this->getServerContext()->msMapObj;
        
        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);
        $scale = $msMapObj->scale;
        return $scale;
    }
    
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

    private function adjustBbox($oldBbox) {
     
        $newBbox = $oldBbox;
        
        // Old ratio so we can check ratios
        $oldRatio = $oldBbox->getWidth() / $oldBbox->getHeight();
        
        $maxExtent = $this->serverContext->maxExtent;
        
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

    private function doLocation($bbox, $scale) {
        $msMapObj = $this->serverContext->msMapObj;

        $msMapObj->setExtent($bbox->minx, $bbox->miny, 
                             $bbox->maxx, $bbox->maxy);
        
        if ($scale) {            
            $center = ms_newPointObj();
            $center->setXY($msMapObj->width/2, $msMapObj->height/2); 
            $msMapObj->zoomscale($scale, $center,
                        $msMapObj->width, $msMapObj->height, $msMapObj->extent);
        }
    }

    private function getLocationResult() {
        $msMapObj = $this->serverContext->msMapObj;
        
        $locationResult = new LocationResult();
        $locationResult->bbox = new Bbox();
        $locationResult->bbox->setFromMsExtent($msMapObj->extent);
        
        $locationResult->scale = round($msMapObj->scale, 4);
        return $locationResult;
    }

    function handleCorePlugin($requ) {

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

        // FIXME: should bbox be adjusted after setting scale ?
        $bbox = $this->adjustBbox($bbox);

        $this->log->debug("bbox after adjusting is");
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

        return $this->getLocationResult();
    }
    
    function getInitValues() {

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
