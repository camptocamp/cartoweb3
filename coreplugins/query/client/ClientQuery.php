<?php

require_once('smarty/Smarty.class.php');

class QueryState {
    /* to be filled */
}

class ClientQuery extends ClientCorePlugin implements ToolProvider {
    private $log;

    private $queryState;
    private $queryResult;
    
    const TOOL_QUERY = 'query';

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);

        $this->queryState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");
        
        return;
    }

    function handleMainmapTool(ToolDescription $tool, 
                            Shape $mainmapShape) {
        
        $queryRequest = new QueryRequest();
        $queryRequest->layers = array();

        if ($mainmapShape instanceof Point) {
            $bbox = new Bbox();
            $bbox->setFrom2Points($mainmapShape, $mainmapShape);
            $mainmapShape = $bbox;   
        } 
        
        if (!$mainmapShape instanceof Bbox) 
            throw new CartoclientException('Only bbox shapes are supported for queries');
            
        $queryRequest->bbox = $mainmapShape;
        return $queryRequest;
    }
    
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do */
    }

    function getTools() {
        
        return array(new ToolDescription(self::TOOL_QUERY, NULL, 'Query', 
            ToolDescription::MAINMAP));
    }

    function handleHttpRequest($request) {

    }

    function buildMapRequest($mapRequest) {
    
        $queryRequest = $this->cartoclient->getHttpRequestHandler()
                    ->handleTools($this);
        if (!$queryRequest) {
            //throw new CartoclientException("failed to build query request");
            return;
        }
        
        $mapRequest->queryRequest = $queryRequest;
    }

    function handleMapResult($mapResult) {
    
        // TODO: have a generic way of request/result serialisation which
        // sits above the plugin mechanism 

        if (!@$mapResult->queryResult)
            return;
        
        $this->queryResult = $mapResult->queryResult;
    }

    private function drawQueryResult($queryResult) {

        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $this->log->debug("query result::");        
        $this->log->debug($queryResult);        

        $smarty->assign('layer_results', $queryResult->layerResults);


        return $smarty->fetch('query.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }
        
        if (!$this->queryResult)
            return;
        
        $queryOutput = $this->drawQueryResult($this->queryResult);

        $template->assign('query_result', $queryOutput);
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->queryState);

        return $this->queryState;
    }
}
?>