<?php

class ServerQuery extends ServerCorePlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getType() {
        return ServerPlugin::TYPE_POST_DRAWING;
    }

    function getRequestName() {
        return 'queryRequest';
    }

    function getResultName() {
        return 'query';
    }

    function getResultFromRequest($requ) {
        $this->log->debug("Get result from request: ");
        $this->log->debug($requ);

        $msMapObj = $this->serverContext->msMapObj;

        $rect = ms_newRectObj();
        //$rect->setextent(-0.5, 50.977222, 0.5, 51.977222);
        $rect->setextent(-10, 45, 10, 55);
        
        $layer = $msMapObj->getLayerByName('LINE');

        $layer->set('status', MS_ON);
        $ret = $layer->queryByRect($rect);
        

        if ($ret == MS_SUCCESS) {

            if ($layer->getNumResults() > 0) 
                $layer->open();
            for ($i = 0; $i < $layer->getNumResults(); $i++) {

                $result = $layer->getResult($i);
                $shape = $layer->getShape($result->tileindex, $result->shapeindex);

                $this->log->debug("shape : " .  $i);
                $this->log->debug($shape);
            }
        }

        //$ret = $msMapObj->queryByRect($rect);
        
        $this->log->debug("ret is " . $ret);
        
        x('gp');
    }    
}

?>