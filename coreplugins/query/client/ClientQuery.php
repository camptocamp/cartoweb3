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
 * Query data to be stored in session
 * @package CorePlugins
 */
class QueryState {
    
    /**
     * If true, will query all layers selected in tree
     * @var boolean
     */
    public $queryAllLayers;
    
    /**
     * Current selections and flags
     * @var array array of {@link QuerySelection}
     */
    public $querySelections;
}

/**
 * Client part of Query plugin
 * @package CorePlugins
 */
class ClientQuery extends ClientPlugin
                  implements Sessionable, GuiProvider, ServerCaller, ToolProvider {
                  
    /**                 
     * @var Logger
     */
    private $log;

    /**
     * @var QueryState
     */
    private $queryState;
    
    /**
     * @var Bbox
     */
    private $bbox;
    
    /**
     * Query tool name
     */
    const TOOL_QUERY = 'query';

    /**
     * Default flags for queries
     */
    const DEFAULT_POLICY     = QuerySelection::POLICY_XOR;
    const DEFAULT_MASKMODE   = false;
    const DEFAULT_ATTRIBUTES = true;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see Sessionable::loadSession()
     */
    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);

        $this->queryState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");
      
        $this->queryState = new QueryState();
        $this->clearSession();
        
        return;
    }

    /**
     * Reinitializes session content
     */
    private function clearSession() {
    
        $this->queryState->queryAllLayers = true;
        $this->queryState->querySelections = array();
    }

    /**
     * @see Sessionable::saveSession()
     */
    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->queryState);

        if (!$this->getConfig()->persistentQueries) {
        
            // Do not store selections
            $this->queryState->querySelections = array(); 
        }

        return $this->queryState;
    }

    /**
     * @see ToolProvider::handleMainmapTool()
     */
    function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {
        
        if ($mainmapShape instanceof Point) {
            $bbox = new Bbox();
            $bbox->setFrom2Points($mainmapShape, $mainmapShape);
            $mainmapShape = $bbox;   
        } 
        
        if (!$mainmapShape instanceof Bbox) 
            throw new CartoclientException('Only bbox shapes are supported for queries');
            
        return $mainmapShape;    
    }
    
    /**
     * @see ToolProvider::handleKeymapTool()
     */
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do */
    }

    /**
     * @see ToolProvider::getTools()
     */
    function getTools() {
        return array(new ToolDescription(self::TOOL_QUERY, true,
                       new JsToolAttributes(JsToolAttributes::SHAPE_RECTANGLE,
                                            JsToolAttributes::CURSOR_HELP),
                                            40));
    }

    /**
     * Returns layers Ids and labels from MapInfo
     * @param array layers
     * @param array layer labels
     */
    private function getLayers(&$queryLayers, &$queryLayersLabel) {
    
        $queryLayersStr = $this->getConfig()->queryLayers;
        if (!empty($queryLayersStr)) {
            $queryLayers = explode(',', $queryLayersStr);
            $queryLayers = array_map('trim', $queryLayers);
            $queryLayersLabel = array();
            foreach ($queryLayers as $layer)
                $queryLayersLabel[] = I18n::gt($layer);
        } else {
        
            // Takes all layers 
            $mapInfo = $this->cartoclient->getMapInfo();
            $queryLayers = array();
            $queryLayersLabel = array();
            foreach($mapInfo->getLayers() as $layer) {
                if (! $layer instanceof Layer)
                    continue;
                $queryLayers[] = $layer->id; 
                $queryLayersLabel[] = I18n::gt($layer->label); 
            }
        }
    }

    /** 
     * Handles variables that can be posted by POST or by GET
     * @param array
     * @param boolean
     */
    private function handleQuery($request, $check = false) {
        $queryClear    = $this->getHttpValue($request, 'query_clear');    
            
        if ($check) {
            if (!$this->checkBool($queryClear, 'query_clear'))
                return NULL;                
        }            
        if (!is_null($queryClear) && $queryClear == 1) {
            $this->clearSession();
        }
    }
    
    /**
     * Finds {@link QuerySelection} for a layer Id
     *
     * Returns null if no QuerySelection was found.
     * @param string
     * @return QuerySelection
     */
    private function findQuerySelection($layerId) {
        $querySelection = null;
        foreach ($this->queryState->querySelections as $stateQuerySelection) {
            if ($stateQuerySelection->layerId == $layerId) {
                $querySelection = $stateQuerySelection;
                break;
            }
        }
        return $querySelection;
    }
    
    /**
     * Adds a {@link QuerySelection} with default values
     * @param string
     */
    private function addDefaultQuerySelection($layerId) {
    
        $querySelection = new QuerySelection();
        $querySelection->layerId = $layerId;
        $querySelection->policy = self::DEFAULT_POLICY;
        $querySelection->maskMode = self::DEFAULT_MASKMODE;
        $querySelection->tableFlags = new TableFlags();
        $querySelection->tableFlags->returnAttributes =
                                          self::DEFAULT_ATTRIBUTES;
        $querySelection->tableFlags->returnTable = true;
        $this->queryState->querySelections[] = $querySelection;
        
        return $querySelection;
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    function handleHttpPostRequest($request) {

        $this->bbox = $this->cartoclient->getHttpRequestHandler()
                        ->handleTools($this);

        $this->handleQuery($request);

        // Handles form table
        $queryAllLayers  = $this->getHttpValue($request, 'query_alllayers');
        $queryLayerIds   = $this->getHttpValue($request, 'query_layerid');
        $queryInQuerys   = $this->getHttpValue($request, 'query_inquery');
        $queryMaskModes  = $this->getHttpValue($request, 'query_maskmode');
        $queryAttributes = $this->getHttpValue($request, 'query_attributes');

        if (is_null($queryInQuerys)) {
            $queryInQuerys = array();
        }
        if (is_null($queryMaskModes)) {
            $queryMaskModes = array();
        }
        if (is_null($queryAttributes)) {
            $queryAttributes = array();
        }
        
        $this->queryState->queryAllLayers = ($queryAllLayers == '1');
        
        if (!is_null($queryLayerIds) && count($queryLayerIds) > 0) {
            
            for ($i = 0; $i < count($queryLayerIds); $i++) {
                
                // Finds out if layer has been already queried
                $querySelection = $this->findQuerySelection($queryLayerIds[$i]);
                if (in_array($queryLayerIds[$i], $queryInQuerys)) {
                    if (is_null($querySelection)) {
                        $querySelection = $this
                                ->addDefaultQuerySelection($queryLayerIds[$i]);
                    }   
                }
                if (!is_null($querySelection)) {             
                    $queryPolicy = $this->getHttpValue($request,    
                                                       "query_policy_$i");
                    if (!empty($queryPolicy)) {
                        $querySelection->policy = $queryPolicy;
                    }
                    $querySelection->maskMode = 
                        (in_array($queryLayerIds[$i], $queryMaskModes));
                    $querySelection->tableFlags = new TableFlags();
                    $querySelection->tableFlags->returnAttributes =
                        (in_array($queryLayerIds[$i], $queryAttributes));
                    $querySelection->tableFlags->returnTable = true;
                    $querySelection->useInQuery = 
                        (in_array($queryLayerIds[$i], $queryInQuerys));;
                }                                
            }
        }        
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    function handleHttpGetRequest($request) {

        $this->handleQuery($request, true);

        // Handles simple layer selection
        $queryLayer    = $this->getHttpValue($request, 'query_layer');
        $querySelect   = $this->getHttpValue($request, 'query_select');    
        $queryUnSelect = $this->getHttpValue($request, 'query_unselect');    
        $queryPolicy   = $this->getHttpValue($request, 'query_policy');    
        $queryMaskMode = $this->getHttpValue($request, 'query_maskmode');    
        $queryRetAttr  = $this->getHttpValue($request, 'query_return_attributes');    
    
        $queryLayers = array();
        $queryLayersLabel = array();
        $this->getLayers($queryLayers, $queryLayersLabel);
        if (!is_null($queryLayer)
            && !in_array($queryLayer, $queryLayers)) {
            $this->cartoclient->addMessage('Selection layer not found');
            return NULL;
        }
        if (!$this->checkBool($queryMaskMode, 'query_maskmode'))
            return NULL;            
        if (!$this->checkBool($queryRetAttr, 'query_return_attributes'))
            return NULL;            

        if (!is_null($queryLayer)) {
        
            // Query on only one layer
            $this->queryState->queryAllLayers = false; 
        
            // Finds out if layer has been already queried            
            $querySelection = $this->findQuerySelection($queryLayer);
            if (is_null($querySelection)) {
                $querySelection = $this->addDefaultQuerySelection($queryLayer);
            }
                       
            if (!is_null($querySelect)) {                
                $selectIds = urldecode($querySelect);
                $selectIds = explode(',', $selectIds);            
                $querySelection->selectedIds = array_merge(
                    $querySelection->selectedIds, $selectIds);
                $querySelection->selectedIds = array_unique(
                    $querySelection->selectedIds);
            }
        
            if (!is_null($queryUnSelect)) {
                $unselectIds = urldecode($queryUnSelect);
                $unselectIds = explode(',', $unselectIds);
                $querySelection->selectedIds = array_diff(
                    $querySelection->selectedIds, $unselectIds);
            }
            
            $querySelection->policy = $queryPolicy;
            $querySelection->maskMode = $queryMaskMode;
            $querySelection->tableFlags = new TableFlags();
            $querySelection->tableFlags->returnAttributes = $queryRetAttr;
            $querySelection->tableFlags->returnTable = true;
        }
    }

    /**
     * @see ServerCaller::buildMapRequest()
     */ 
    function buildMapRequest($mapRequest) {
    
        $queryRequest = new QueryRequest();
        $queryRequest->queryAllLayers = $this->queryState->queryAllLayers;
        $queryRequest->defaultMaskMode = self::DEFAULT_MASKMODE;
        $queryRequest->defaultTableFlags = new TableFlags();
        $queryRequest->defaultTableFlags->returnAttributes =
                                         self::DEFAULT_ATTRIBUTES;
        $queryRequest->defaultTableFlags->returnTable = true;
    
        $queryRequest->querySelections = $this->queryState->querySelections;
        
        $queryRequest->bbox = $this->bbox;

        $mapRequest->queryRequest = $queryRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    function initializeResult($queryResult) {
        if (empty($queryResult))
            return;

        $queryResult = $this->processResult($queryResult);
        
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;
        $tablesPlugin->addTableGroups($queryResult->tableGroup);        
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    function handleResult($queryResult) {}

    /**
     * Executes character encoding conversion
     *
     * FIXME: should be done globally
     * @param string
     * @return string
     */
    private function encodingConversion($str) {
        return utf8_decode($str);
    }

    /**
     * Executes character encoding conversion on an array
     *
     * FIXME: should be done globally
     * @param array
     * @return array
     */
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
     * Process a query result
     *
     * Updates query selections depending on returned tables.
     * @param QueryResult
     * @return QueryResult
     */
    private function processResult(QueryResult $queryResult) {

        foreach ($queryResult->tableGroup->tables as $table) {

            // Finds out if Ids were already selected for this layer
            $ids = $table->getIds();
            $querySelection = $this->findQuerySelection($table->tableId);
            if (is_null($querySelection) && $table->numRows > 0) {            
                // Ids selected by bbox
                $querySelection = $this->addDefaultQuerySelection($table->tableId);
            }
            if (!is_null($querySelection)) {
                $querySelection->selectedIds = $ids;
            }
            if ($table->numRows > 0) {
                $table->columnTitles = $this->arrayEncodingConversion(
                                            $table->columnTitles);
                                                                               
                foreach ($table->rows as $row) {            
                    $row->cells = $this->arrayEncodingConversion($row->cells);
                }
            }
        }
        return $queryResult;
    }

    /**
     * Displays Query form
     * @return string HTML generated by Smarty
     */
    private function drawQuery() {
        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);

        $queryLayers = array();
        $queryLayersLabel = array();
        $this->getLayers($queryLayers, $queryLayersLabel);

        $selections = array();
        for ($i = 0; $i < count($queryLayers); $i++) {
            $selection = new stdClass();
            $selection->layerId = $queryLayers[$i];
            $selection->layerLabel = $queryLayersLabel[$i];
            foreach ($this->queryState->querySelections as $querySelection) {
                if ($querySelection->layerId == $queryLayers[$i]) {
                    $selection->useInQuery = $querySelection->useInQuery;
                    $selection->policy     = $querySelection->policy;
                    $selection->maskMode   = $querySelection->maskMode;
                    $selection->returnAttributes =
                                $querySelection->tableFlags->returnAttributes;           
                    break;
                }
            }            
            $selections[] = $selection;
        }
        $smarty->assign('query_selections', $selections);

        $smarty->assign('query_alllayers', $this->queryState->queryAllLayers);
        $smarty->assign('query_hilightattr_active',
                                $this->getConfig()->returnAttributesActive);
        
        return $smarty->fetch('query.tpl');          
    }

    /**
     * @see GuiProvider::renderForm()
     */
    function renderForm(Smarty $template) {
    
        $queryOutput = $this->drawQuery();
        $template->assign('query_result', $queryOutput);    
    }
}

?>
