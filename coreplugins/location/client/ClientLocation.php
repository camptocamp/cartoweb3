<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Client side state
 * @package CorePlugins
 */
class LocationState {
    
    /**
     * @var Bbox
     */
    public $bbox;
    
    /**
     * @var string
     */
    public $idRecenterSelected;
}

/**
 * Client part of Location plugin
 * @package CorePlugins
 */
class ClientLocation extends ClientPlugin
                     implements Sessionable, GuiProvider, ServerCaller,
                                InitUser, ToolProvider, Exportable {
                                
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var LocationState
     */
    private $locationState;

    /**
     * @var LocationRequest
     */
    private $locationRequest;
    
    /**
     * @var LocationResult
     */
    private $locationResult;
    
    /**
     * @var array
     */
    private $scales;
    
    /**
     * @var array
     */
    private $shortcuts;

    const TOOL_ZOOMIN   = 'zoom_in';
    const TOOL_ZOOMOUT  = 'zoom_out';
    const TOOL_PAN      = 'pan';

    /**
     * @var Smarty_CorePlugin
     */
    private $smarty;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
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
     * Handles panning buttons
     * @return LocationRequest
     */
    private function handlePanButtons() {
        $panButtonToDirection = array(
            'pan_nw' => array(PanDirection::VERTICAL_PAN_NORTH, 
                              PanDirection::HORIZONTAL_PAN_WEST),
            'pan_n' => array(PanDirection::VERTICAL_PAN_NORTH, 
                             PanDirection::HORIZONTAL_PAN_NONE),
            'pan_ne' => array(PanDirection::VERTICAL_PAN_NORTH, 
                              PanDirection::HORIZONTAL_PAN_EAST),

            'pan_w' => array(PanDirection::VERTICAL_PAN_NONE, 
                             PanDirection::HORIZONTAL_PAN_WEST),
            'pan_e' => array(PanDirection::VERTICAL_PAN_NONE,
                             PanDirection::HORIZONTAL_PAN_EAST),

            'pan_sw' => array(PanDirection::VERTICAL_PAN_SOUTH,
                              PanDirection::HORIZONTAL_PAN_WEST),
            'pan_s' => array(PanDirection::VERTICAL_PAN_SOUTH,
                             PanDirection::HORIZONTAL_PAN_NONE),
            'pan_se' => array(PanDirection::VERTICAL_PAN_SOUTH,
                              PanDirection::HORIZONTAL_PAN_EAST),
            );
                            
        foreach ($panButtonToDirection as $buttonName => $directions) {
            if (!HttpRequestHandler::isButtonPushed($buttonName))
                continue;
            $verticalPan = $directions[0];                
            $horizontalPan = $directions[1];                

            $panRatio = $this->getConfig()->panRatio;
            if (!$panRatio) {                
                $panRatio = 1.0;
            }
               
            $bbox = $this->locationState->bbox;
            $xOffset = $bbox->getWidth() * $panRatio * 
                $this->panDirectionToFactor($horizontalPan);
            $yOffset = $bbox->getHeight() * $panRatio *
                $this->panDirectionToFactor($verticalPan);

            $center = $bbox->getCenter();
            $point = new Point($center->x + $xOffset,
                         $center->y + $yOffset);
                
            return $this->buildZoomPointRequest(
                    ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
        }
        return NULL;
    }

    /**
     * Handles clicks on key map
     * @return LocationRequest
     */
    private function handleKeymapButton() {

        $cartoForm = $this->cartoclient->getCartoForm();
        
        $keymapShape = $cartoForm->keymapShape; 

        if (is_null($keymapShape))
            return;
        if (!$keymapShape instanceof Point) {
            throw new CartoclientException('shapes other than point ' .
                                           'unsupported for keymap');
            return;   
        } 

        $point = $keymapShape;

        return $this->buildZoomPointRequest(
                  ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
    }

    /**
     * Handles recenter/scales HTTP request
     *
     * When useDoit parameter is true, scale is changed only if a scale 
     * selection was done on form. In this case, a form value ("doit") is
     * set to '1' using Javascript.      
     * @param array HTTP request
     * @param boolean 
     * @return LocationRequest
     */
    private function handleRecenterScales($request,
                                          $useDoit = true, 
                                          $check = false) {

        $center = $this->locationState->bbox->getCenter();
        $point = clone($center); 
        
        $recenterX = $this->getHttpValue($request, 'recenter_x');
        $recenterY = $this->getHttpValue($request, 'recenter_y');

        $scale        = $this->getHttpValue($request, 'recenter_scale');
        $recenterDoit = $this->getHttpValue($request, 'recenter_doit');                            
                            
        if (!is_null($recenterX) && !is_null($recenterY)) {            
            $point->setXY($recenterX, $recenterY);
        }
        
        if ($check) {
            if (!$this->checkNumeric($recenterX, 'recenter_x'))
                return NULL;
            if (!$this->checkNumeric($recenterY, 'recenter_Y'))
                return NULL;
            if (!$this->checkInt($scale, 'recenter_scale'))
                return NULL;

            if ((is_null($recenterX) && !is_null($recenterY)) ||
                (!is_null($recenterX) && is_null($recenterY))) {
                $this->cartoclient->
                    addMessage('Parameters recenter_x and recenter_y cannot be used alone');
                return NULL;
            }
        }
               
        if (is_null($scale) || ($recenterDoit != '1' && $useDoit)) {
            $scale = 0;
        }         
        if ($point == $center && $scale == 0) {
            return NULL;
        }
        
        if ($scale == 0) {
            return $this->buildZoomPointRequest(
                      ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
        } else {
            return $this->buildZoomPointRequest(
                      ZoomPointLocationRequest::ZOOM_SCALE, $point, 0, $scale);        
        }
    }

    /**
     * Draws recenter form
     * @return string
     */
    private function drawRecenter() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        return $this->smarty->fetch('recenter.tpl');
    }

    /**
     * Draws scales form
     * @return string
     */
    private function drawScales() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $scaleValues = array(0);
        $scaleLabels = array('');
        $scales = $this->scales;
        if (!is_array($scales)) $scales = array();
        
        $noScales = count($scales) == 0;        
        foreach ($scales as $scale) {
            $scaleValues[] = $scale->value;
            $scaleLabels[] = I18n::gt($scale->label);            
        }
        $this->smarty->assign(array('recenter_noscales'    => $noScales,
                                    'recenter_scaleValues' => $scaleValues,
                                    'recenter_scaleLabels' => $scaleLabels,
                                    'recenter_scale'       => 
                                        $this->locationResult->scale));
        return $this->smarty->fetch('scales.tpl');
    }

    /**
     * Handles recenter on Ids HTTP request
     * @param array HTTP request
     * @param boolean 
     * @return LocationRequest
     */
    private function handleIdRecenter($request, $check = false) {

        $center = $this->locationState->bbox->getCenter();
        $point = clone($center);
        
        $idRecenterLayer = $this->getHttpValue($request, 'id_recenter_layer');
        $idRecenterIds   = $this->getHttpValue($request, 'id_recenter_ids');    
               
        if (is_null($idRecenterLayer) || is_null($idRecenterIds)) {
            return NULL;
        }
        
        $ids = explode(',', $idRecenterIds);
        
        if ($check) {
            $found = false;
            foreach($this->cartoclient->getMapInfo()->getLayers() as $layer) {
                if (! $layer instanceof Layer) {
                    continue;
                }
                if ($idRecenterLayer == $layer->id) {
                    $found = true;
                }
            }
            if (!$found) {
                $this->cartoclient->addMessage('ID recenter layer not found');
                return NULL;
            }
        }
        
        $recenterRequest = new RecenterLocationRequest();
        
        $idSelection = new IdSelection();
        $idSelection->layerId = $idRecenterLayer;
        $this->locationState->idRecenterSelected = $idSelection->layerId;
        $idSelection->selectedIds = $ids;
        
        $recenterRequest->idSelections = array($idSelection);
        
        $locationRequest = new LocationRequest();              
        $locationType = $recenterRequest->type;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $recenterRequest;
        
        return $locationRequest;
    }

    /**
     * Draws recenter on Ids form
     * @return string
     */
    private function drawIdRecenter() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);

        $mapInfo = $this->cartoclient->getMapInfo();
        $layersId = array();
        $layersLabel = array();
        foreach($mapInfo->getLayers() as $layer) {
            if (! $layer instanceof Layer)
                continue;
            $layersId[] = $layer->id; 
            $layersLabel[] = I18n::gt($layer->label); 
        }

        if (!empty($this->locationState->idRecenterSelected))
            $idRecenterSelected = $this->locationState->idRecenterSelected;
        else
            $idRecenterSelected = $layersId[0];

        $this->smarty->assign(array('id_recenter_layers_id' => $layersId,
                                    'id_recenter_layers_label' => $layersLabel,
                                    'id_recenter_selected' => $idRecenterSelected));
        return $this->smarty->fetch('id_recenter.tpl');
    }

    /**
     * Handles shortcuts HTTP request
     * @param array HTTP request
     * @param boolean
     * @param boolean
     * @return LocationRequest
     */
    private function handleShortcuts($request, $useDoit = true, $check = false) {
        
        $shortcut_id  = $this->getHttpValue($request, 'shortcut_id');
        $shortcutDoit = $this->getHttpValue($request, 'shortcut_doit');                            

        if (is_null($shortcut_id) || ($shortcutDoit != '1' && $useDoit)) {
            return NULL;
        }
        
        if ($check) {
            if (!$this->checkInt($shortcut_id, 'shortcut_id'))
                return NULL;
                
            if (!array_key_exists($shortcut_id, $this->shortcuts)) {
                $this->cartoclient->addMessage('Shortcut ID not found');
                return NULL;
            }
        }
                   
        $bboxRequest = new BboxLocationRequest();
        $bboxRequest->bbox = $this->shortcuts[$request['shortcut_id']]->bbox;

        $locationRequest = new LocationRequest();                
        $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;
        $locationRequest->bboxLocationRequest = $bboxRequest;
        
        return $locationRequest;        
    }

    /**
     * Draws shortcuts form
     * @return string
     */
    private function drawShortcuts() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $shortcutValues = array(-1);
        $shortcutLabels = array('');
        $shortcuts = $this->shortcuts;
        if (!is_array($shortcuts)) $shortcuts = array();
        foreach ($shortcuts as $key => $shortcut) {
            $shortcutValues[] = $key;
            $shortcutLabels[] = I18n::gt($shortcut->label);            
        }
        $this->smarty->assign(array('shortcut_values' => $shortcutValues,
                                    'shortcut_labels' => $shortcutLabels));
        return $this->smarty->fetch('shortcuts.tpl');
    }

    /**
     * Handles recenter on bbox HTTP request
     * @param array HTTP request
     * @param boolean
     * @return LocationRequest
     */
    private function handleBboxRecenter($request, $check = false) {
        
        $recenterBbox = $this->getHttpValue($request, 'recenter_bbox');
        if (is_null($recenterBbox)) {
            return NULL;
        }
       
        if ($check) {
        
            $values = explode(',', $recenterBbox);
            if (count($values) != 4) {
                $this->cartoclient->
                    addMessage('Parameter recenter_bbox should be 4 values separated by commas');
                return NULL;
            }
            list($minx, $miny, $maxx, $maxy) = $values;
            if (!$this->checkNumeric($minx, 'recenter_bbox (minx)'))
                return NULL;
            if (!$this->checkNumeric($miny, 'recenter_bbox (miny)'))
                return NULL;
            if (!$this->checkNumeric($maxx, 'recenter_bbox (maxx)'))
                return NULL;
            if (!$this->checkNumeric($maxy, 'recenter_bbox (maxy)'))
                return NULL;
            
            if ($minx >= $maxx) {
                $this->cartoclient->
                    addMessage('Parameter recenter_bbox minx must be < maxx');
                return NULL;
            }
            if ($miny >= $maxy) {
                $this->cartoclient->
                    addMessage('Parameter recenter_bbox miny must be < maxy');
                return NULL;
            }
            $bbox = new Bbox($minx, $miny, $maxx, $maxy);
        }

        $bboxRequest = new BboxLocationRequest();
        $bboxRequest->bbox = $bbox;
        $locationRequest = new LocationRequest();                
        $locationType = $bboxRequest->type;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $bboxRequest;
        
        return $locationRequest;
    }

    /**
     * @see Sessionable::loadSession()
     */
    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);

        $this->locationState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');

        $this->locationState = new LocationState();
        $this->locationState->bbox = $initialMapState->location->bbox;
    }

    /**
     * Returns current bbox
     * @return Bbox
     */
    function getLocation() {

        if (!$this->locationState)
            throw new CartoclientException('location state not yet initialized');
        return $this->locationState->bbox;
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    function handleHttpPostRequest($request) {
    
        $this->locationRequest = $this->handlePanButtons();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleKeymapButton();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleRecenterScales($request);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleIdRecenter($request);
        if (!is_null($this->locationRequest))
            return;
        
        $this->locationRequest = $this->handleShortcuts($request);
        if (!is_null($this->locationRequest))
            return;
        
        $cartoclient = $this->cartoclient;
        $this->locationRequest = $cartoclient->getHttpRequestHandler()
                                    ->handleTools($this);  
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    function handleHttpGetRequest($request) {

        $this->locationRequest = $this->handleBboxRecenter($request, true);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleRecenterScales($request,
                                                             false, true);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleIdRecenter($request, true);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleShortcuts($request, false, true);
        if (!is_null($this->locationRequest))
            return;
    }
    
    /**
     * Returns zoom factor depending on selected rectangle
     * @param Rectangle
     * @return double
     */
    private function getZoomInFactor(Rectangle $rectangle) {

        $bbox = $this->locationState->bbox;
        
        $widthRatio = $bbox->getWidth() / $rectangle->getWidth();
        $heightRatio = $bbox->getHeight() / $rectangle->getHeight();
        
        return min($widthRatio, $heightRatio);
    }

    /**
     * Constructs a {@link ZoomPointLocationRequest}
     * @param string
     * @param Point
     * @param double
     * @param double
     * @return LocationRequest
     */
    private function buildZoomPointRequest($zoomType, Point $point, $zoomFactor=0, $scale=0) {

        $zoomRequest = new ZoomPointLocationRequest();
        $zoomRequest->locationType = LocationRequest::LOC_REQ_ZOOM_POINT;
        $zoomRequest->point = $point; 
        $zoomRequest->zoomType = $zoomType;
        $zoomRequest->zoomFactor = $zoomFactor;
        $zoomRequest->scale = $scale;
        $zoomRequest->bbox = $this->locationState->bbox;

        $locationRequest = new LocationRequest();                
        $locationType = $zoomRequest->locationType;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $zoomRequest;
        
        return $locationRequest;
    }

    /**
     * @see ToolProvider::handleMainmapTool() 
     */
    function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {

        $toolToZoomType = array(
                self::TOOL_ZOOMIN  => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_IN,
                self::TOOL_PAN => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_NONE,
                self::TOOL_ZOOMOUT=> 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_OUT);

        $zoomType = @$toolToZoomType[$tool->id];
        if (empty($zoomType))
            throw new CartoclientException('unknown mainmap tool ' . $tool->id);

        $point = $mainmapShape->getCenter();

        $zoomFactor = 0;
        if ($tool->id == self::TOOL_ZOOMIN && $mainmapShape instanceof Rectangle) {
            $zoomType = ZoomPointLocationRequest::ZOOM_FACTOR;
            $zoomFactor = $this->getZoomInFactor($mainmapShape);
        }
        
        return $this->buildZoomPointRequest($zoomType, $point, $zoomFactor);
    }
    
    /**
     * @see ToolProvider::handleKeymapTool() 
     */
    function handleKeymapTool(ToolDescription $tool, 
                              Shape $keymapShape) {
        /* nothing to do */                         
    }

    /**
     * @see ToolProvider::getTools() 
     */
    function getTools() {
        
        return array(new ToolDescription(self::TOOL_ZOOMIN, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_RECTANGLE_OR_POINT),
                                         10),
                     new ToolDescription(self::TOOL_ZOOMOUT, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_POINT),
                                         11),
                     new ToolDescription(self::TOOL_PAN, true, 
                        new JsToolAttributes(JsToolAttributes::SHAPE_PAN,
                                             JsToolAttributes::CURSOR_MOVE),
                                         12),
                   );
    }

    /**
     * @see ServerCaller::buildMapRequest()
     */
    function buildMapRequest($mapRequest) {

        $locationRequest = NULL;
        if (!is_null($this->locationRequest)) 
            $locationRequest = $this->locationRequest;
        
        if (is_null($locationRequest)) // stay at the same location
            $locationRequest = $this->buildZoomPointRequest(
                        ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, 
                        $this->locationState->bbox->getCenter());
        $mapRequest->locationRequest = $locationRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    function initializeResult($locationResult) {
        $this->locationState->bbox = $locationResult->bbox;
        $this->locationResult = $locationResult;
    }

    /**
     * @see ServerCaller::handleResult()
     */
    function handleResult($locationResult) {}

    /**
     * Returns current scale
     * @return float
     */
    function getCurrentScale() {
        return $this->locationResult->scale;
    }

    /**
     * @see InitUser::handleInit()
     */
    function handleInit($locationInit) {
        $this->scales = $locationInit->scales;
        $this->minScale = $locationInit->minScale;
        $this->maxScale = $locationInit->maxScale;
        $this->shortcuts = $locationInit->shortcuts;
    }
    
    /**
     * Returns a string with some location information (scale, bbox, etc.)
     * @return string
     */
    private function getLocationInformation() {
        
        $delta = $this->maxScale - $this->minScale;
        if ($delta > 0) {
            $percent = (($this->locationResult->scale - $this->minScale) * 100) /
                        ($this->maxScale - $this->minScale);
            $percent = round($percent, 1);
        } else {
            $percent = '#ERR';
        }
        
        $locationInfo = sprintf('Bbox: %s  <br/> scale: min:%s current: %s ' .
                                'max: %s (percent: %s)', 
                    $this->locationState->bbox->__toString(),
                    $this->minScale, $this->locationResult->scale, 
                    $this->maxScale, $percent);
        
        return $locationInfo;
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    function renderForm(Smarty $template) {

        $scaleUnitLimit = $this->getConfig()->scaleUnitLimit;
        if ($scaleUnitLimit && $this->locationResult->scale >= $scaleUnitLimit)
            $factor = 1000;
        else $factor = 1;

        $recenter_active = $this->getConfig()->recenterActive;
        $scales_active = $this->getConfig()->scalesActive;
        $id_recenter_active = $this->getConfig()->idRecenterActive;
        $shortcuts_active = $this->getConfig()->shortcutsActive;
               
        $template->assign(array('location_info' => $this->getLocationInformation(),
                                'bboxMinX' => $this->locationState->bbox->minx,
                                'bboxMinY' => $this->locationState->bbox->miny,
                                'bboxMaxX' => $this->locationState->bbox->maxx,
                                'bboxMaxY' => $this->locationState->bbox->maxy,
                                'factor' => $factor,
                                'recenter_active' => $recenter_active,
                                'scales_active' => $scales_active,
                                'id_recenter_active' => $id_recenter_active,
                                'shortcuts_active' => $shortcuts_active,
                                ));

        if ($recenter_active)
            $template->assign('recenter', $this->drawRecenter());
        if ($scales_active)
            $template->assign('scales', $this->drawScales());
        if ($id_recenter_active)
            $template->assign('id_recenter', $this->drawIdRecenter());
        if ($shortcuts_active)
            $template->assign('shortcuts', $this->drawShortcuts());
    }

    /**
     * @see Sessionable::saveSession()
     */
    function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->locationState);

        return $this->locationState;
    }

    /**
     * @see Exportable::adjustExportMapRequest()
     */
    function adjustExportMapRequest(ExportConfiguration $configuration,
                                    MapRequest $mapRequest) {

        $locationRequest = $mapRequest->locationRequest;

        $type = $configuration->getLocationType();
        if (!is_null($type)) {
            $locationRequest->locationType = $type;

            $locationType = $locationRequest->locationType;
  
            $locationRequests = array('bboxLocationRequest',
                                      'panLocationRequest', 
                                      'zoomPointLocationRequest',
                                      'recenterLocationRequest');
            // FIXME: what if some new kind of request is added?
            foreach ($locationRequest as $name => $member) {
                if (in_array($name, $locationRequests)) 
                    $locationRequest->$name = NULL;
            }
   
            switch($locationType) {
                case 'zoomPointLocationRequest':
                    $locationRequest->$locationType = 
                        new ZoomPointLocationRequest;
                    
                    $bbox = $configuration->getBbox();
                    if (!is_null($bbox))
                        $locationRequest->$locationType->bbox = $bbox;
       
                    $point = $configuration->getPoint();
                    if (!is_null($point))
                        $locationRequest->$locationType->point = $point;
       
                    $scale = $configuration->getScale();
                    if (!is_null($scale))
                        $locationRequest->$locationType->scale = $scale;
       
                    $zoomType = $configuration->getZoomType();
                    // FIXME: use given zoomType instead of ZOOM_SCALE
                    if (!is_null($zoomType))
                        $locationRequest->$locationType->zoomType = 
                            ZoomPointLocationRequest::ZOOM_SCALE;
                    break;
            
            }
        }
    }
}

?>
