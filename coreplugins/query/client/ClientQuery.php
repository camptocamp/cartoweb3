<?php
/**
 * Client query plugin
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
 * @package CorePlugins
 * @version $Id$
 */

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
class ClientQuery extends ClientPlugin implements Sessionable, GuiProvider,
                             ServerCaller, ToolProvider, Exportable, Ajaxable {
                  
    /**                 
     * @var Logger
     */
    private $log;

    /**
     * @var QueryState
     */
    protected $queryState;
    
    /**
     * @var Shape
     */
    protected $shape;
    
    /**
     * Query tool name
     */
    const TOOL_QUERY_BY_POINT = 'query_by_point';
    const TOOL_QUERY_BY_BBOX = 'query_by_bbox';
    const TOOL_QUERY_BY_POLYGON = 'query_by_polygon';
    const TOOL_QUERY_BY_CIRCLE = 'query_by_circle';
    
    /**
     * Default flags for queries
     */
    const DEFAULT_POLICY     = QuerySelection::POLICY_XOR;
    const DEFAULT_MASKMODE   = false;
    const DEFAULT_HILIGHT    = true;
    const DEFAULT_ATTRIBUTES = true;
    const DEFAULT_TABLE      = true;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);

        $this->queryState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->log->debug('creating session:');
      
        $this->queryState = new QueryState();
        $this->clearSession();
        
        return;
    }

    /**
     * Reinitializes session content
     */
    protected function clearSession() {
    
        $this->queryState->queryAllLayers = true;
        $this->queryState->querySelections = array();
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->log->debug('saving session:');
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
    public function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {
        return $mainmapShape;    
    }
    
    /**
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {}
    
    /**
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {}


    /**
     * @see ToolProvider::getTools()
     */
    public function getTools() {
        return array(new ToolDescription(self::TOOL_QUERY_BY_POINT, true, 40),
                     new ToolDescription(self::TOOL_QUERY_BY_BBOX, true, 41),
                     new ToolDescription(self::TOOL_QUERY_BY_POLYGON, true, 42),
                     new ToolDescription(self::TOOL_QUERY_BY_CIRCLE, true, 43));
    }

    /**
     * Returns layers Ids and labels from MapInfo
     * @param array layers
     * @param array layer labels
     */
    protected function getLayers(&$queryLayers, &$queryLayersLabel) {
    
        $queryLayersStr = $this->getConfig()->queryLayers;
        if (!empty($queryLayersStr)) {
            $queryLayers = explode(',', $queryLayersStr);
            $queryLayers = array_map('trim', $queryLayers);
            $queryLayersLabel = array();
            foreach ($queryLayers as $layer)
                $queryLayersLabel[] = I18n::gt($layer);
        } else {
        
            // Takes all layers 
            $layersInit = $this->cartoclient->getMapInfo()->layersInit;
            $queryLayers = array();
            $queryLayersLabel = array();
            foreach($layersInit->getLayers() as $layer) {
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
    protected function handleQuery($request, $check = false) {

        if (!empty($request['query_clear'])) {
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
    protected function findQuerySelection($layerId) {
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
    protected function addDefaultQuerySelection($layerId) {
    
        $querySelection = new QuerySelection();
        $querySelection->layerId = $layerId;
        $querySelection->useInQuery = true;
        $querySelection->policy = self::DEFAULT_POLICY;
        if (!is_null($this->getConfig()->defaultPolicy)) {
            $querySelection->policy = $this->getConfig()->defaultPolicy;            
        }
        $querySelection->maskMode = self::DEFAULT_MASKMODE;
        if (!is_null($this->getConfig()->defaultMaskmode)) {
            $querySelection->maskMode = $this->getConfig()->defaultMaskmode;
        }
        $querySelection->hilight = self::DEFAULT_HILIGHT;
        if (!is_null($this->getConfig()->defaultHilight)) {
            $querySelection->hilight = $this->getConfig()->defaultHilight;
        }
        $querySelection->tableFlags = new TableFlags();
        $querySelection->tableFlags->returnAttributes =
                                          self::DEFAULT_ATTRIBUTES;
        if (!is_null($this->getConfig()->defaultAttributes)) {
            $querySelection->tableFlags->returnAttributes =
                                          $this->getConfig()->defaultAttributes;
        }
        $querySelection->tableFlags->returnTable = 
                                          self::DEFAULT_TABLE;
        if (!is_null($this->getConfig()->defaultTable)) {
            $querySelection->tableFlags->returnTable =
                                          $this->getConfig()->defaultTable;
        }
        $querySelection->selectedIds = array();
        $this->queryState->querySelections[] = $querySelection;
        
        return $querySelection;
    }

    /**
     * Handles standard parameters
     * @param QueryRequest
     * @param boolean
     */
    protected function handleStandardParameters($request, $check = false) {

        // Handles simple layer selection
        $queryLayer    = $this->getHttpValue($request, 'query_layer');
        $querySelect   = $this->getHttpValue($request, 'query_select');    
        $queryUnSelect = $this->getHttpValue($request, 'query_unselect');    
        $queryPolicy   = $this->getHttpValue($request, 'query_policy');    
        $queryMaskMode = $this->getHttpValue($request, 'query_maskmode');    
        $queryHilight  = $this->getHttpValue($request, 'query_hilight');    
        $queryRetAttr  = $this->getHttpValue($request, 'query_return_attributes'); 
        
        // if attributes are required, then table must be displayed
        $queryRetTable = !empty($queryRetAttr) ? true :
                         $this->getHttpValue($request, 'query_return_table');    
        
        $queryBlocks   = $this->getHttpValue($request, 'query_blocks');
    
        $queryLayers = array();
        $queryLayersLabel = array();
        $this->getLayers($queryLayers, $queryLayersLabel);

        if ($check) {
            if (!is_null($queryLayer)
                && !in_array($queryLayer, $queryLayers)) {
                $this->cartoclient->addMessage('Selection layer not found');
                return;
            }
            if (!$this->checkBool($queryMaskMode, 'query_maskmode'))
                return;            
            if (!$this->checkBool($queryHilight, 'query_hilight'))
                return;            
            if (!$this->checkBool($queryRetAttr, 'query_return_attributes'))
                return;
            if (!$this->checkBool($queryRetTable, 'query_return_table'))
                return;
        }            
    
        if (is_null($queryLayer) && is_null($queryBlocks)) {
            $this->queryState->queryAllLayers = true;
        } else {
        
            $queries = array();
            if (!empty($queryBlocks)) {
                if (!is_array($queryBlocks)) return;

                foreach($queryBlocks as $layerId => $select) {
                    if (empty($layerId) || empty($select)) continue;
                    $queries[$layerId] = array('select'   => $select,
                                               'unselect' => NULL);;
                }

                if (empty($queries)) return;

                $queryPolicy = self::DEFAULT_POLICY;
                $this->queryState->queryAllLayers = true;
            
            } else {
                // Query only on some layers
                $this->queryState->queryAllLayers = false; 
                $queries[$queryLayer] = array('select'   => $querySelect,
                                              'unselect' => $queryUnSelect);
            }
            

            foreach($queries as $layerId => $query) {

                // Finds out if layer has been already queried            
                $querySelection = $this->findQuerySelection($layerId);
                if (is_null($querySelection)) {
                    $querySelection = $this->addDefaultQuerySelection($layerId);
                }

                $select = $query['select'];
                if (!empty($select)) {                
                    $selectIds = urldecode($select);
                    $selectIds = explode(',', $selectIds);            
                    $querySelection->selectedIds = array_merge(
                        $querySelection->selectedIds, $selectIds);
                    $querySelection->selectedIds = array_unique(
                        $querySelection->selectedIds);
                }
            
                $unselect = $query['unselect'];
                if (!empty($unselect)) {
                    $unselectIds = urldecode($unselect);
                    $unselectIds = explode(',', $unselectIds);
                    $querySelection->selectedIds = array_diff(
                        $querySelection->selectedIds, $unselectIds);
                }
                
                if (!empty($queryPolicy)) {
                    $querySelection->policy = $queryPolicy;
                }
                if (!empty($queryMaskMode)) {
                    $querySelection->maskMode = (bool)$queryMaskMode;
                }
                if (!empty($queryHilight)) {
                    $querySelection->hilight = (bool)$queryHilight;
                }
                if (!empty($queryRetAttr) || !empty($queryRetTable)) {
                    $querySelection->tableFlags = new TableFlags();
                    if (!empty($queryRetAttr)) {
                        $querySelection->tableFlags->returnAttributes = (bool)$queryRetAttr;
                    }
                    if (!empty($queryRetTable)) {                    
                        $querySelection->tableFlags->returnTable = (bool)$queryRetTable;
                    }
                }
            }
        }
    }
    
    /**
     * Handles parameters coming from complete form
     * @param QueryRequest
     */
    protected function handleCompleteForm($request) {
    
        // Handles form table
        $queryAllLayers  = $this->getHttpValue($request, 'query_alllayers');
        $queryLayerIds   = $this->getHttpValue($request, 'query_layerid');
        $queryInQuerys   = $this->getHttpValue($request, 'query_inquery');
        $queryMaskModes  = $this->getHttpValue($request, 'query_maskmode');
        $queryHilight    = $this->getHttpValue($request, 'query_hilight');
        $queryAttributes = $this->getHttpValue($request, 'query_attributes');
        $queryTable      = $this->getHttpValue($request, 'query_table');

        if (is_null($queryInQuerys)) {
            $queryInQuerys = array();
        }
        if (is_null($queryMaskModes)) {
            $queryMaskModes = array();
        }
        if (is_null($queryHilight)) {
            $queryHilight = array();
        }
        if (is_null($queryAttributes)) {
            $queryAttributes = array();
        }
        if (is_null($queryTable)) {
            $queryTable = array();
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
                    $querySelection->hilight = 
                        (in_array($queryLayerIds[$i], $queryHilight));
                    $querySelection->tableFlags = new TableFlags();
                    $querySelection->tableFlags->returnAttributes =
                        (in_array($queryLayerIds[$i], $queryAttributes));
                    $querySelection->tableFlags->returnTable = 
                        (in_array($queryLayerIds[$i], $queryTable));
                    $querySelection->useInQuery = 
                        (in_array($queryLayerIds[$i], $queryInQuerys));;
                }                                
            }
        }        
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {

        $this->shape = $this->cartoclient->getHttpRequestHandler()
                        ->handleTools($this);

        $this->handleQuery($request);

        if (!$this->getConfig()->displayExtendedSelection) {
        
            // Complete form is disabled, handles same parameters as Get request
            $this->handleStandardParameters($request);
            return;
        }

        $this->handleCompleteForm($request);
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {

        $this->handleQuery($request, true);

        $this->handleStandardParameters($request, true);
    }

    /**
     * @see ServerCaller::buildRequest()
     */ 
    public function buildRequest() {
    
        if (!is_null($this->shape) || (!is_null($this->queryState)
            && count($this->queryState->querySelections) > 0)) {
            $queryRequest = new QueryRequest();
            $queryRequest->queryAllLayers = $this->queryState->queryAllLayers;
            $queryRequest->defaultMaskMode = self::DEFAULT_MASKMODE;
            if (!is_null($this->getConfig()->defaultMaskmode)) {
                $queryRequest->defaultMaskMode = $this->getConfig()->defaultMaskmode;
            }
            $queryRequest->defaultHilight = self::DEFAULT_HILIGHT;
            if (!is_null($this->getConfig()->defaultHilight)) {
                $queryRequest->defaultHilight = $this->getConfig()->defaultHilight;
            }
            $queryRequest->defaultTableFlags = new TableFlags();
            $queryRequest->defaultTableFlags->returnAttributes =
                                              self::DEFAULT_ATTRIBUTES;
            if (!is_null($this->getConfig()->defaultAttributes)) {
                $queryRequest->defaultTableFlags->returnAttributes =
                                              $this->getConfig()->defaultAttributes;
            }
            $queryRequest->defaultTableFlags->returnTable = 
                                              self::DEFAULT_TABLE;
            if (!is_null($this->getConfig()->defaultTable)) {
                $queryRequest->defaultTableFlags->returnTable =
                                              $this->getConfig()->defaultTable;
            }
            $queryRequest->querySelections = $this->queryState->querySelections;        
            $queryRequest->shape = $this->shape;   
            return $queryRequest;
        }
        return null;
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($queryResult) {
        if (empty($queryResult))
            return;

        $queryResult = $this->processResult($queryResult);
        
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;
        $tablesPlugin->addTableGroups($queryResult->tableGroup);        
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($queryResult) {}
    
    /**
     * Process a query result
     *
     * Updates query selections depending on returned tables.
     * @param QueryResult
     * @return QueryResult
     */
    protected function processResult(QueryResult $queryResult) {

        foreach ($queryResult->tableGroup->tables as $table) {

            // Finds out if Ids were already selected for this layer
            $ids = $table->getIds();
            $querySelection = $this->findQuerySelection($table->tableId);
            if (is_null($querySelection) && $table->numRows > 0) {            
                // Ids selected by shape
                $querySelection = $this->addDefaultQuerySelection($table->tableId);
            }
            if (!is_null($querySelection)) {
                $querySelection->selectedIds = $ids;
            }
        }
        return $queryResult;
    }

    /**
     * Displays Query form
     * @return string HTML generated by Smarty
     */
    protected function drawQuery() {

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
    
        if ($this->getConfig()->displayExtendedSelection) {
            
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
                        $selection->hilight    = $querySelection->hilight;
                        $selection->returnAttributes =
                                    $querySelection->tableFlags->returnAttributes;           
                        $selection->returnTable =
                                    $querySelection->tableFlags->returnTable;           
                        break;
                    }
                }            
                $selections[] = $selection;
            }
            $smarty->assign('query_selections', $selections);
    
            $smarty->assign('query_alllayers', $this->queryState->queryAllLayers);
            $smarty->assign('query_hilightattr_active',
                                    $this->getConfig()->returnAttributesActive);           
        }
        $smarty->assign('query_display_selection',
            $this->getConfig()->displayExtendedSelection);
        
        return $smarty->fetch('query.tpl');          
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign('query_result', $this->drawQuery());    
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addHtmlCode('queryResult', $this->drawQuery());
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginsDirectives) {
        switch ($actionName) {
            case 'Query.Perform':
            case 'Query.Clear':
                $pluginsDirectives->disableCoreplugins();
                $pluginsDirectives->enablePlugin('images');
                $pluginsDirectives->enablePlugin('query');
                $pluginsDirectives->enablePlugin('tables');
            default:
                if ($this->getConfig()->persistentQueries == false) {                    
                    $pluginsDirectives->enablePlugin('images');
                    $pluginsDirectives->enablePlugin('query');
                    $pluginsDirectives->enablePlugin('tables');
                }
            break;
            
        }
    }
    
    /**    
     * @see Exportable::adjustExportMapRequest()
     */ 
    public function adjustExportMapRequest(ExportConfiguration $configuration, 
                                    MapRequest $mapRequest) {
                                            
        if (isset($mapRequest->queryRequest) && 
            $this->getConfig()->persistentQueries) {
            
            // Do not re-query in case of persistent queries
            $mapRequest->queryRequest->shape = null;
            
            // In case last request was a rectangle query, add current
            // selection to last request
            $mapRequest->queryRequest->querySelections
                                        = $this->queryState->querySelections;        
        }

        $querySelections = $configuration->getQuerySelections();
        if (!is_null($querySelections)) {
            $mapRequest->queryRequest->querySelections = $querySelections;
        }

        $queryBbox = $configuration->getQueryBbox();
        if (!is_null($queryBbox)) {
            $mapRequest->queryRequest->shape = $queryBbox;
        }
    }
}
