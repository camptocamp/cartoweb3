<?php
/**
 * Classes useful to manage HTTP request posted by GUI form
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Client
 * @version $Id$
 */

/**
 * Converter from pixel to geographical coordinates
 * @package Client
 */
class PixelCoordsConverter {

    /**
     * Computes pixel to geographical coordinates transformation
     * @param double pixel coordinate
     * @param double maximum pixel coordinate
     * @param double minimum geographical coordinate
     * @param double maximum geographical coordinate
     * @param boolean true if pixel coordinates are reversed
     * @return double geographical coordinate
     */
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
     * Converts a point coordinates from pixel to geographical.
     * 
     * Pixel coordinates have their origin on image top left. x grows positively
     * from left to right, and y grows from top to bottom.
     * @param Point the point in pixel coordinates
     * @param Dimension the size of the image containing the pixel point 
     * @param Bbox the geographical bbox extent of the image.
     * @return Point point in geographical coordinates
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
 * @package Client
 */
class DhtmlSelectionParser {
    
    const SELECTION_TYPE = 'selection_type';
    const SELECTION_COORDS = 'selection_coords';
    
    /**
     * Returns true if main map was clicked
     * @return boolean
     */
    static function isMainmapClicked() {
    
        if (HttpRequestHandler::isButtonPushed('mainmap'))
            return true;
        if (!empty($_REQUEST[self::SELECTION_COORDS]))
            return true;
        return ;
    }
    
    /**
     * Parses coord data and converts it to Point
     * @param string serialized coordinates
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return Point point in geographical coordinates
     */
    private function coordToPoint($coord, 
            Dimension $imageSize, Bbox $bbox) {
        
        list($x, $y) = explode(',', $coord);        
        $point = new Point($x, $y);
        if ($this->cartoclient->getConfig()->noDhtml) {
            // if no DHTML, assumed that coords given in pixels
            return PixelCoordsConverter::point2Coords($point, $imageSize, $bbox);
        }
        else
            return $point;
    }
    
    /**
     * Parses coords array data and converts it to an array of Point
     * @param string serialized pixel coordinates
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return array array of {@link Point} in geographical coordinates
     */
    private function coordsToPoints($selection_coords, 
            Dimension $imageSize, Bbox $bbox) {
     
        $coords = explode(';', $selection_coords);
        $points = array();
        foreach($coords as $coord) {
            $point = self::coordToPoint($coord, $imageSize, $bbox);     
            $points[] = $point;
        }        
        return $points; 
    }

    /**
     * Parses coords array data stored in $_REQUEST and converts it to a Line
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return Line line in geographical coordinates
     */
    private function getLineShape(Dimension $imageSize, Bbox $bbox) {

        $points = self::coordsToPoints($_REQUEST[self::SELECTION_COORDS], 
            $imageSize, $bbox);
        if (count($points) == 0)
            throw new CartoclientException("can't parse line dhtml coords");
                
        // if only one point then return a point
        if (count($points) == 1) {
            return $points[0];
        }
                        
        $line = new Line();
        $line->points = $points;        
        return $line;        
    }
    
    /**
     * Parses coords array data stored in $_REQUEST and converts it to a Rectangle
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return Rectangle rectangle in geographical coordinates
     */
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
       
    /**
     * Parses coords array data stored in $_REQUEST and converts it to a Polygon
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return Polygon polygon in geographical coordinates
     */
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

    /**
     * Parses coords array data stored in $_REQUEST and converts it to Point
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return Point point in geographical coordinates
     */
    private function getPointShape(Dimension $imageSize, Bbox $bbox) {

        $points = self::coordsToPoints($_REQUEST[self::SELECTION_COORDS],
                                       $imageSize, $bbox);
        if (count($points) != 1)
            throw new CartoclientException("can't parse point dhtml coords");
        
        return $points[0];
    }
    
