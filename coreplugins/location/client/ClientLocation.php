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

    const TOOL_ZOOMIN = 'zoom_in';
    const TOOL_ZOOMOUT = 'zoom_out';
    const TOOL_PAN = 'pan';
    const TOOL_RECENTER = 'recenter';

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
                
            $this->locationRequest = $this->buildZoomPointRequest(
                    ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
        }
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);

        $this->locationState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");

        $this->locationState = new LocationState();
        $this->locationState->bbox = $initialMapState->location->bbox;
    }

    function getLocation() {

        if (!$this->locationState)
            throw new CartoclientException("location state not yet initialized");
        return $this->locationState->bbox;
    }

    function handleHttpRequest($request) {
    
        $this->handlePanButtons();
    }
    
    private function getZoomInFactor(Rectangle $rectangle) {

        $bbox = $this->locationState->bbox;
        
        $widthRatio = $bbox->getWidth() / $rectangle->getWidth();
        $heightRatio = $bbox->getHeight() / $rectangle->getHeight();
        
        return min($widthRatio, $heightRatio);
    }

    private function buildZoomPointRequest($zoomType, Point $point, $zoomFactor=0) {

        $zoomRequest = new ZoomPointLocationRequest();
        $zoomRequest->locationType = LocationRequest::
                                                LOC_REQ_ZOOM_POINT;
        $zoomRequest->point = $point; 
        $zoomRequest->zoomType = $zoomType;
        $zoomRequest->zoomFactor = $zoomFactor;
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
        
        return array(new ToolDescription(self::TOOL_ZOOMIN, NULL, 'Zoom in', 
            ToolDescription::MAINMAP),
            new ToolDescription(self::TOOL_ZOOMOUT, NULL, 'Zoom out', 
                ToolDescription::MAINMAP),
            new ToolDescription(self::TOOL_PAN, NULL, 'Pan', 
                ToolDescription::MAINMAP),
            // recenter tool is disabled for now.
            // there should be a way to know if we are in html or dhtml mode, and
            // to return the appropriate tools accordingly
            /* new ToolDescription(self::TOOL_RECENTER, NULL, 'Recenter', 
                ToolDescription::MAINMAP) */
                    );
    }

    function buildMapRequest($mapRequest) {

        $locationRequest = NULL;
        if (!is_null($this->locationRequest)) 
            $locationRequest = $this->locationRequest;
        
        if (is_null($locationRequest)) {
        
            $cartoclient = $this->cartoclient;
            $locationRequest = $cartoclient->getHttpRequestHandler()
                                    ->handleTools($this);
        }
        
        if (is_null($locationRequest)) // stay at the same location
            $locationRequest = $this->buildZoomPointRequest(
                        ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, 
                        $this->locationState->bbox->getCenter());
        $mapRequest->locationRequest = $locationRequest;
    }

    function handleMapResult($mapResult) {
        // TODO: have a generic way of request/result serialisation which
        // sits above the plugin mechanism 

        $mapResult->location
            = Serializable::unserializeObject($mapResult, 'locationResult', 'LocationResult');

        $this->locationState->bbox = $mapResult->location->bbox;
        
        $this->locationResult = $mapResult->location;
    }

    function renderForm($template) {
        
        $locationInfo = sprintf("Bbox: %s scale %s", 
                    $this->locationState->bbox->__toString(),
                    $this->locationResult->scale);
        
        $template->assign('location_info', $locationInfo);
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->locationState);

        return $this->locationState;
    }
}
?>