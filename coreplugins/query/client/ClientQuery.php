<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Smarty templates
 */
require_once('smarty/Smarty.class.php');

/**
 * @package CorePlugins
 */
class QueryState {
    /* to be filled */
}

/**
 * @package CorePlugins
 */
class ClientQuery extends ClientCorePlugin implements ToolProvider {
    private $log;

    private $queryState;
    private $queryRequest;
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
        return array(new ToolDescription(self::TOOL_QUERY, true,
                       new JsToolAttributes(JsToolAttributes::SHAPE_RECTANGLE,
                                            JsToolAttributes::CURSOR_HELP),
                                            40));
    }

    function handleHttpRequest($request) {
        $this->queryRequest = $this->cartoclient->getHttpRequestHandler()
                    ->handleTools($this);
    }

    function buildMapRequest($mapRequest) {
    
        if (!$this->queryRequest) {
            return;
        }
        
        $mapRequest->queryRequest = $this->queryRequest;
    }

    function handleResult($queryResult) {
        if (empty($queryResult))
            return;

        $this->queryResult = $queryResult;
    }

    private function drawQueryResult($queryResult) {

        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $this->log->debug("query result::");        
        $this->log->debug($queryResult);        

        $smarty->assign('layer_results', $queryResult->layerResults);


        return $smarty->fetch('query.tpl');
    }

    private function encodingDecode(QueryResult $queryResult) {
        
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }
        
        if (!$this->queryResult)
            return;
        
        //$queryResut = $this->encodingDecode(/* TODO */);
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