    /**
     * Converts coords array data to a Shape
     * @param CartoForm 
     * @param Dimension image size
     * @param Bbox current bbox in geographical coordinates
     * @return Shape shape in geographical coordinates
     */
    public function getMainmapShape($cartoForm, Dimension $imageSize, 
                                    Bbox $bbox) {

        if (HttpRequestHandler::isButtonPushed('mainmap')) {
            $point = HttpRequestHandler::getButtonPixelPoint('mainmap');
            $_REQUEST[self::SELECTION_TYPE] = 'point';
            $_REQUEST[self::SELECTION_COORDS] = sprintf('%d,%d', $point->x, 
                                                                 $point->y);
        }

        $type = $_REQUEST[self::SELECTION_TYPE];
        if ($type == 'point') 
            return self::getPointShape($imageSize, $bbox); 
        else if ($type == 'polyline') 
            return self::getLineShape($imageSize, $bbox);
        else if ($type == 'rectangle') 
            return self::getRectangleShape($imageSize, $bbox);
        else if ($type == 'polygon')
            return self::getPolygonShape($imageSize, $bbox); 
        else
            throw new CartoclientException("unknown selection_type: $type");
    }
    
}

/**
 * Does common actions on HTTP request
 * @package Client
 */
class HttpRequestHandler {

    /** 
     * @var Logger
     */
    private $log;

    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;
    }

    /**
     * Returns true if button was clicked
     * @param string button name
     * @return boolean
     */
    static function isButtonPushed($name) {
        return isset($_REQUEST[$name . '_x']) || isset($_REQUEST[$name . '_y']);
    }

    /**
     * Returns point where button was clicked
     * @param string button name
     * @return Point position of click
     */
    static function getButtonPixelPoint($buttonName) {
        $x = $_REQUEST[$buttonName . '_x'];
        $y = $_REQUEST[$buttonName . '_y'];
        
        return new Point($x, $y);
    }

    /**
     * Returns true if main map was clicked
     *
     * Stores the shape selected on main map.
     * @param CartoForm
     * @return boolean
     */
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

    /**
     * Returns true if key map was clicked and stores the point selected on key
     * map into $cartoForm.
     * 
     * @param CartoForm
     * @return boolean
     */
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

    /**
     * Checks if one map was clicked
     * @param CartoForm
     */
    private function checkClickedButtons($cartoForm) {

        if ($this->checkMainmapButton($cartoForm)) {
            return;
        }
            
        if ($this->checkKeymapButton($cartoForm)) {
            return;
        }
    }
    
    /**
     * Handles HTTP request for one tool of one plugin
     * @param ClientPlugin plugin
     * @param ToolDescription tool
     * @return mixed request
     */
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

    /**
     * Handles HTTP request for selected plugin tool
     *
     * Assumes that $plugin is an instance of {@link ToolProvider}.
     * @param ClientPlugin plugin
     * @return mixed request
     */
    public function handleTools(ClientPlugin $plugin) {
    
        if (!$plugin instanceof ToolProvider) {
            throw new CartoclientException("tool $plugin is not a tool provider");
            return;
        }
        
        if (!array_key_exists('tool', $_REQUEST)) {
            $this->log->debug('no tool selected, skipping');
            return;
        }
        $toolRequest = $_REQUEST['tool'];
        
        $tools = $this->cartoclient->getPluginManager()->
                callPluginImplementing($plugin, 'ToolProvider', 'getTools');
        
        foreach ($tools as $tool) {
            $this->log->debug('tool is ' . $tool->id);
            $this->log->debug('request ' . $toolRequest);
            $this->log->debug('id ' . $tool->id);
            if ($toolRequest == $tool->id) {
                return $this->handleTool($plugin, $tool);
            }
        }
        return;
    }

    /** 
     * Handles buttons and tools.
     * @param ClientSession session
     * @param CartoForm current status
     * @return CartoForm modified status
     */
    public function handleHttpRequest(ClientSession $clientSession, $cartoForm) {

        // buttons
        $cartoForm->pushedButton = CartoForm::BUTTON_NONE;
        $this->checkClickedButtons($cartoForm);

        // tools
        if (array_key_exists('tool', $_REQUEST)) {
            $tool = explode(',', $_REQUEST['tool']);
            $_REQUEST['tool'] = array_pop($tool); 
            $clientSession->selectedTool = $_REQUEST['tool'];
        }

        return $cartoForm;
    }
}

?>
