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

    const TOOL_ZOOMIN = 'zoom_in';
    const TOOL_ZOOMOUT = 'zoom_out';
    const TOOL_PAN = 'pan';
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

            //FIXME: read this from config / mapInfo
            $panRatio = 1.0;
               
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
            array_key_exists('recenter_doit', $_REQUEST) &&
            $_REQUEST['recenter_x'] != '' &&
            $_REQUEST['recenter_y'] != '' &&
            $_REQUEST['recenter_doit'] == '1') {
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
            throw new CartoclientException("location state not yet initialized");
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
            throw new CartoclientException("unknown mainmap tool " . $tool->id);

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
        $weightZoomIn  = $this->getConfig()->weightZoomIn;
        $weightZoomOut = $this->getConfig()->weightZoomOut;
        $weightPan     = $this->getConfig()->weightPan;
        if (!$weightZoomIn) $weightZoomIn = 10;
        if (!$weightZoomOut) $weightZoomOut = 11;
        if (!$weightPan) $weightPan = 12;
        
        return array(new ToolDescription(self::TOOL_ZOOMIN, self::TOOL_ZOOMIN,
                                         'Zoom in', ToolDescription::MAINMAP,
                                         $weightZoomIn, 'location'),
                     new ToolDescription(self::TOOL_ZOOMOUT, self::TOOL_ZOOMOUT,
                                         'Zoom out', ToolDescription::MAINMAP,
                                         $weightZoomOut, 'location'),
                     new ToolDescription(self::TOOL_PAN, self::TOOL_PAN, 'Pan', 
                                         ToolDescription::MAINMAP, $weightPan,
                                         'location'),
                     // recenter tool is disabled for now.
                     // there should be a way to know if we are in html or 
                     // dhtml mode, and to return the appropriate tools 
                     // accordingly
                     /* new ToolDescription(self::TOOL_RECENTER, 
                                            self::TOOL_RECENTER, 'Recenter', 
                                            ToolDescription::MAINMAP,
                                            $weightRecenter, 'location'), */
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
    }
    
    function renderForm($template) {
        
        $locationInfo = sprintf("Bbox: %s scale %s", 
                    $this->locationState->bbox->__toString(),
                    $this->locationResult->scale);

        $recenter_active = $this->getConfig()->recenterActive;

        $scaleUnitLimit = $this->getConfig()->scaleUnitLimit;
        if ($scaleUnitLimit && $this->locationResult->scale >= $scaleUnitLimit)
            $factor = 1000;
        else $factor = 1;
       
        $template->assign(array('location_info' => $locationInfo,
                                'bboxMinX' => $this->locationState->bbox->minx,
                                'bboxMinY' => $this->locationState->bbox->miny,
                                'bboxMaxX' => $this->locationState->bbox->maxx,
                                'bboxMaxY' => $this->locationState->bbox->maxy,
                                'factor' => $factor,
                                'recenter_active' => $recenter_active, 
                                ));
        $template->assign('recenter', $this->drawRecenter());
    }

    function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->locationState);

        return $this->locationState;
    }
}
?>
