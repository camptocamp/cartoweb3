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
 * Exception to be used by the server.
 * 
 * @package Plugins
 */
class CartopluginException extends CartowebException {

}

/**
 * Server Outline class
 * @package Plugins
 */
class ServerOutline extends ClientResponderAdapter 
                    implements InitProvider {
    
    /**
     * @var Logger
     */
    private $log;

    /**
     * @var string
     */
    private $pathToSymbols;

    /**
     * @var string 
     */
    private $symbolType;


    /** 
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @param Shape
     * @return ms_styleObj
     */
    protected function toShapeObj(Shape $shape) {
        $p = ms_newLineObj();
        
        $className = get_class($shape);
        if ($className == 'Point') {
            $f = ms_newShapeObj(MS_SHAPE_POINT);
            $p->addXY($shape->x, $shape->y);
            
        } else if ($className == 'Rectangle') {
            $f = ms_newShapeObj(MS_SHAPE_POLYGON);
            $p->addXY($shape->minx, $shape->maxy);
            $p->addXY($shape->maxx, $shape->maxy);
            $p->addXY($shape->maxx, $shape->miny); 
            $p->addXY($shape->minx, $shape->miny);
        } else {
            if ($className == 'Line') {
                $f = ms_newShapeObj(MS_SHAPE_LINE);
            } else if ($className == 'Polygon') {
                $f = ms_newShapeObj(MS_SHAPE_POLYGON);
            }
            foreach ($shape->points as $point) {
                $p->addXY($point->x, $point->y);
            }
        }
        $f->add($p);

        return $f;
    }

    /**
     * @param StyledShape
     * @param string
     */
    protected function drawFeature(StyledShape $shape, $layerName) {

        //$layerName : name of the layer to get from mapfile

        if (is_null($layerName) || $layerName == '') {
            throw new CartoserverException('Layer name is not set. ' .
                                           'check your outline.ini');
        }

        // find or create a mapserver class for this style.
        $layer = new LayerOverlay();
        $layer->name = $layerName;
        $layer->action = BasicOverlay::ACTION_SEARCH;
        if (!is_null($shape->shapeStyle)) {
            $layer->transparency = $shape->shapeStyle->transparency;            
        }
        $layer->classes = array($this->getMsClass($shape->shapeStyle, 
                                                  $shape->labelStyle));
        // get the class index
        try {
            $mapOverlay = $this->serverContext->getPluginManager()->mapOverlay;
        } catch (Exception $e) {
            throw new CartoserverException('mapOverlay plugin not loaded, ' . 
                                           'and needed by outline: ' .
                                           'add "mapOverlay" to your ' .
                                           'server-side "loadPlugins" parameter');
        }
        $result = $mapOverlay->updateMap($layer);

        $f = $this->toShapeObj($shape->shape);

        $f->set('text', $shape->label);
        $f->set('classindex', $result->layers[0]->classes[0]->index);

        $msLayer = $this->serverContext->getMapObj()->getLayer($result->layers[0]->index);
        $msLayer->addFeature($f);
        $msLayer->set('status', MS_ON);    
    }

    /**
     * @param StyleOverlay
     * @param LabelOverlay
     *
     * @return ClassOverlay
     */
    protected function getMsClass($shapeStyle, $labelStyle) {
        // search for a class with this style
        $class = new ClassOverlay();
        $class->action = BasicOverlay::ACTION_SEARCH;
        $class->label = $labelStyle;
        if (!is_null($shapeStyle)) {
            $class->styles = array($shapeStyle);
        }   
        return $class;
    }

    /**
     * Adds a point to Mapserver layer
     *
     * If point layer is not defined in configuration file, tries with
     * polygon layer.
     * @param StyledShape point
     */
    protected function drawPoint($point) {

        $layerName = $this->getConfig()->pointLayer;
        if (!$layerName) {
            $layerName = $this->getConfig()->polygonLayer;
        }
        $this->drawFeature($point, $layerName);
    }


    /**
     * Adds a line to Mapserver layer
     *
     * @param StyledShape line
     */
    protected function drawLine($line) {
          
        $this->drawFeature($line, 
                           $this->getConfig()->lineLayer);
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
     * @param StyledShape polygon
     * @param boolean mask mode on/off
     */
    protected function drawPolygon($polygon, $maskMode) {
        if (!$maskMode) {
            $this->drawFeature($polygon, 
                               $this->getConfig()->polygonLayer);
        } else {
            // implementation note: this time we don't use MapOverlay because 
            // the layer is not created in the mapfile.
            $msMapObj = $this->serverContext->getMapObj();
            $image2 = $msMapObj->prepareimage();
            $rectangle = ms_newRectObj();
            $rectangle->setExtent($this->serverContext->getMaxExtent()->minx,
                                  $this->serverContext->getMaxExtent()->miny,
                                  $this->serverContext->getMaxExtent()->maxx,
                                  $this->serverContext->getMaxExtent()->maxy);

            $maskLayer = ms_newLayerObj($msMapObj);
            $maskLayer->set("type", MS_LAYER_POLYGON);
            $maskLayer->set("status", MS_ON);
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

            $p = $this->toShapeObj($polygon->shape);
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
    protected function drawMap($msMapObj) {
        $plugins = $this->serverContext->getPluginManager();
        if (!empty($plugins->query) && $plugins->query->drawQuery()) {
            $this->serverContext->setMsMainmapImage($msMapObj->drawQuery());
        } else {
            $this->serverContext->setMsMainmapImage($msMapObj->draw());
        }
    } 
    
    /**
     * Handles shapes drawing and area computation
     * @param array array of StyledShape
     * @param boolean mask mode
     * @return double area
     */
    public function draw($shapes, $maskMode = false) {
        
        if (empty($shapes)) {
            return 0.0;
        }
                
        $msMapObj = $this->serverContext->getMapObj();
        
        if ($maskMode) {
            $this->drawMap($msMapObj);
            $msMapObj->labelcache->free();
        }
        
        $area = 0.0;
        foreach ($shapes as $shape) {
            switch (get_class($shape->shape)) {
            case 'Point':
                $this->drawPoint($shape);
                break;
            case 'Line':
                $this->drawLine($shape);
                break;
            case 'Rectangle':
            case 'Polygon':
                $this->drawPolygon($shape, $maskMode);
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

    /**
     * @see InitProvider::getInit
     */
    public function getInit() {

        if($this->getConfig()->pointSymbols || $this->getConfig()->lineSymbols || 
           $this->getConfig()->polygonSymbols) {
            $this->generateSymbolIcon();
        }
        
        $outlineInit = new OutlineInit();
        $outlineInit->point = Utils::parseArray($this->getConfig()->pointSymbols);
        
        // special case: "pointSymbols.labels" has a dot, cannot use getConfig() directly
        $tmp = "pointSymbols.labels";
        $outlineInit->pointLabels = Utils::parseArray($this->getConfig()->$tmp);
        $outlineInit->line = Utils::parseArray($this->getConfig()->lineSymbols);
        $outlineInit->polygon = Utils::parseArray($this->getConfig()->polygonSymbols);

        $outlineInit->pathToSymbols = $this->pathToSymbols;
        $outlineInit->symbolType = $this->symbolType;

        $targetList = array('pointLayer', 'lineLayer', 'polygonLayer');

        $defaultValuesList = new OutlineDefaultValuesList();
        $msMapObj = $this->serverContext->getMapObj();

        foreach($targetList as $targetLayerType) {
          
          $currentDefaultValues = new OutlineDefaultValues();
          $currentShapeStyle = new StyleOverlay();
          
          // set type
          $currentDefaultValues->type = $targetLayerType;

          $currentLayer = $msMapObj->getLayerByName($this->getConfig()->$targetLayerType);
          
          // get layer transparency
          $currentShapeStyle->transparency = $currentLayer->transparency;
          //get first class
          $currentClass = $currentLayer->getClass(0);
          $currentStyle = $currentClass->getStyle(0);

          $colorList = array('red', 'green', 'blue');
          foreach($colorList as $color) {
              $currentShapeStyle->color->$color = $currentStyle->color->$color;
              $currentShapeStyle->outlineColor->$color = $currentStyle->outlinecolor->$color;
          }
          $currentShapeStyle->size = $currentStyle->size;
          
          $currentDefaultValues->shapeStyle = $currentShapeStyle;
          $defaultValuesList->outlineDefaultValuesList[] = $currentDefaultValues;
        }        

        $outlineInit->outlineDefaultValues = $defaultValuesList;

        return $outlineInit;
    }

    /**
     * Generate symbol icons to be used with symbol picker
     */
    protected function generateSymbolIcon() {
        
        $msMapObj = $this->serverContext->getMapObj();
        $this->symbolType = $msMapObj->outputformat->extension;

        $project = $this->serverContext->getProjectHandler()->getProjectName();
        $writablePath = $this->serverContext->getConfig()->webWritablePath;
        $mapId = $msMapObj->name;
        $iconRelativePath = implode('/', array('icons', $project, $mapId)) . '/';
        $iconAbsolutePath = Utils::pathToPlatform($writablePath . $iconRelativePath);
        
        $resourceHandler = $this->serverContext->getResourceHandler();
        $this->pathToSymbols = $resourceHandler->getGeneratedUrl($iconRelativePath);
         
        // create fake layer to be able to generate icons from class/style
        $newLayer = ms_newLayerObj($msMapObj);
        $newLayer->set('type', MS_LAYER_POINT); // important
        $newClass = ms_newClassObj($newLayer);
        $newStyle = ms_newStyleObj($newClass);
        $newStyle->color->setRGB(255, 0, 0); // important
        $newStyle->set('size', 30); // important
        
        $refIndex = $newLayer->index;
         
        $symbolRefAr = array_merge(Utils::parseArray($this->getConfig()->pointSymbols), 
                                   Utils::parseArray($this->getConfig()->lineSymbols),
                                   Utils::parseArray($this->getConfig()->polygonSymbols));
         
        // loop through all symbols
        for($ii=0; $ii < $msMapObj->getNumSymbols(); $ii++) {
            $symbolName = $msMapObj->getSymbolObjectById($ii)->name;
            // create icon only on selected symbols
            if(in_array($symbolName, $symbolRefAr)) {
                $newStyle->set('symbolname', $symbolName);
                $iconPath = $iconAbsolutePath . $symbolName . '.' . $this->symbolType;
                $invertedIconPath = $iconAbsolutePath . $symbolName . '_over.' . $this->symbolType; 
                Utils::makeDirectoryWithPerms(dirname($iconPath), $writablePath);
                 
                if (!file_exists($iconPath) ||
                    filemtime($this->serverContext->getMapPath()) > 
                    filemtime($iconPath)) {

                    $newIcon = $newClass->createLegendIcon(30,30);
                    $check = $newIcon->saveImage($iconPath);
                    $newIcon->free(); // free resources
                    Utils::invertImage($iconPath, $invertedIconPath, true, $this->symbolType);

                    if ($check < 0) {
                        throw new CartoserverException("Failed writing $iconAbsolutePath");
                    }
                    $this->serverContext->checkMsErrors();
                }
            }
        }        
        // remove the layer
        $newLayer->set("status", MS_DELETE);
    }
}

?>
