<?php

class ClickedLocation {
    const CLICK_POINT = 1;
    const CLICK_RECTANGLE = 2;

    public $type;

    public $center;
    public $bbox;
}

class HttpRequestHandler {
    private $log;

    function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;
    }

    private function isButtonPushed($name) {
        return @$_REQUEST[$name . '_x'] or @$_REQUEST[$name . '_y'];
    }

    private function checkPanButton($cartoForm) {

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
            if ($this->isButtonPushed($buttonName)) {
                $cartoForm->pushedButton = CartoForm::BUTTON_PAN;
                $panDirection = new PanDirection();
                $panDirection->verticalPan = $directions[0];
                $panDirection->horizontalPan = $directions[1];
                $cartoForm->panDirection = $panDirection;
                $this->log->debug("pan direction is " . $panDirection->__toString());
                break;
            }
        }
    }
    
    // TO BE REMOVED
    private function XXcheckClickedLocation($cartoForm, $buttonName) {

        if ($this->isButtonPushed($buttonName)) {
        
            $clickInfo = new ClickInfo();
            $clickInfo->type = ClickInfo::CLICK_POINT;

            $clickX = (int)$_REQUEST[$buttonName . '_x'];
            $clickY = (int)$_REQUEST[$buttonName . '_y'];

            $clickInfo->pointClick = new Point($clickX, $clickY);
      
            return $clickInfo;
        }
        return false;
    }

    private function pixel2Coord($pixelPos, $pixelMax, $geoMin, $geoMax, $inversePix) {

        $geoWidth = $geoMax - $geoMin;

        if ($inversePix)
            $pixelPos = $pixelMax - $pixelPos;

        $factor = $geoWidth / $pixelMax;
        $deltaGeo = $pixelPos * $factor;
        $geoPos = $geoMin + ($pixelPos * $factor);

        return $geoPos;
    }

    private function point2Coords(Point $pixelPoint, Dimension $imageSize, Bbox $bbox) {

        $this->log->debug("pixel point");
        $this->log->debug($pixelPoint);
        $this->log->debug("image size");
        $this->log->debug($imageSize);
        $this->log->debug("bbox");
        $this->log->debug($bbox);

        $xCoord = $this->pixel2Coord($pixelPoint->x, $imageSize->width, 
                                     $bbox->minx, $bbox->maxx, false);
        $yCoord = $this->pixel2Coord($pixelPoint->y, $imageSize->height, 
                                     $bbox->miny, $bbox->maxy, true);
        
        return new Point($xCoord, $yCoord);
    }

    private function checkClickedLocation($cartoForm, $buttonName, 
                                          Dimension $imageSize, Bbox $bbox) {

        if ($this->isButtonPushed($buttonName)) {
        
            // TODO: be able to retrieve rectangles

            $clickedLocation = new ClickInfo();
            $clickedLocation->type = ClickInfo::CLICK_POINT;

            $clickX = (int)$_REQUEST[$buttonName . '_x'];
            $clickY = (int)$_REQUEST[$buttonName . '_y'];

            //$clickedLocation->pointClick = new Point($clickX, $clickY);
            $pixelPoint = new Point($clickX, $clickY);

            $clickedLocation->center = $this->point2Coords($pixelPoint, $imageSize, $bbox);

            return $clickedLocation;
        }
        return false;
    }

    private function checkClickedButton($cartoForm) {

        if ($this->checkPanButton($cartoForm))
            return;

        // disabled for now
        if (false) {

            $mainmapSize = $this->cartoclient->pluginManager->images->getMainmapDimensions();
            $mainmapBbox = $this->cartoclient->pluginManager->location->getLocation();
    
            $mainmapClickInfo = $this->checkClickedLocation($cartoForm, 'mainmap', $mainmapSize, $mainmapBbox);

            if ($mainmapClickInfo) {
                $cartoForm->pushedButton = CartoForm::BUTTON_MAINMAP;
                $cartoForm->mainmapClickInfo = $mainmapClickInfo;
                return;
            }
            
            // TODO: wrap in cartoclient
            $keymapBbox = $this->cartoclient->mapInfo->keymap->extent;

            $keymapClickInfo = $this->checkClickedLocation($cartoForm, 'keymap', $keymapBbox);
            if ($keymapClickInfo) {
                $cartoForm->pushedButton = CartoForm::BUTTON_KEYMAP;
                $cartoForm->keymapClickInfo = $keymapClickInfo;
                return;
            }
        }

        // to be removed
        $mainmapClickInfo = $this->XXcheckClickedLocation($cartoForm, 'mainmap');

        if ($mainmapClickInfo) {
            $cartoForm->pushedButton = CartoForm::BUTTON_MAINMAP;
            $cartoForm->mainmapClickInfo = $mainmapClickInfo;
            return;
        }

        $keymapClickInfo = $this->XXcheckClickedLocation($cartoForm, 'keymap');
        if ($keymapClickInfo) {
            $cartoForm->pushedButton = CartoForm::BUTTON_KEYMAP;
            $cartoForm->keymapClickInfo = $keymapClickInfo;
            return;
        }
    }

    function handleHttpRequest($clientSession, $cartoForm) {

        // buttons
        $cartoForm->pushedButton = CartoForm::BUTTON_NONE;
        $this->checkClickedButton($cartoForm);

        // tools
        if (@$_REQUEST['tool'])
            $clientSession->selectedTool = $_REQUEST['tool'];

        return $cartoForm;
    }
}
?>