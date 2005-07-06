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
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
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
     * Adds a point to Mapserver layer
     *
     * If point layer is not defined in configuration file, tries with
     * polygon layer.
     * @param MsMapObj Mapserver Map object
     * @param Point
     */
    private function drawPoint($msMapObj, $point, $labelMode) {
        $layerName = $this->getConfig()->pointLayer;
        if (!$layerName) {
            $layerName = $this->getConfig()->polygonLayer;
        }
        
        $outlineLayer = $this->getLayer($msMapObj, $layerName);
        $class = $outlineLayer->getClass(0);

        $line = ms_newLineObj();

        $line->addXY($point->x, $point->y);

        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);
        // TODO verify LABEL object is set in mapfile for the layer
        if ($labelMode) {
            $p->set('text',$point->label);
        }

        $outlineLayer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $outlineLayer->addFeature($p);
        
    }

    /**
     * Adds a line to Mapserver layer
     *
     * @param MsMapObj Mapserver Map object
     * @param line
     */
        private function drawLine($msMapObj, $line, $labelMode) {
          
        $points = array();       

        $layerName = $this->getConfig()->lineLayer;
        $outlineLayer = $this->getLayer($msMapObj, $layerName);
        $class = $outlineLayer->getClass(0);
        
        $outlineLayer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $dLine = ms_newLineObj();
                
        foreach ($line->points as $point) {
            $dLine->addXY($point->x, $point->y);
        }
            
        $p = ms_newShapeObj(MS_SHAPE_LINE);
        $p->add($dLine);
        // TODO verify LABEL object is set in mapfile for the layer
        if ($labelMode) {
            $p->set('text',$line->label);
        }
        
        $outlineLayer->addFeature($p);
                
    }

    /**
     * Adds a rectangle to Mapserver layer
     *
     * @see drawPolygon()
     * @param MsMapObj Mapserver Map object
     * @param Rectangle
     * @param boolean mask mode on/off
     */
    private function drawRectangle($msMapObj, $rectangle, $labelMode, $maskMode) {
        $points = array();       
        $points[] = new Point($rectangle->minx, $rectangle->miny);
        $points[] = new Point($rectangle->minx, $rectangle->maxy);
        $points[] = new Point($rectangle->maxx, $rectangle->maxy);
        $points[] = new Point($rectangle->maxx, $rectangle->miny);

        $polygon = new Polygon();
        $polygon->points = $points;
        $polygon->label = $rectangle->label;
        
        $this->drawPolygon($msMapObj, $polygon, $labelMode, $maskMode);
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
    private function drawPolygon($msMapObj, $polygon, $labelMode, $maskMode) {

        if (!$maskMode) { 
            $outlineLayer = $this->getLayer($msMapObj, 
                                            $this->getConfig()->polygonLayer);
            $class = $outlineLayer->getClass(0);

            $outlineLayer->set('status', MS_ON);
            $class->set('status', MS_ON);

            $p = $this->convertPolygon($polygon);
            // TODO verify LABEL object is set in mapfile for the layer
            if ($labelMode) {
                $p->set('text',$polygon->label);
            }
            $outlineLayer->addFeature($p);            
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
                                          
            $p = $this->convertPolygon($polygon);
            $p->draw($msMapObj, $maskLayer, $image2, 0, "");
            // TODO verify LABEL object is set in mapfile for the layer
            if ($labelMode) {
                $p->set('text',$polygon->label);
            }
                       
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
	 * Returns the area of a shape. May be overrided to have a more precise area
	 * computation.
	 */
    protected function getShapeArea($shape) {
        return $shape->getArea();
    }
    
    /**
     * Handles shapes drawing and area computation
     * @param array array of shapes
     * @param boolean mask mode
     * @return double area
     */
    public function draw($shapes, $maskMode = false, $labelMode = false) {
    
        $msMapObj = $this->serverContext->getMapObj();

        if ($maskMode) {
            $this->drawMap($msMapObj);
            $msMapObj->labelcache->free();
        }

        $area = 0;
        foreach ($shapes as $shape) {
            switch (get_class($shape)) {
            case 'Point':
                $this->drawPoint($msMapObj, $shape, $labelMode, $maskMode);
                break;
            case 'Line':
                $this->drawLine($msMapObj, $shape, $labelMode, $maskMode);
                break;
            case 'Rectangle':
                $this->drawRectangle($msMapObj, $shape, $labelMode, $maskMode);
                break;
            case 'Polygon':
                $this->drawPolygon($msMapObj, $shape, $labelMode, $maskMode);
                break;
            default:
                throw new CartoserverException('unknown shape type ' . 
                                               get_class($shape));
            }
            
            //$area += $shape->getArea();
            $area += $this->getShapeArea($shape);
            
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
        
        $area = $this->draw($requ->shapes, $requ->maskMode, $requ->labelMode);
        
        $result = new OutlineResult();
        $result->area = $area;
        return $result;
    }
}

?>
