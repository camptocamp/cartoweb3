<?
/**
 * Outline plugin
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

    function __construct() {
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
                throw new CartoserverException('No outline layer defined in config file');
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
    private function drawPoint($msMapObj, $point) {

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

        $outlineLayer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
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
    private function drawRectangle($msMapObj, $rectangle, $maskMode) {

        $points = array();       
        $points[] = new Point($rectangle->minx, $rectangle->miny);
        $points[] = new Point($rectangle->minx, $rectangle->maxy);
        $points[] = new Point($rectangle->maxx, $rectangle->maxy);
        $points[] = new Point($rectangle->maxx, $rectangle->miny);

        $polygon = new Polygon();
        $polygon->points = $points;
        
        $this->drawPolygon($msMapObj, $polygon, $maskMode);
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
            $outlineLayer = $this->getLayer($msMapObj, $this->getConfig()->polygonLayer);
            $class = $outlineLayer->getClass(0);

            $outlineLayer->set('status', MS_ON);
            $class->set('status', MS_ON);
        
            $outlineLayer->addFeature($this->convertPolygon($polygon));            
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
                       
            $this->serverContext->getMsMainmapImage()->pasteImage($image2, 0xff0000);
            
        }

    }

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
     * @param OutlineRequest
     * @return OutlineResult
     */
    function handleDrawing($requ) {

        $msMapObj = $this->serverContext->getMapObj();

        if ($requ->maskMode) {
            $this->drawMap($msMapObj);
            $msMapObj->labelcache->free();
        }

        $area = 0;
        foreach ($requ->shapes as $shape) {
            switch (get_class($shape)) {
            case 'Point':
                $this->drawPoint($msMapObj, $shape, $requ->maskMode);
                break;
            case 'Rectangle':
                $this->drawRectangle($msMapObj, $shape, $requ->maskMode);
                break;
            case 'Polygon':
                $this->drawPolygon($msMapObj, $shape, $requ->maskMode);
                break;
            default:
                throw new CartoserverException("unknown shape type " . get_class($shape));
            }
            
            $area += $shape->getArea();
        }
        
        if (!$requ->maskMode) {
            $this->drawMap($msMapObj);
        }
        
        $result = new OutlineResult();

        $areaFactor = $this->getConfig()->areaFactor;
        if (is_null($areaFactor)) {
            $areaFactor = 1.0;
        } else {
            $areaFactor = (double)$areaFactor;
        }

        $result->area = $area * $areaFactor;
        return $result;
    }
}

?>