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
class ClientQuery extends ClientPlugin
                  implements Sessionable, GuiProvider, ServerCaller, ToolProvider {
    private $log;

    private $queryState;
    private $queryRequest;
    
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

    function handleHttpPostRequest($request) {
        $this->queryRequest = $this->cartoclient->getHttpRequestHandler()
                    ->handleTools($this);
    }

    function handleHttpGetRequest($request) {
    }

    function buildMapRequest($mapRequest) {
    
        if (!$this->queryRequest) {
            return;
        }
        
        $mapRequest->queryRequest = $this->queryRequest;
    }

    function initializeResult($queryResult) {
        if (empty($queryResult))
            return;

        $queryResult = $this->processResult($queryResult);
        
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;
        $tablesPlugin->addTableGroups($queryResult->tableGroup);        
    }

    function handleResult($queryResult) {}

    private function encodingConversion($str) {
        return utf8_decode($str);
    }

    private function arrayEncodingConversion($array) {
        if (empty($array))
            return $array;
        $ret = array();
        foreach($array as $key => $str) {
            $ret[$this->encodingConversion($key)] = $this->encodingConversion($str);
        }
        return $ret;
    }
    
    /**
     * Process a query result, decoding and translating the fields and result
     * values
     */
    private function processResult(QueryResult $queryResult) {
        foreach ($queryResult->tableGroup->tables as $table) {
            $table->columnTitles = $this->arrayEncodingConversion(
                                        $table->columnTitles);
            foreach ($table->rows as $row) {
                $row->cells = $this->arrayEncodingConversion($row->cells);
            }
        }
        return $queryResult;
    }

    function renderForm(Smarty $template) {
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->queryState);

        return $this->queryState;
    }
}
?>
