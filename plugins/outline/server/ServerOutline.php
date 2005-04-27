<?php
/**
 * Outline plugin
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
 * @package Plugins
 * @version $Id$
 */

/**
 * Server Outline class
 * @package Plugins
 */
class ServerOutline extends ClientResponderAdapter {
    
    /**
     * @var Logger
     */
    private $log;

    /**
     * Array of current style classes
     * @var array
     */
    private $styles;

    /**
     * Array of default style classes
     * @var array
     */
    private $defaultStyles;

    /** 
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
        
        $this->styles = array();
    }

    /**
     * Returns outline layer if it was defined
     * @param MsMapObj Mapserver Map object
     * @param string layer name
     * @return MsLayer Mapserver Layer object
     */
    private function getLayer($msMapObj, $layerName) {
        
        $outlineLayer = @$msMapObj->getLayerByName($layerName);
        if (!$outlineLayer) {
            if ($layerName) {
                throw new CartoserverException('Outline layer ' . $layerName . 
                                               ' is not defined in mapfile');
            } else {
                throw new CartoserverException('No outline layer defined in ' .
                                               'config file');
            }
        }
        return $outlineLayer;
    }

    /**
     * Sets a color object from a Mapserver color
     * @param MsColorObj
     * @param Color
     */
    private function setColor($msColorObj, &$color) {
        if ($msColorObj->red >= 0)
            $color->r = $msColorObj->red;
        if ($msColorObj->green >= 0)
            $color->g = $msColorObj->green;
        if ($msColorObj->blue >= 0)
            $color->b = $msColorObj->blue;
    }

    /**
     * Sets styles (shape & label) from a Mapserver layer
     * @param MsLayerObj
     * @param ShapeStyle
     * @param LabelStyle
     */
    private function setStyles($msLayerObj, &$shapeStyle, &$labelStyle) {
        $shapeStyle = new ShapeStyle();                                           
        $labelStyle = new LabelStyle();                                           

        if ($msLayerObj->numclasses > 0) {
            $msClassObj = $msLayerObj->getClass(0);
            if ($msClassObj->numstyles > 0) {
                $msStyleObj = $msClassObj->getStyle(0);
                $shapeStyle->symbol = $msStyleObj->symbol;
                $shapeStyle->size = $msStyleObj->size;        
                $this->setColor($msStyleObj->color, $shapeStyle->color);        
                $this->setColor($msStyleObj->outlinecolor, $shapeStyle->outlineColor);        
                $this->setColor($msStyleObj->backgroundcolor, $shapeStyle->backgroundColor);
            }
            $shapeStyle->transparency = $msLayerObj->transparency;        

            $msClassObj = $msLayerObj->getClass(0);
            $msLabelObj = $msClassObj->label;
            $labelStyle->font = $msLabelObj->font;
            $labelStyle->size = $msLabelObj->size;        
            $this->setColor($msStyleObj->color, $labelStyle->color);        
            $this->setColor($msStyleObj->outlinecolor, $labelStyle->outlineColor);        
            $this->setColor($msStyleObj->backgroundcolor, $labelStyle->backgroundColor);
        }            
    }

    /**
     * Updates a Mapserver color from a Color object
     * @param MsColorObj
     * @param Color
     */
    private function updateColor(&$msColorObj, $color) {
        $r = $msColorObj->red;
        $g = $msColorObj->green;
        $b = $msColorObj->blue;
        if (!is_null($color->r))
            $r = $color->r;
        if (!is_null($color->g))
            $g = $color->g;
        if (!is_null($color->b))
            $b = $color->b;
        $msColorObj->setRGB($r, $g, $b);
    } 

