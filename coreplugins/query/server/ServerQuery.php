<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Server part of Query plugin
 * @package CorePlugins
 */
class ServerQuery extends ClientResponderAdapter {
    
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * Tells if query must be drawn by Mapserver
     * @var boolean
     */
    private $drawQuery = false;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Executes character encoding conversion
     *
     * FIXME: should be done globally
     * @param string
     * @return string
     */
    private function encodingConversion($str) {
        // FIXME: $str is asserted to be iso8851-1 
        return utf8_encode($str);
    }

    /**
     * Executes character encoding conversion on an array
     *
     * FIXME: should be done globally
     * @param array
     * @return array
     */
    private function arrayEncodingConversion($array) {
        $ret = array();
        foreach($array as $key => $str) {
            $ret[$this->encodingConversion($key)] = $this->encodingConversion($str);
        }
        return $ret;
    }

    /**
     * Returns list of attributes to be returned
     * @param string layer id
     * @return array
     */
    private function getAttributes($layerId) {

        $msMapObj = $this->serverContext->getMapObj();

        $mapInfo = $this->serverContext->getMapInfo();
        $msLayer = $mapInfo->getMsLayerById($msMapObj, $layerId);

        $returnedAttributesMetadataName = 'query_returned_attributes';
        
        $retAttrString = $msLayer->getMetaData($returnedAttributesMetadataName);
        if (empty($retAttrString)) {
            // fallback to header property for compatibility

            $retAttrString = $msLayer->header;
            if (!empty($retAttrString))
                $this->log->warn("Using compatibility header property for layer instead of " .
                    "$returnedAttributesMetadataName metadata field, please update your " .
                    "Mapfile !!");
        }
        if (empty($retAttrString)) {
            $this->log->warn('no filter for returned attributes, returning everything');
            return array();
        }
        return explode(' ', $retAttrString);
    }

    /**
     * Returns true if query results must be drawn using Mapserver
     * @return boolean
     */
    function drawQuery() {
        return $this->drawQuery;
    }

    /**
     * Converts a Mapserver query result to a {@link Table}
     * @param array
     * @param string
     * @param string
     * @param array array of attributes
     * @param TableFlags
     * @return Table
     */
    private function resultToTable($result, $layerId, $idAttribute, 
                                   $attributes, $tableFlags) {
        
        $table = new Table();
        $table->tableId = $layerId;
        $table->tableTitle = $layerId;
        $table->rows = array();
        $table->numRows = 0;
        
        if (count($result) == 0) {
            return $table;
        }
        if (count($attributes) == 0) {        
            $attributes = array_diff(array_keys($result[0]->values),
                                     array($idAttribute));
        }
        $table->noRowId = false;
        if (empty($idAttribute)) {
            $table->noRowId = true;
        }
        
        foreach ($result as $shape) {

            if (empty($table->columnTitles)
                && !is_null($tableFlags)
                && $tableFlags->returnAttributes) {
                $columnIds = array();
                $columnTitles = array();
                foreach ($attributes as $columnId) {
                    $columnIds[] = $columnId;
                    $columnTitles[] = $columnId;
                }
                $table->columnIds =
                    $this->arrayEncodingConversion($columnIds);
                $table->columnTitles =
                    $this->arrayEncodingConversion($columnTitles);
            }
            
            $tableRow = new TableRow();
            if (!empty($idAttribute)) {
                $tableRow->rowId = $this
                    ->encodingConversion($shape->values[$idAttribute]);
            }
            $cells = array();
            if (!is_null($tableFlags) && $tableFlags->returnAttributes) {
                foreach ($attributes as $columnId) {
                    $cells[] = $shape->values[$columnId];
                }
            }
            $tableRow->cells = $this->arrayEncodingConversion($cells);
            
            $table->rows[] = $tableRow;
            $table->numRows ++;
        }
        return $table;
    }    

    /**
     * Merges two tables
     * @param Table
     * @param Table
     * @param string merge policy
     * @return Table
     */
    private function mergeTables(Table $table1, Table $table2, $policy) {
        
        $resultTable = new Table();
        $resultTable->tableId = $table1->tableId;
        $resultTable->tableTitle = $table1->tableTitle;
        $resultTable->columnTitles = $table1->columnTitles;
        $resultTable->rows = array();
        $resultTable->numRows = 0;
        
        $table2Ids = $table2->getIds();
        foreach ($table1->rows as $row1) {
            
            if (in_array($row1->rowId, $table2Ids)) {
                if ($policy != QuerySelection::POLICY_XOR) {
                    $resultTable->rows[] = $row1;
                    $resultTable->numRows ++;
                }
            } else {
                if ($policy != QuerySelection::POLICY_INTERSECTION) {
                    $resultTable->rows[] = $row1;
                    $resultTable->numRows ++;
                }
            }
        }
        if ($policy != QuerySelection::POLICY_INTERSECTION) {

            $table1Ids = $table1->getIds(); 
            foreach ($table2->rows as $row2) {
                        
                if (!in_array($row2->rowId, $table1Ids)) {                
                    $resultTable->rows[] = $row2;
                    $resultTable->numRows ++;
                }
            }                      
        }
        return $resultTable;
    }

