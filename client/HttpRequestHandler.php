<?php
/**
 * @package Client
 * @version $Id$
 */

/**
 * @package Client
 */
class PixelCoordsConverter {
    private static function pixel2Coord($pixelPos, $pixelMax, $geoMin, $geoMax, 
                                        $inversePix) {

        $geoWidth = $geoMax - $geoMin;

        if ($inversePix)
            $pixelPos = $pixelMax - $pixelPos;

        $factor = $geoWidth / $pixelMax;
        $deltaGeo = $pixelPos * $factor;
        $geoPos = $geoMin + ($pixelPos * $factor);

        return $geoPos;
    }

    /**
     * Converts a point coordinates from pixel to ue to geographical.
     * 
     * Pixel coordinates have their origin on image top left. x grows positively
     * from left to right, and y grows from top to bottom.
     *
     * @param $pixelPoint the point in pixel coordinates
     * @param $imageSize the size of the image containing the pixel point 
     * @param $bbox the geographical bbox extent of the image.
     *
     * @return a point in geographical coordinates
     */

    static function point2Coords(Point $pixelPoint, Dimension $imageSize, Bbox $bbox) {

        $xCoord = self::pixel2Coord($pixelPoint->x, $imageSize->width, 
                                     $bbox->minx, $bbox->maxx, false);
        $yCoord = self::pixel2Coord($pixelPoint->y, $imageSize->height, 
                                     $bbox->miny, $bbox->maxy, true);
        
        return new Point($xCoord, $yCoord);
    }
}

/**
 * @package Client
 */
class CompatibilityDhtml {
    
    const PIXEL_COORD_VAR = 'INPUT_COORD';
    
    static function isMainmapClicked() {
    
        if (HttpRequestHandler::isButtonPushed('mainmap'))
            return true;
        if (!empty($_REQUEST[self::PIXEL_COORD_VAR]))
            return true;
        return ;
    }
    
    function getMainmapShape($cartoForm, Dimension $imageSize, Bbox $bbox) {
        if (HttpRequestHandler::isButtonPushed('mainmap'))
            x('todo_non_dhml_mainmap');
            
        $pixelCoords = $_REQUEST[self::PIXEL_COORD_VAR];
        $coords = explode(',', $pixelCoords);
        $coords = array_map('intval', $coords);
        if (count($coords) != 4)
            throw new CartoclientException("can't parse dhtml coords");
        
        $pixelPoint0 = $pixelPoint = new Point($coords[0], $coords[1]);
        $pixelPoint1 = $pixelPoint = new Point($coords[2], $coords[3]);
        
        $point0 = PixelCoordsConverter::point2Coords($pixelPoint0, $imageSize, $bbox);
        
        if ($coords[0] == $coords[2] && $coords[1] == $coords[3]) {
            return $point0; 
        }
        
        $point1 = PixelCoordsConverter::point2Coords($pixelPoint1, $imageSize, $bbox);
        $rect = new Rectangle();
        $rect->setFrom2Points($point0, $point1);        
        return $rect;
    }
}

/**
 * @package Client
 */
class HttpRequestHandler {
    private $log;

    function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;
    }

    static function isButtonPushed($name) {
        return @$_REQUEST[$name . '_x'] or @$_REQUEST[$name . '_y'];
    }

    private function checkMainmapButton($cartoForm) {

        if (!CompatibilityDhtml::isMainmapClicked())
            return false;
    
        $plugins = $this->cartoclient->getPluginManager();
        
        $mainmapSize = $plugins->images->getMainmapDimensions();
        $mainmapBbox = $plugins->location->getLocation();

        $mainmapShape = CompatibilityDhtml::getMainmapShape(
                        $cartoForm, $mainmapSize, $mainmapBbox);
        
        if ($mainmapShape) {
            $cartoForm->pushedButton = CartoForm::BUTTON_MAINMAP;
            $cartoForm->mainmapShape = $mainmapShape;
            return true;
        }
        
        return false;
    }

    private function checkKeymapButton($cartoForm) {
        
        // TODO: key map button check
        
        return false;
    }

    private function checkClickedButtons($cartoForm) {

        if ($this->checkMainmapButton($cartoForm)) {
            return;
        }
            
        if ($this->checkKeymapButton($cartoForm)) {
            return;
        }
    }

    private function handleTool(ClientPlugin $plugin, ToolDescription $tool) {
    
        $cartoForm = $this->cartoclient->getCartoForm();
        
        if ($cartoForm->pushedButton == CartoForm::BUTTON_MAINMAP) {
            if (!($tool->appliesTo & ToolDescription::MAINMAP)) {
                return NULL;
            }
            return $plugin->handleMainmapTool($tool, 
                            $cartoForm->mainmapShape);
        } else if ($cartoForm->pushedButton == CartoForm::BUTTON_KEYMAP) {
            if (!($tool->appliesTo & ToolDescription::KEYMAP)) {
                return NULL;
            }
            return $plugin->handleKeymapTool($tool, 
                            $cartoForm->keymapShape);
        }
    }

    function handleTools(ClientPlugin $plugin) {
    
        if (!$plugin instanceof ToolProvider) {
            throw new CartoclientException("tool $plugin is not a tool provider");
            return;
        }
        
        if (!@$_REQUEST['tool']) {
            $this->log->debug('no tool selected, skipping');
            return;
        }
        $toolRequest = $_REQUEST['tool'];
        
        $tools = $plugin->getTools();
        foreach ($tools as $tool) {
            $this->log->debug("tool is " . $tool->label);
            $this->log->debug("request " . $toolRequest);
            $this->log->debug("id " . $tool->id);
            if ($toolRequest == $tool->id) {
                return $this->handleTool($plugin, $tool);
            }
        }
        return;
    }

    function handleHttpRequest($clientSession, $cartoForm) {

        // buttons
        $cartoForm->pushedButton = CartoForm::BUTTON_NONE;
        $this->checkClickedButtons($cartoForm);

        // tools
        if (@$_REQUEST['tool'])
            $clientSession->selectedTool = $_REQUEST['tool'];

        return $cartoForm;
    }
}
?>