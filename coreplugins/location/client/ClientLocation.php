<?php

class ClickInfo {
    const CLICK_POINT = 1;
    const CLICK_RECTANGLE = 2;

    public $type;
    public $pointClick;
    public $rectangleClick;

}

class LocationState {
    // FIXME: maybe use location object

    public $bbox;
    //public $scale;
}

class ClientLocation extends ClientCorePlugin {
    private $log;

    private $locationState;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);

        $this->locationState = $sessionObject;
        
    }

    function createSession($mapInfo) {
        $this->log->debug("creating session:");

        $this->locationState = new LocationState();
        $this->locationState->bbox = $mapInfo->location->bbox;
    }

    function getLocation() {

        if (!$this->locationState)
            throw new CartoclientException("location state not yet initialized");
        return $this->locationState->bbox;
    }

    function handleHttpRequest($request) {

//         $this->log->debug("update form :");
//         $this->log->debug($this->locationState);

        
//         if (!@$request['locations'])
//             $request['locations'] = array();
//         $this->log->debug("requ locations");
//         $this->log->debug($request['locations']);
//         $this->locationState->selectedLocations = $request['locations'];

//         $this->log->debug("selected locations: ");
//         $this->log->debug($this->locationState->selectedLocations);
    }


    private function getMainmapClickedLocationRequest($selectedTool, 
                                                      $currentLocation,
                                                      $mainmapClickInfo) {
    
        $locationRequest = new ZoomPointLocationRequest();

        switch($mainmapClickInfo->type) {

        case ClickInfo::CLICK_POINT:

            $toolToZoomDirection = array(
                CartoForm::TOOL_ZOOMIN => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_IN,
                CartoForm::TOOL_RECENTER => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_NONE,
                CartoForm::TOOL_ZOOMOUT=> 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_OUT);

            $direction = NULL;
            foreach ($toolToZoomDirection as $tool => $dir) {
                if ($selectedTool == $tool) {
                    $direction = $dir;
                    break;
                }
            }

            if ($direction) {
                $locationRequest->locationType = LocationRequest::LOC_REQ_ZOOM_POINT;
            
                //$locationRequest->bbox = $currentLocation->bbox;
                $locationRequest->imagePoint = 
                    $mainmapClickInfo->pointClick;
                $locationRequest->zoomDirection = $direction;
                return $locationRequest;
            }

            x("todo_tool");

            break;
        case ClickInfo::CLICK_RECTANGLE:
            x("todo_rect");
            break;
        default:
            throw new CartoclientException("unknown mainmap clickinfo type");
            break;
        }
    
        return $locationRequest;
    }

    private function getLocationRequest() {

        $cartoclient = $this->cartoclient;

        $locationRequest = new LocationRequest();

        $cartoForm = $cartoclient->getCartoForm();
        $clientSession = $cartoclient->getClientSession();

        if (!$cartoForm)
            x('_no_cartoform_');

        if ($cartoForm->pushedButton == CartoForm::BUTTON_PAN) {
            $locationRequest = new PanLocationRequest();
            $locationRequest->locationType = LocationRequest::LOC_REQ_PAN;

            $locationRequest->panDirection = 
                $cartoForm->panDirection;


        } else if ($cartoForm->pushedButton == CartoForm::BUTTON_MAINMAP) {
            $type = 'panLocationRequest';
        
            $locationRequest = 
                $this->getMainmapClickedLocationRequest($clientSession->selectedTool, 
                                                        $this->locationState->bbox,
                                                        $cartoForm->mainmapClickInfo);
        } else {

            $locationRequest = new BboxLocationRequest();
            $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;
        }

        // the previous bbox
        $locationRequest->bbox = $this->locationState->bbox;

        $outerLocationRequest = new LocationRequest();
        $locationType = $locationRequest->locationType;
        $outerLocationRequest->locationType = $locationType;
        $outerLocationRequest->$locationType = $locationRequest;

        return $outerLocationRequest;
    }

    function buildMapRequest($mapRequest) {

        $mapRequest->locationRequest = $this->getLocationRequest();
    }

    function handleMapResult($mapResult) {

        // TODO: have a generic way of request/result serialisation which
        // sits above the plugin mechanism 

        $mapResult->location = StructHandler::unserialize($mapResult->location, 'LocationResult', 
                                                          StructHandler::CONTEXT_OBJ);

        $this->locationState->bbox = $mapResult->location->bbox;
    }

    function renderForm($template) {
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->locationState);

        return $this->locationState;
    }
}
?>