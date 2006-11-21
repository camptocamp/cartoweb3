<?php
/**
 * Query plugin, server
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
    protected $drawQuery = false;

    /**
     * Constructor
     */ 
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Returns list of attributes to be returned
     * @param string layer id
     * @return array
     */
    protected function getAttributes($layerId) {

        $msMapObj = $this->serverContext->getMapObj();

        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        if (empty($msLayer)) {
            return array();
        }
    
        $returnedAttributesMetadataName = 'query_returned_attributes';
        
        $retAttrString = $msLayer->getMetaData($returnedAttributesMetadataName);
        if (empty($retAttrString)) {
            // fallback to header property for compatibility

            $retAttrString = $msLayer->header;
            if (!empty($retAttrString))
                $this->log->warn('Using compatibility header property for layer'
                                 . " instead of $returnedAttributesMetadataName"
                                 . ' metadata field, please update your '
                                 . 'Mapfile!!');
        }
        if (empty($retAttrString)) {
            $this->log->warn('no filter for returned attributes, ' .
                             'returning everything');
            return array();
        }
        return explode(' ', $retAttrString);
    }

    /**
     * Returns true if query results must be drawn using Mapserver
     * @return boolean
     */
    public function drawQuery() {
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
    protected function resultToTable($result, $layerId, $idAttribute, 
                                   $attributes, $tableFlags) {
        
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $layer = $layersInit->getLayerById($layerId);

        $table = new Table();
        $table->tableId = $layerId;
        if (empty($layer->label)) {
            $table->tableTitle = $layerId;
        } else {
            $table->tableTitle = $layer->label;
        }
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
        if (empty($idAttribute) || $this->getConfig()->noRowId) {
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
                $table->columnIds = Encoder::encode($columnIds, 'config');
                $table->columnTitles = Encoder::encode($columnTitles, 'config');
            }
            
            $tableRow = new TableRow();
            if (!empty($idAttribute)) {
                $tableRow->rowId = Encoder::encode($shape->values[$idAttribute],
                                                   'config');
            }
            $cells = array();
            if (!is_null($tableFlags) && $tableFlags->returnAttributes) {
                foreach ($attributes as $columnId) {
                    $cells[] = $shape->values[$columnId];
                }
            }
            $tableRow->cells = Encoder::encode($cells, 'config');
            
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
    protected function mergeTables(Table $table1, Table $table2, $policy) {
        
        $resultTable = new Table();
        $resultTable->tableId = $table1->tableId;
        $resultTable->tableTitle = $table1->tableTitle;
        $resultTable->columnIds = $table1->columnIds;
        $resultTable->columnTitles = $table1->columnTitles;
        if ($policy == QuerySelection::POLICY_REPLACE) {
            
            $resultTable->rows = $table2->rows;
            $resultTable->numRows = $table2->numRows;
            return $resultTable;
        }
        
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
     * Query can be done using a {@link Shape}, a list of Ids, or both
     * @param Shape
     * @param QuerySelection
     * @return Table
     */
    protected function queryLayer($shape, $querySelection) {

        if (is_null($querySelection->tableFlags)) {
            $querySelection->tableFlags = new TableFlags;
            $querySelection->tableFlags->returnTable = false;
            $querySelection->tableFlags->returnAttributes = false;
        }
   
        if (!$querySelection->hilight && 
            !$querySelection->tableFlags->returnTable) {
            $table = new Table;
            $table->tableId = $querySelection->layerId;
            return $table;
        }

        if (!$querySelection->tableFlags->returnTable)
            $querySelection->tableFlags->returnAttributes = false;
        
        // Activates layer
        if (count($querySelection->selectedIds) > 0
            || $querySelection->useInQuery) {
            
            $msMapObj = $this->serverContext->getMapObj();
            $layersInit = $this->serverContext->getMapInfo()->layersInit;
            $msLayer = $layersInit->getMsLayerById($msMapObj,
                                                $querySelection->layerId);
            if (empty($msLayer)) {
                $table = new Table();
                $table->tableId = $querySelection->layerId;
                return $table;
            }                                                
            $msLayer->set('status', MS_ON);
        }
    
        // Attributes to be returned
        if ($querySelection->tableFlags->returnAttributes) {
            $attributes = $this->getAttributes($querySelection->layerId);
        } else {
            $attributes = array();
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

        $tableShape = null;        
        if (!is_null($shape) && $querySelection->useInQuery) {
            if (!empty($pluginManager->mapquery)) {
                  
                $resultShape = $pluginManager->mapquery->queryByShape(
                                                  $querySelection->layerId,
                                                  $shape);
                $tableShape = $this->resultToTable($resultShape,
                                                  $querySelection->layerId,  
                                                  $idAttribute,
                                                  $attributes,
                                                  $querySelection->tableFlags);           
            }                                    
        }

        if (is_null($tableIds) && is_null($tableShape)) {
            $table = new Table();
            $table->tableId = $querySelection->layerId;
            return $table;
        }
        if (is_null($tableIds)) {
            return $tableShape;
        }
        if (is_null($tableShape)) {
            return $tableIds;
        }

        return $this->mergeTables($tableIds, $tableShape,
                                  $querySelection->policy);
    }

    /**
     * Hilights query
     * @param array tables
     * @param array 
     */
    protected function hilight($tables, $hilightQuerySelections) {

        $pluginManager = $this->serverContext->getPluginManager();
        if ($this->getConfig()->drawQueryUsingHilight) {
            
            if (empty($pluginManager->hilight))
                throw new CartoserverException('hilight plugin not loaded, '
                    . 'and needed for the query hilight drawing');

            foreach ($tables as $table) {
                
                if ($table->numRows > 0) {
                    $querySelection = $hilightQuerySelections[$table->tableId];

                    if (!$querySelection->hilight)
                        continue;
                    
                    $querySelection->selectedIds = $table->getIds();
                    $pluginManager->hilight->hilightLayer($querySelection);
                }
            }

        } else {
            
            $layersInit = $this->serverContext->getMapInfo()->layersInit;
            $msMapObj = $this->serverContext->getMapObj();
            
            foreach ($tables as $table) {
                
                $querySelection = $hilightQuerySelections[$table->tableId]; 
            
                if (!$querySelection->hilight)
                    continue;
            
                // Checks if layer has Ids
                $serverLayer = $layersInit->getLayerById($table->tableId);
                $msLayer = $msMapObj->getLayerByName($serverLayer->msLayer);
                $this->serverContext->checkMsErrors();
                if (empty($msLayer)) {
                    continue;
                }
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
    public function handlePreDrawing($requ) {
        
        $this->log->debug('handlePreDrawing: ');
        $this->log->debug($requ);
        $layersOk = array();
        $tables = array();
        $noReturnTables = array();
        
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
            $defaultQuerySelection->maskMode    = $requ->defaultMaskMode;
            $defaultQuerySelection->hilight     = $requ->defaultHilight;
            $defaultQuerySelection->tableFlags  = $requ->defaultTableFlags;
            foreach ($layerNames as $layerName) {
            
                $querySelection = clone($defaultQuerySelection);
                $querySelection->layerId = $layerName;
                foreach ($querySelections as $requQuerySelection) {
                    if ($requQuerySelection->layerId == $layerName) {
                        $querySelection = $requQuerySelection;

                        // In this case, all layers are used in query
                        $querySelection->useInQuery = true;                                               
                    }
                }

                $msMapObj = $this->serverContext->getMapObj();
                $layerConnexType = $msMapObj->getLayerByName($layerName)->connectiontype;
                if ($layerConnexType == MS_WMS || $layerConnexType == MS_WFS ){
                    continue;
                }

                $tables[] = $this->queryLayer($requ->shape, $querySelection);
                $layersOk[] = $layerName;
                $hilightQuerySelections[$layerName] = $querySelection;
                if (!$querySelection->tableFlags->returnTable) {
                    $noReturnTables[] = $layerName;
                }
            }            
        }
        foreach ($querySelections as $querySelection) {
            
            if (!in_array($querySelection->layerId, $layersOk)) {
            
                $tables[] = $this->queryLayer($requ->shape, $querySelection);                
                $layersOk[] = $querySelection->layerId;
                $hilightQuerySelections[$querySelection->layerId]
                                                            = $querySelection;
                if (!$querySelection->tableFlags->returnTable) {
                    $noReturnTables[] = $querySelection->layerId;
                }
            }
        }

        $queryResult = new QueryResult();
                
        $queryResult->tableGroup = new TableGroup();
        $queryResult->tableGroup->groupId = 'query';
        $queryResult->tableGroup->groupTitle = I18nNoop::gt('Query');
        $queryResult->tableGroup->tables = $tables;

        // Applies the registred table rules
        $tablesPlugin = $this->getServerContext()->getPluginManager()->tables;        
        $groups = $tablesPlugin->applyRules($queryResult->tableGroup);
        $queryResult->tableGroup = $groups[0];

        $this->hilight($tables, $hilightQuerySelections);
       
        // Empties tables with returnTable = false attributes
        foreach ($queryResult->tableGroup->tables as &$table) {
            if (in_array($table->tableId, $noReturnTables)) {
                $table->numRows = 0;
                $table->rows = array();
            }
        }
        
        $this->account('server_version', 1);

        $resultsPerTable = array();
        foreach ($queryResult->tableGroup->tables as $t) {
            $resultsPerTable[] = sprintf('%s=%s', $t->tableId, $t->numRows);
        }
        $this->account('results_table_count', implode(';', $resultsPerTable));
        
        return $queryResult;
    }    
}

?>
