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

    private function draw_rect($msMapObj, $msImage, $rectangle) {

        $outline_layer = @$msMapObj->getLayerByName($this->serverContext->mapInfo->outlineLayer);
        if (!$outline_layer) {
            if (@$this->serverContext->mapInfo->outlineLayer)
                throw new CartoserverException('Outline layer ' . 
                                               $this->serverContext->mapInfo->outlineLayer . 
                                               ' is not defined in mapfile');
            else
                throw new CartoserverException('No outline layer defined in config file');
        }
        $class = $outline_layer->getClass(0);

        $r = ms_newRectObj();

        $r->setextent($rectangle->minx, $rectangle->miny, 
                      $rectangle->maxx, $rectangle->maxy);

        $outline_layer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $r->draw($msMapObj, $outline_layer, $msImage, 0, false);

    }

    private function draw_poly($msMapObj, $msImage, $polygon) {

        $outline_layer = @$msMapObj->getLayerByName($this->serverContext->mapInfo->outlineLayer);
        if (!$outline_layer) {
            if (@$this->serverContext->mapInfo->outlineLayer)
                throw new CartoserverException('Outline layer ' . 
                                               $this->serverContext->mapInfo->outlineLayer . 
                                               ' is not defined in mapfile');
            else
                throw new CartoserverException('No outline layer defined in config file');
        }
        $class = $outline_layer->getClass(0);

        $line = ms_newLineObj();

        foreach ($polygon->points as $point) {
            $line->addXY($point->x, $point->y);
        }
        $line->addXY($polygon->points[0]->x, $polygon->points[0]->y);
        
        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);
        
        $outline_layer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $p->draw($msMapObj, $outline_layer, $msImage);

    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        $msMainmapImage = $this->serverContext->getMsMainmapImage();

        foreach ($requ->shapes as $shape) {

            switch (get_class($shape)) {
            case 'Rectangle':
                $this->draw_rect($msMapObj, $msMainmapImage, $shape);
                break;
            case 'Polygon':
                $this->draw_poly($msMapObj, $msMainmapImage, $shape);
                break;
            default:
                throw new CartoserverException("unknown shape type " . get_class($shape));
            }
        }
    }
}
?>