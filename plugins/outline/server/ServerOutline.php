<?

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

        /*
        if (!preg_match('/\w+\(+(.*)\)+/', $sfs_shape, $match))
            return "Invalid shape syntax: $sfs_shape";

        $coords = explode(',', $match[1]);
        $line = ms_newLineObj();

        foreach ($coords as $coord) {
            list($x, $y) = explode(' ', $coord);
            $line->addXY($x, $y);
        }
        */

        $shape = ms_newShapeObj(MS_SHAPE_POLYGON);

        $line = ms_newLineObj();
        $line->addXY(0, 0);
        $shape->add($line);

        $r = ms_newRectObj();

        //$r->setextent(556176, 103048, 582426, 121048);

        $r->setextent($rectangle->bbox->minx, $rectangle->bbox->miny, 
                      $rectangle->bbox->maxx, $rectangle->bbox->maxy);

        //$shape->add($r);

        $outline_layer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        //$shape->draw($msMapObj, $outline_layer, $msImage, 0, false);
        $r->draw($msMapObj, $outline_layer, $msImage, 0, false);

    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        $msMainmapImage = $this->serverContext->getMsMainmapImage();

        foreach ($requ->shapes as $shape) {

            switch ($shape->type) {
            case Shape::SHAPE_RECTANGLE:
                $this->draw_rect($msMapObj, $msMainmapImage, $shape);
                break;
            case Shape::SHAPE_POLYGON:
                x("todo_shape_poly");
                break;
            default:
                throw new CartoserverException("unknonw shape type " . $shape->type);
            }
        }
    }
}
?>