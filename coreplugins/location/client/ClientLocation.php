<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class LocationState {
    public $bbox;
    public $idRecenterSelected;
}

/**
 * @package CorePlugins
 */
class ClientLocation extends ClientCorePlugin implements ToolProvider {
    private $log;
    private $locationState;

    private $locationRequest;
    private $locationResult;
    
    private $scales;
    
    private $shortcuts;

    const TOOL_ZOOMIN   = 'zoom_in';
    const TOOL_ZOOMOUT  = 'zoom_out';
    const TOOL_PAN      = 'pan';
    const TOOL_RECENTER = 'recenter';

    private $smarty;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
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

    private function handleRecenter() {

        $center = $this->locationState->bbox->getCenter();
        $point = clone($center);       
        if (array_key_exists('recenter_x', $_REQUEST) &&
            array_key_exists('recenter_y', $_REQUEST) &&
            $_REQUEST['recenter_x'] != '' &&
            $_REQUEST['recenter_y'] != '') {
            $point->setXY($_REQUEST['recenter_x'], $_REQUEST['recenter_y']);
        }
        $scale = 0;
        if (array_key_exists('recenter_scale', $_REQUEST) &&
            array_key_exists('recenter_doit', $_REQUEST) &&
            $_REQUEST['recenter_scale'] != '' &&
            $_REQUEST['recenter_doit'] == '1') {
            $scale = $_REQUEST['recenter_scale']; 
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

    private function drawRecenter() {
        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                                              $this);
        $scaleValues = array(0);
        $scaleLabels = array('');
        $scales = $this->scales;
        if (!is_array($scales)) $scales = array();
        foreach ($scales as $scale) {
            $scaleValues[] = $scale->value;
            $scaleLabels[] = I18n::gt($scale->label);            
        }
        $this->smarty->assign(array('recenter_scaleValues' => $scaleValues,
                                    'recenter_scaleLabels' => $scaleLabels,
                                    'recenter_scale'       => 
                                        $this->locationResult->scale));
        return $this->smarty->fetch('recenter.tpl');
    }

    private function handleIdRecenter() {

        $center = $this->locationState->bbox->getCenter();
        $point = clone($center);       
        if (!array_key_exists('id_recenter_ids', $_REQUEST) ||
            $_REQUEST['id_recenter_ids'] == '')
            return NULL;

        $recenterRequest = new RecenterLocationRequest();
        
        $idSelection = new IdSelection();
        $idSelection->layerId = $_REQUEST['id_recenter_layer'];
        $this->locationState->idRecenterSelected = $idSelection->layerId;
        $idSelection->selectedIds = explode(',', $_REQUEST['id_recenter_ids']);
        
        $recenterRequest->idSelections = array($idSelection);
        
        $locationRequest = new LocationRequest();              
        $locationType = $recenterRequest->type;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $recenterRequest;
        
        return $locationRequest;
    }

    private function drawIdRecenter() {
        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                                              $this);

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

    private function handleShortcuts() {
        if (array_key_exists('shortcut_id', $_REQUEST) &&
            array_key_exists('shortcut_doit', $_REQUEST) &&
            $_REQUEST['shortcut_id'] != '' &&
            $_REQUEST['shortcut_doit'] == '1') {
            
            $bboxRequest = new BboxLocationRequest();
            $bboxRequest->bbox = $this->shortcuts[$_REQUEST['shortcut_id']]->bbox;

            $locationRequest = new LocationRequest();                
            $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;
            $locationRequest->bboxLocationRequest = $bboxRequest;
        
            return $locationRequest;        
        } else {
            return NULL;
        }
    }

    private function drawShortcuts() {
        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                                              $this);
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

    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);

        $this->locationState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');