    /**
     * Updates a Mapserver class from styles (shape & label)
     * @param MsClassObj
     * @param ShapeStyle
     * @param LabelStyle
     */
    private function updateClass(&$msClassObj, $shapeStyle, $labelStyle) {
        if ($msClassObj->numstyles == 0) {
            $msStyleObj = ms_newStyleObj($msClassObj);
        } else {
            $msStyleObj = $msClassObj->getStyle(0);
        }        
        if (!empty($shapeStyle->symbol))
            $msStyleObj->set('symbol', $shapeStyle->symbol);
        if (!empty($shapeStyle->size))
            $msStyleObj->set('size', $shapeStyle->size);
        if (!empty($shapeStyle->color))
            $this->updateColor($msStyleObj->color, $shapeStyle->color);
        if (!empty($shapeStyle->outlineColor))
            $this->updateColor($msStyleObj->outlinecolor, $shapeStyle->outlineColor);
        if (!empty($shapeStyle->backgroundColor))
            $this->updateColor($msStyleObj->backgroundcolor, $shapeStyle->backgroundColor);
        
        if (!empty($labelStyle->font))
            $msClassObj->label->set('font', $labelStyle->font);
        if (!empty($labelStyle->size))
            $msClassObj->label->set('size', $labelStyle->size);
        if (!empty($labelStyle->color))
            $this->updateColor($msClassObj->label->color, $labelStyle->color);
        if (!empty($labelStyle->outlineColor))
            $this->updateColor($msClassObj->label->outlinecolor, $labelStyle->outlineColor);
        if (!empty($labelStyle->backgroundColor))
            $this->updateColor($msClassObj->label->backgroundcolor, $labelStyle->backgroundColor);
    }

    /** 
     * Computes a string from a Color object
     * @param Color
     * @return string
     */
    private function serializeColor($color) {
        $key = 'color' . $color->r . ',' . $color->g . ',' . $color->b;
        return $key;
    }

    /**
     * Computes a string key to identify a specific class
     * @param ShapeStyle
     * @param LabelStyle
     * @return string
     */
    private function computeKey($shapeStyle, $labelStyle) {
        $key = 'shapestyle-' . $shapeStyle->symbol;
        $key .= '-' . $shapeStyle->size;
        $key .= '-' . $this->serializeColor($shapeStyle->color);
        $key .= '-' . $this->serializeColor($shapeStyle->outlineColor);
        $key .= '-' . $this->serializeColor($shapeStyle->backgroundColor);
        $key .= '-' . $shapeStyle->transparency;

        $key .= 'labelstyle-' . $labelStyle->font;
        $key .= '-' . $labelStyle->size;
        $key .= '-' . $this->serializeColor($labelStyle->color);
        $key .= '-' . $this->serializeColor($labelStyle->outlineColor);
        $key .= '-' . $this->serializeColor($labelStyle->backgroundColor);
        return $key;
    }

    /**
     * Initializes styles array with layer information
     * @param MsMapObj
     * @param string
     */
    private function initializeStyles($msMapObj, $layerName) {
        
        if (!array_key_exists($layerName, $this->styles)) {
                        
            $msLayerObj = $this->getLayer($msMapObj, $layerName);
                                                 
            if ($msLayerObj->numclasses == 0) {
                $class = ms_newClassObj($msLayerObj);
            }
            $this->setStyles($msLayerObj, $shapeStyle, $labelStyle);
            
            $this->defaultStyles[$layerName] =
                array('shape' => $shapeStyle, 'label' => $labelStyle);
            
            $key = $this->computeKey($shapeStyle, $labelStyle);
            
            $this->styles[$layerName] = array($shapeStyle->transparency
                                              => array($key => 0));
        }
    }
    
    /**
     * Returns layer and class index for a shape
     * @param MsMapObj
     * @param string
     * @param StyledShape
     * @param MsLayer
     * @param int
     */
    private function findClass($msMapObj, $layerName, $styledShape, 
                               &$layer, &$classIndex) {
        
        $shapeStyle = $this->defaultStyles[$layerName]['shape']->merge($styledShape->shapeStyle);
        $labelStyle = $this->defaultStyles[$layerName]['label']->merge($styledShape->labelStyle);
        $transparency = $shapeStyle->transparency;

        $msDefaultLayer = $msMapObj->getLayerByName($layerName);
                                              
        $key = $this->computeKey($shapeStyle, $labelStyle);

        $layerStyles = $this->styles[$layerName];        
        if (!array_key_exists($transparency, $layerStyles)) {
            
            // New transparency            
            $layerStyles[$transparency] = array();
            
            $layer = ms_newLayerObj($msMapObj, $msDefaultLayer);

            // FIXME: Mapscript 4.4 allows class removal
            // for ($i = $layer->numclasses - 1; $i >= 0; $i--) {
            //     $layer->removeClass($i);
            // }            
            $layer->set('transparency', $transparency);
            $layer->set('name', $layerName . $transparency);           
        } else if ($transparency == 
            $this->defaultStyles[$layerName]['shape']->transparency) {
            
            // Same transparency as default layer
            $layer = $msDefaultLayer;
        } else {
        
            // Other transparency
            $layer = $msMapObj->getLayerByName($layerName . $transparency);           
        }
        if (!array_key_exists($key, $layerStyles[$transparency])) {
            $msDefaultClass = $msDefaultLayer->getClass(0);
            
            $classIndex = $layer->numclasses;
            $class = ms_newClassObj($layer, $msDefaultClass);
            
            $this->updateClass($class, $shapeStyle, $labelStyle);
            
            $layerStyles[$transparency][$key] = $classIndex;     
            $this->styles[$layerName] = $layerStyles;
        }
        $classIndex = $layerStyles[$transparency][$key];
    }