    /**
     * Executes query on layer
     *
     * Query can be done using a {@link Bbox}, a list of Ids, or both
     * @param Bbox
     * @param QuerySelection
     * @return Table
     */
    private function queryLayer($bbox, $querySelection) {
    
        // Attributes to be returned
        $attributes = array();
        if (!is_null($querySelection->tableFlags)
            && $querySelection->tableFlags->returnAttributes) {
            $attributes = $this->getAttributes($querySelection->layerId);
        }
    
        // ID attribute
        $idAttribute = $querySelection->idAttribute;
        if (is_null($idAttribute)) {
            $idAttribute = $this->serverContext
                                ->getIdAttribute($querySelection->layerId);
        }
    
        $pluginManager = $this->serverContext->getPluginManager();
    
        $tableIds = null;
        if (count($querySelection->selectedIds) > 0) {
            if (!empty($pluginManager->mapquery)) {
            
                $resultIds = $pluginManager->mapquery
                                    ->queryByIdSelection($querySelection);

                $tableIds = $this->resultToTable($resultIds,
                                                 $querySelection->layerId,
                                                 $idAttribute,
                                                 $attributes,
                                                 $querySelection->tableFlags);           
            }                        
        }

        $tableBbox = null;        
        if (!is_null($bbox) && $querySelection->useInQuery) {
            if (!empty($pluginManager->mapquery)) {
                  
                $resultBbox = $pluginManager->mapquery
                                    ->queryByBbox($querySelection->layerId, 
                                                  $bbox); 
                $tableBbox = $this->resultToTable($resultBbox,
                                                  $querySelection->layerId,  
                                                  $idAttribute,
                                                  $attributes,
                                                  $querySelection->tableFlags);           
            }                                    
        }

        if (is_null($tableIds) && is_null($tableBbox)) {
            $table = new Table();
            $table->tableId = $querySelection->layerId;
            return $table;
        }
        if (is_null($tableIds)) {
            return $tableBbox;
        }
        if (is_null($tableBbox)) {
            return $tableIds;
        }

        return $this->mergeTables($tableIds, $tableBbox,
                                  $querySelection->policy);
    }

    /**
     * Hilights query
     * @param array tables
     * @param array 
     */
    private function hilight($tables, $hilightQuerySelections) {

        $pluginManager = $this->serverContext->getPluginManager();
        if ($this->getConfig()->drawQueryUsingHilight) {
            
            if (empty($pluginManager->hilight))
                throw new CartoserverException("hilight plugin not loaded, "
                    . "and needed for the query hilight drawing");

            foreach ($tables as $table) {
                
                if ($table->numRows > 0) {
                    $querySelection = $hilightQuerySelections[$table->tableId];
                    $querySelection->selectedIds = $table->getIds();
                    $pluginManager->hilight->hilightLayer($querySelection);
                }
            }

        } else {
            
            $mapInfo = $this->serverContext->getMapInfo();
            $msMapObj = $this->serverContext->getMapObj();
            
            foreach ($tables as $table) {
            
                // Checks if layer has Ids
                $serverLayer = $mapInfo->getLayerById($table->tableId);
                $msLayer = $msMapObj->getLayerByName($serverLayer->msLayer);
                $this->serverContext->checkMsErrors();
                $idAttribute = $msLayer->getMetaData('id_attribute_string');
                if (empty($idAttribute)) {
                    continue;
                }

                $msMapObj->freeQuery($msLayer->index);                                
                if ($table->numRows == 0) {
                    // Nothing to highlight
                    continue;
                }
                
                // Redo query so hilight by drawQuery works
                $querySelection = $hilightQuerySelections[$table->tableId]; 
                $querySelection->selectedIds = $table->getIds();
                $resultIds = $pluginManager->mapquery
                                     ->queryByIdSelection($querySelection);                                  
            }
 
            $this->drawQuery = true;
        }
    } 
    
    /**
     * @see ClientResponder::handlePreDrawing()
     */
    function handlePreDrawing($requ) {
        
        $this->log->debug("handlePreDrawing: ");
        $this->log->debug($requ);
        $layersOk = array();
        $tables = array();
                
        $querySelections = $requ->querySelections;
        if (is_null($querySelections)) {
            $querySelections = array();
        }    
        // Stores all QuerySelection for hilight 
        $hilightQuerySelections = array();            
        if ($requ->queryAllLayers) {
        
            // Queries on all layers (list taken from Layers plugin)
            $pluginManager = $this->serverContext->getPluginManager();
            $layerNames = $pluginManager->layers->getRequestedLayerNames();
            
            $defaultQuerySelection = new QuerySelection();
            $defaultQuerySelection->selectedIds = array();
            $defaultQuerySelection->useInQuery  = true;
            $defaultQuerySelection->policy      = QuerySelection::POLICY_UNION;
            $defaultQuerySelection->maskMode = $requ->defaultMaskMode;
            $defaultQuerySelection->tableFlags = $requ->defaultTableFlags;
            foreach ($layerNames as $layerName) {
            
                $querySelection = clone($defaultQuerySelection);
                $querySelection->layerId = $layerName;
                foreach ($querySelections as $requQuerySelection) {
                    if ($requQuerySelection->layerId == $layerName) {
                        $querySelection = $requQuerySelection;

                        // In this case, all layers used in query
                        $querySelection->useInQuery = true;                                               
                    }
                }
                $tables[] = $this->queryLayer($requ->bbox, $querySelection);
                $layersOk[] = $layerName;
                $hilightQuerySelections[$layerName] = $querySelection;
            }            
        }
        foreach ($querySelections as $querySelection) {
            
            if (!in_array($querySelection->layerId, $layersOk)) {
            
                $tables[] = $this->queryLayer($requ->bbox, $querySelection);                
                $layersOk[] = $querySelection->layerId;
                $hilightQuerySelections[$querySelection->layerId]
                                                            = $querySelection;
            }
        }

        $queryResult = new QueryResult();
                
        $queryResult->tableGroup = new TableGroup();
        $queryResult->tableGroup->groupId = "query";
        $queryResult->tableGroup->groupTitle = "Query";
        $queryResult->tableGroup->tables = $tables;

        $this->hilight($tables, $hilightQuerySelections);
        
        return $queryResult;
    }    
}

?>