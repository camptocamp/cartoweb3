<?
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class ServerOutline extends ServerPlugin {
    private $log;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function getType() {
        return ServerPlugin::TYPE_DRAWING;
    }

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

    private function drawRectangle($msMapObj, $rectangle) {

        $outlineLayer = $this->getLayer($msMapObj, $this->getConfig()->polygonLayer);
        $class = $outlineLayer->getClass(0);

        $line = ms_newLineObj();

        $line->addXY($rectangle->minx, $rectangle->miny);
        $line->addXY($rectangle->minx, $rectangle->maxy);
        $line->addXY($rectangle->maxx, $rectangle->maxy);
        $line->addXY($rectangle->maxx, $rectangle->miny);
        $line->addXY($rectangle->minx, $rectangle->miny);

        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);

        $outlineLayer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $outlineLayer->addFeature($p);

    }

    private function drawPolygon($msMapObj, $polygon) {

        $outlineLayer = $this->getLayer($msMapObj, $this->getConfig()->polygonLayer);
        $class = $outlineLayer->getClass(0);

        $line = ms_newLineObj();

        foreach ($polygon->points as $point) {
            $line->addXY($point->x, $point->y);
        }
        $line->addXY($polygon->points[0]->x, $polygon->points[0]->y);
        
        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);
        
        $outlineLayer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $outlineLayer->addFeature($p);

    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        foreach ($requ->shapes as $shape) {
            switch (get_class($shape)) {
            case 'Point':
                $this->drawPoint($msMapObj, $shape);
                break;
            case 'Rectangle':
                $this->drawRectangle($msMapObj, $shape);
                break;
            case 'Polygon':
                $this->drawPolygon($msMapObj, $shape);
                break;
            default:
                throw new CartoserverException("unknown shape type " . get_class($shape));
            }
        }
        
        $this->serverContext->msMainmapImage = $msMapObj->draw();
    }
}
?>