        $this->locationState = new LocationState();
        $this->locationState->bbox = $initialMapState->location->bbox;
    }

    function getLocation() {

        if (!$this->locationState)
            throw new CartoclientException('location state not yet initialized');
        return $this->locationState->bbox;
    }

    function handleHttpRequest($request) {
    
        $this->locationRequest = $this->handlePanButtons();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleKeymapButton();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleRecenter();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleIdRecenter();

        if (!is_null($this->locationRequest))
            return;
        
        $this->locationRequest = $this->handleShortcuts();
        if (!is_null($this->locationRequest))
            return;
        
        $cartoclient = $this->cartoclient;
        $this->locationRequest = $cartoclient->getHttpRequestHandler()
                                    ->handleTools($this);                                   
    }
    
    private function getZoomInFactor(Rectangle $rectangle) {

        $bbox = $this->locationState->bbox;
        
        $widthRatio = $bbox->getWidth() / $rectangle->getWidth();
        $heightRatio = $bbox->getHeight() / $rectangle->getHeight();
        
        return min($widthRatio, $heightRatio);
    }

    private function buildZoomPointRequest($zoomType, Point $point, $zoomFactor=0, $scale=0) {

        $zoomRequest = new ZoomPointLocationRequest();
        $zoomRequest->locationType = LocationRequest::
                                                LOC_REQ_ZOOM_POINT;
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

    function handleMainmapTool(ToolDescription $tool, 
                            Shape $mainmapShape) {

        $toolToZoomType = array(
                self::TOOL_ZOOMIN  => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_IN,
                self::TOOL_PAN => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_NONE,
                self::TOOL_RECENTER => 
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
    
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do */                         
    }

    function getTools() {
        
        return array(new ToolDescription(self::TOOL_ZOOMIN, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_RECTANGLE),
                                         10),
                     new ToolDescription(self::TOOL_ZOOMOUT, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_POINT),
                                         11),
                     new ToolDescription(self::TOOL_PAN, true, 
                        new JsToolAttributes(JsToolAttributes::SHAPE_PAN,
                                             JsToolAttributes::CURSOR_MOVE),
                                         12),
                     // recenter tool is disabled for now.
                     // there should be a way to know if we are in html or 
                     // dhtml mode, and to return the appropriate tools 
                     // accordingly
                     /*
                     new ToolDescription(self::TOOL_RECENTER, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_POINT),
                                          13), */ 
                    );
    }

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

    function handleResult($locationResult) {
        $this->locationState->bbox = $locationResult->bbox;
        $this->locationResult = $locationResult;
    }

    function handleInit($locationInit) {
        $this->scales = $locationInit->scales;
        $this->minScale = $locationInit->minScale;
        $this->maxScale = $locationInit->maxScale;
        $this->shortcuts = $locationInit->shortcuts;
    }
    
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
    
    function renderForm($template) {

        $scaleUnitLimit = $this->getConfig()->scaleUnitLimit;
        if ($scaleUnitLimit && $this->locationResult->scale >= $scaleUnitLimit)
            $factor = 1000;
        else $factor = 1;

        $recenter_active = $this->getConfig()->recenterActive;
        $id_recenter_active = $this->getConfig()->idRecenterActive;
        $shortcuts_active = $this->getConfig()->shortcutsActive;
               
        $template->assign(array('location_info' => $this->getLocationInformation(),
                                'bboxMinX' => $this->locationState->bbox->minx,
                                'bboxMinY' => $this->locationState->bbox->miny,
                                'bboxMaxX' => $this->locationState->bbox->maxx,
                                'bboxMaxY' => $this->locationState->bbox->maxy,
                                'factor' => $factor,
                                'recenter_active' => $recenter_active,
                                'id_recenter_active' => $id_recenter_active,
                                'shortcuts_active' => $shortcuts_active,
                                ));

        if ($recenter_active)
            $template->assign('recenter', $this->drawRecenter());
        if ($id_recenter_active)
            $template->assign('id_recenter', $this->drawIdRecenter());
        if ($shortcuts_active)
            $template->assign('shortcuts', $this->drawShortcuts());
    }

    function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->locationState);

        return $this->locationState;
    }
}
?>
