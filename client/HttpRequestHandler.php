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
 * Parses dhtml HTTP Requests, and returns a shape for the drawn selection. 
 *
 * @package Client
 */
class DhtmlSelectionParser {
    
    const SELECTION_TYPE = 'selection_type';
    const SELECTION_COORDS = 'selection_coords';
    
    static function isMainmapClicked() {
    
        if (HttpRequestHandler::isButtonPushed('mainmap'))
            return true;
        if (!empty($_REQUEST[self::SELECTION_COORDS]))
            return true;
        return ;
    }
    
    private function pixelToPoint($pixel_coord, 
            Dimension $imageSize, Bbox $bbox) {
        
        list($x, $y) = explode(',', $pixel_coord);        
        $pixelPoint = new Point($x, $y);
        return PixelCoordsConverter::point2Coords($pixelPoint, $imageSize, $bbox);
    }
    
    private function coordsToPoints($selection_coords, 
            Dimension $imageSize, Bbox $bbox) {
     
        $coords = explode(';', $selection_coords);
        $points = array();
        foreach($coords as $coord) {
            $point = self::pixelToPoint($coord, $imageSize, $bbox);     
            $points[] = $point;
        }        
        return $points; 
    }
    
    private function getRectangleShape(Dimension $imageSize, Bbox $bbox) {
        
        $points = self::coordsToPoints($_REQUEST[self::SELECTION_COORDS],
                                       $imageSize, $bbox);
        if (count($points) != 2)
            throw new CartoclientException("can't parse rectangle dhtml coords");
        
        // if the two coordinates are the same, then return a point
        if ($points[0] == $points[1]) {
            return $points[0];
        }
        
        $rect = new Rectangle();
        $rect->setFrom2Points($points[0], $points[1]);        
        return $rect;        
    }
       
    private function getPolygonShape(Dimension $imageSize, Bbox $bbox) {
        
        $points = self::coordsToPoints($_REQUEST[self::SELECTION_COORDS],
                                       $imageSize, $bbox);
        if (count($points) == 0)
            throw new CartoclientException("can't parse polygon dhtml coords");
        
        // if only one point then return a point
        if (count($points) == 1) {
            return $points[0];
        }
        
        $poly = new Polygon();
        $poly->points = $points;        
        return $poly;        
    }

    private function getPointShape(Dimension $imageSize, Bbox $bbox) {

        $points = self::coordsToPoints($_REQUEST[self::SELECTION_COORDS],
                                       $imageSize, $bbox);
        if (count($points) != 1)
            throw new CartoclientException("can't parse point dhtml coords");
        
        return $points[0];
    }
    
    function getMainmapShape($cartoForm, Dimension $imageSize, Bbox $bbox) {

        if (HttpRequestHandler::isButtonPushed('mainmap')) {
            $point = HttpRequestHandler::getButtonPixelPoint('mainmap');
            $_REQUEST[self::SELECTION_TYPE] = 'point';
            $_REQUEST[self::SELECTION_COORDS] = sprintf('%d,%d', $point->x, 
                                                                 $point->y);
        }

        $type = $_REQUEST[self::SELECTION_TYPE];
        if ($type == 'point') 
            return self::getPointShape($imageSize, $bbox); 
        else if ($type == 'rectangle') 
            return self::getRectangleShape($imageSize, $bbox);
        else if ($type == 'polygon')
            return self::getPolygonShape($imageSize, $bbox); 
        else
            throw new CartoclientException("unknown selection_type: $type");
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

    static function getButtonPixelPoint($buttonName) {
        $x = $_REQUEST[$buttonName . '_x'];
        $y = $_REQUEST[$buttonName . '_y'];
        
        return new Point($x, $y);
    }

    private function checkMainmapButton($cartoForm) {

        if (!DhtmlSelectionParser::isMainmapClicked())
            return false;
    
        $plugins = $this->cartoclient->getPluginManager();
        
        $mainmapSize = $plugins->images->getMainmapDimensions();
        $mainmapBbox = $plugins->location->getLocation();

        $mainmapShape = DhtmlSelectionParser::getMainmapShape(
                        $cartoForm, $mainmapSize, $mainmapBbox);
        
        if ($mainmapShape) {
            $cartoForm->pushedButton = CartoForm::BUTTON_MAINMAP;
            $cartoForm->mainmapShape = $mainmapShape;
            return true;
        }
        
        return false;
    }

    private function checkKeymapButton($cartoForm) {
        
        if (!self::isButtonPushed('keymap'))
            return false;  

        $pixelPoint = $this->getButtonPixelPoint('keymap');

        $mapInfo = $this->cartoclient->getMapInfo();
        $keymapGeoDimension = $mapInfo->keymapGeoDimension;

        $point = PixelCoordsConverter::point2Coords($pixelPoint, 
                    $keymapGeoDimension->dimension,
                    $keymapGeoDimension->bbox);

        $cartoForm->pushedButton = CartoForm::BUTTON_KEYMAP;
        $cartoForm->keymapShape = $point;

        return true;
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
        
        $tools = $plugin->doGetTools();
        foreach ($tools as $tool) {
            $this->log->debug("tool is " . $tool->id);
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
        if (@$_REQUEST['tool']) {
            $tool = explode(',', $_REQUEST['tool']);
            $_REQUEST['tool'] = array_pop($tool); 
            $clientSession->selectedTool = $_REQUEST['tool'];
        }

        return $cartoForm;
    }
}
?>