    /**
     * Adds a point to Mapserver layer
     *
     * If point layer is not defined in configuration file, tries with
     * polygon layer.
     * @param MsMapObj Mapserver Map object
     * @param Point
     */
    private function drawPoint($msMapObj, $point) {
        $layerName = $this->getConfig()->pointLayer;
        if (!$layerName) {
            $layerName = $this->getConfig()->polygonLayer;
        }
        
        $line = ms_newLineObj();
        $line->addXY($point->shape->x, $point->shape->y);

        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);

        if (!empty($point->label)) {
            $p->set('text', $point->label);
        }

        $this->initializeStyles($msMapObj, $layerName);
        $this->findClass($msMapObj, $layerName, $point, &$layer, &$classIndex);

        $p->set('classindex', $classIndex);        
        $layer->set('status', MS_ON);
        $layer->addFeature($p);
    }

    /**
     * Adds a line to Mapserver layer
     *
     * @param MsMapObj Mapserver Map object
     * @param line
     */
     private function drawLine($msMapObj, $line) {
          
        $layerName = $this->getConfig()->lineLayer;
        
        $dLine = ms_newLineObj();
                
        foreach ($line->shape->points as $point) {
            $dLine->addXY($point->x, $point->y);
        }
            
        $p = ms_newShapeObj(MS_SHAPE_LINE);
        $p->add($dLine);

        if (!empty($line->label)) {
            $p->set('text', $line->label);
        }
        
        $this->initializeStyles($msMapObj, $layerName);
        $this->findClass($msMapObj, $layerName, $line, &$layer, &$classIndex);

        $p->set('classindex', $classIndex);        
        $layer->set('status', MS_ON);
        $layer->addFeature($p);
    }

    /**
     * Adds a rectangle to Mapserver layer
     *
     * @see drawPolygon()
     * @param MsMapObj Mapserver Map object
     * @param Rectangle
     * @param boolean mask mode on/off
     */
    private function drawRectangle($msMapObj, $rectangle, $maskMode) {
        $points = array();       
        $points[] = new Point($rectangle->shape->minx, $rectangle->shape->miny);
        $points[] = new Point($rectangle->shape->minx, $rectangle->shape->maxy);
        $points[] = new Point($rectangle->shape->maxx, $rectangle->shape->maxy);
        $points[] = new Point($rectangle->shape->maxx, $rectangle->shape->miny);

        $polygon = new Polygon();
        $polygon->points = $points;
        $styledPolygon = new StyledShape();
        $styledPolygon->shape = $polygon;
        $styledPolygon->shapeStyle = $rectangle->shapeStyle;
        $styledPolygon->label = $rectangle->label;
        $styledPolygon->labelStyle = $rectangle->labelStyle;
        
        $this->drawPolygon($msMapObj, $styledPolygon, $maskMode);
    }

    /**
     * Converts a Polygon to a Mapserver polygon object
     * @param Polygon
     * @return MsPolygonObj
     */ 
    private function convertPolygon($polygon) {
        $line = ms_newLineObj();

        if (count($polygon->points) == 0)
            throw new CartoserverException('Invalid polygon: has 0 points');
        foreach ($polygon->points as $point) {
            $line->addXY($point->x, $point->y);
        }
        $line->addXY($polygon->points[0]->x, $polygon->points[0]->y);
    
        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);
        
        return $p;
    }

    /**
     * Adds a polygon to Mapserver layer
     *
     * If not in mask mode, simply draws Polygon.
     *
     * If in mask mode, uses MapScript pasteImage function to simulate a mask.
     * This function doesn't include transparency handling. Mask color is set
     * in configuration file, key maskColor.
     * @param MsMapObj Mapserver Map object
     * @param Polygon
     * @param boolean mask mode on/off
     */
    private function drawPolygon($msMapObj, $polygon, $maskMode) {

        if (!$maskMode) { 

            $layerName = $this->getConfig()->polygonLayer;

            $p = $this->convertPolygon($polygon->shape);

            if (!empty($polygon->label)) {
                $p->set('text', $polygon->label);
            }

            $this->initializeStyles($msMapObj, $layerName);
            $this->findClass($msMapObj, $layerName, $polygon, &$layer, &$classIndex);

            $p->set('classindex', $classIndex);        
            $layer->set('status', MS_ON);
            $layer->addFeature($p);
        } else {
        
            $image2 = $msMapObj->prepareimage();
            $rectangle = ms_newRectObj();
            $rectangle->setExtent($this->serverContext->getMaxExtent()->minx,
                                  $this->serverContext->getMaxExtent()->miny,
                                  $this->serverContext->getMaxExtent()->maxx,
                                  $this->serverContext->getMaxExtent()->maxy);

            $maskLayer = ms_newLayerObj($msMapObj);
            $maskLayer->set("type", MS_LAYER_POLYGON);
            $maskLayer->set("status", 1);
            $maskClass = ms_newClassObj($maskLayer);
            $maskStyle = ms_newStyleObj($maskClass);
            $color = $this->getConfig()->maskColor;
            if (!$color) {
                $color = '255 255 255';
            }
            list($red, $green, $blue) = explode(' ', $color);
            $maskStyle->color->setRGB($red, $green, $blue);

            $rectangle->draw($msMapObj, $maskLayer, $image2, 0, "");
            
            $maskStyle->color->setRGB(255, 0, 0);
            $maskStyle->outlinecolor->setRGB(255, 0, 0);
                                          
            $p = $this->convertPolygon($polygon->shape);
            $p->draw($msMapObj, $maskLayer, $image2, 0, "");

            // No labels, no styles in mask mode
                       
            $this->serverContext->getMsMainmapImage()->pasteImage($image2,
                                                                  0xff0000);
            
        }

    }

    /**
     * Draws map using drawQuery() or draw()
     * @param MsMapObj Mapserver Map object
     */
    private function drawMap($msMapObj) {
        $plugins = $this->serverContext->getPluginManager();
        if (!empty($plugins->query) && $plugins->query->drawQuery()) {
            $this->serverContext->setMsMainmapImage($msMapObj->drawQuery());
        } else {
            $this->serverContext->setMsMainmapImage($msMapObj->draw());
        }
    } 
    
    /**
     * Handles shapes drawing and area computation
     * @param array array of shapes
     * @param boolean mask mode
     * @return double area
     */
    public function draw($shapes, $maskMode = false) {
        $msMapObj = $this->serverContext->getMapObj();

        if ($maskMode) {
            $this->drawMap($msMapObj);
            $msMapObj->labelcache->free();
        }

        $area = 0;
        foreach ($shapes as $shape) {
            switch (get_class($shape->shape)) {
            case 'Point':
                $this->drawPoint($msMapObj, $shape, $maskMode);
                break;
            case 'Line':
                $this->drawLine($msMapObj, $shape, $maskMode);
                break;
            case 'Rectangle':
                $this->drawRectangle($msMapObj, $shape, $maskMode);
                break;
            case 'Polygon':
                $this->drawPolygon($msMapObj, $shape, $maskMode);
                break;
            default:
                throw new CartoserverException('unknown shape type ' . 
                                               get_class($shape->shape));
            }
            
            $area += $shape->shape->getArea();
        }
        
        if (!$maskMode) {
            $this->drawMap($msMapObj);
        }
        
        $areaFactor = $this->getConfig()->areaFactor;
        if (is_null($areaFactor)) {
            $areaFactor = 1.0;
        } else {
            $areaFactor = (double)$areaFactor;
        }

        return $area * $areaFactor;
    }
    
    /**
     * Handles shapes drawing and area computation
     * @param OutlineRequest
     * @return OutlineResult
     */
    public function handleDrawing($requ) {
        
        $area = $this->draw($requ->shapes, $requ->maskMode);
        
        $result = new OutlineResult();
        $result->area = $area;
        return $result;
    }
}

?>
