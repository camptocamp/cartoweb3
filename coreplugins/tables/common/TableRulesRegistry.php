<?php
/**
 * @package CorePlugins
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Defines a position in list of columns
 * @package CorePlugins
 */
class ColumnPosition {

    const TYPE_ABSOLUTE = 0;   
    const TYPE_RELATIVE = 1; 
    
    /**
     * Type of position (absolute or relative)
     * @var int
     */
    public $type;
    
    /**
     * Absolute index or offset (if type is relative)
     * @var int
     */
    public $index;
    
    /**
     * If type is relative, column Id to count the offset from
     * @var string
     */
    public $columnId;    
    
    /**
     * @param int
     * @param int
     * @param string
     */
    function __construct($type, $index, $columnId = '') {
        $this->type = $type;
        $this->index = $index;
        $this->columnId = $columnId;
    }
}

/**
 * Base rule class
 * @package CorePlugins
 */
abstract class BaseRule {

    const WEIGHT_NO_MATCH = 0;
    
    /**
     * @var Logger
     */
    protected $log;

    function __construct() {
        $this->log =& LoggerManager::getLogger(get_class($this));
    }
}

/**
 * Base rule for rules that will be applied on groups of tables
 * @package CorePlugins
 */
abstract class GroupRule extends BaseRule {

    const WEIGHT_GROUP_SINGLE  = 256;
    const WEIGHT_GROUP_PARTIAL = 128;
    const WEIGHT_GROUP_GLOBAL  = 64;

    /**
     * @var string
     */
    public $groupId;

    /**
     * Computes weight
     *
     * Weight is used to decide which rule will be executed. Group rules can
     * be defined with following parameters:
     * <ul>
     * <li>'*': rule applies on all groups</li>
     * <li>'foo*': rule applies on groups with id starting with 'foo'</li>
     * <li>'bar': rule applies on group with id = 'bar'</li>
     * </ul>
     * @param string
     * @return int
     */
    protected function getWeight($groupId) {
    
        if ($this->groupId == '*') {
            return self::WEIGHT_GROUP_GLOBAL;
        } else if ($this->groupId == $groupId) {
            return self::WEIGHT_GROUP_SINGLE;
        } else if (substr($this->groupId, -1) == '*' &&
                   substr($this->groupId, 0, strlen($this->groupId) -1) ==
                   substr($groupId, 0, strlen($this->groupId) -1)) {
            return self::WEIGHT_GROUP_PARTIAL;
        }
        return self::WEIGHT_NO_MATCH;
    }

    /**
     * Stores computed weights
     *
     * The array which contains weights has the following structure:
     * <pre>
     * Array ([0] => Array (['rule'] => Object (),
     *                      ['weights'] => Array (['param1'] => weight1,
     *                                            ['param2'] => weight2) ),
     *        [1] => Array (['rule'] => Object (),
     *                      ['weights'] => Array (['param3'] => weight3,
     *                                            ['param4'] => weight4) ) )
     * </pre>
     * For most rules, there is only one element in the array and only one
     * rule and weight defined. See {@link ColumnAdder} for an example of a
     * more complicated rule. 
     * @param int
     * @param array
     */
    protected function addWeight($weight, &$weights) {

        $oldWeight = self::WEIGHT_NO_MATCH;
        if (count($weights) == 1) {
            $oldWeight = $weights[0]['weights']['dummy'];
        }
        if ($weight > self::WEIGHT_NO_MATCH && $weight >= $oldWeight) {
            $weights[0] = array('rule' => $this, 
                                'weights' => array('dummy' => $weight));
        }
    }

    /**
     * Checks a rule
     * @param string
     * @param array
     */
    function checkRule($groupId, &$weights) {
    
        $this->log->debug("Checking rule " . get_class($this) 
                          . " (group " . $this->groupId . ")");
                                  
        $weight = $this->getWeight($groupId);
        
        $this->addWeight($weight, $weights);        
    }

    /**
     * Executes a rule on a group
     *
     * Parameters are taken from the weights structure. 
     * @param TableGroup
     * @param array
     * @see GroupRule::addWeight()
     */
    function applyRule($group, $params) {}
    
    /**
     * Applies a set of rules on a group
     * @param array array of GroupRule
     * @param TableGroup
     */
    static function applyRules($rules, $group) {
        $weights = array();
        foreach ($rules as $rule) {
            $rule->checkRule($group->groupId, $weights);
        }
        foreach ($weights as $weight) {
            $weight['rule']->applyRule($group, array_keys($weight['weights']));
        }
    }
}

/**
 * Base rule for rules that will be applied on tables
 * @package CorePlugins
 */
abstract class TableRule extends GroupRule {
   
    const WEIGHT_TABLE_SINGLE  = 32;
    const WEIGHT_TABLE_PARTIAL = 16;
    const WEIGHT_TABLE_GLOBAL  = 8;
        
    /**
     * @var string
     */
    public $tableId;

    /**
     * Computes weight
     * @param string
     * @param string
     * @return int
     * @see GroupRule::getWeight()
     */
    protected function getWeight($groupId, $tableId) {

        $weight = parent::getWeight($groupId);
        if ($weight > self::WEIGHT_NO_MATCH) {
            if ($this->tableId == '*') {
                return $weight + self::WEIGHT_TABLE_GLOBAL;
            } else if ($this->tableId == $tableId) {
                return $weight + self::WEIGHT_TABLE_SINGLE;
            } else if (substr($this->tableId, -1) == '*' &&
                       substr($this->tableId, 0, strlen($this->tableId) -1) ==
                       substr($tableId, 0, strlen($this->tableId) -1)) {
                return $weight + self::WEIGHT_TABLE_PARTIAL;
            }
        }
        return self::WEIGHT_NO_MATCH;
    }

    /**
     * Checks a rule
     * @param string
     * @param string
     * @param array
     */
    function checkRule($groupId, $tableId, &$weights) {
    
        $this->log->debug("Checking rule " . get_class($this) 
                          . " (group " . $this->groupId 
                          . ", table " . $this->tableId . ")");
                                  
        $weight = $this->getWeight($groupId, $tableId);
        
        $this->addWeight($weight, $weights);        
    }

    /**
     * Executes a rule on a table
     *
     * Parameters are taken from the weights structure. 
     * @param Table
     * @param array
     * @see GroupRule::addWeight()
     */
    function applyRule($table, $params) {}
    
    /**
     * Applies a set of rules on a table
     * @param array array of TableRule
     * @param string
     * @param Table
     */
    static function applyRules($rules, $groupId, $table) {
        $weights = array();
        foreach ($rules as $rule) {
            $rule->checkRule($groupId, $table->tableId, $weights);
        }
        foreach ($weights as $weight) {
            $weight['rule']->applyRule($table, array_keys($weight['weights']));
        }
    }

    /**
     * Returns a map of index id's to their offset in the columnIds array.
     * 
     * @param Table
     * @return array
     */
    protected function getIndexes($table) {
        $indexes = array();
        if (!empty($table->columnIds)) {
            foreach ($table->columnIds as $index => $columnId) {
                $indexes[$columnId] = $index;
            }
        }
        return $indexes;
    }
}

/**
 * Base rule for rules that will be applied on columns
 * @package CorePlugins
 */
abstract class ColumnRule extends TableRule {

    const WEIGHT_COLUMN_SINGLE  = 4;
    const WEIGHT_COLUMN_PARTIAL = 2;
    const WEIGHT_COLUMN_GLOBAL  = 1;

    /**
     * @var string
     */
    public $columnId;

    /**
     * Computes weight
     * @param string
     * @param string
     * @param string
     * @see TableRule::getWeight()
     */
    protected function getWeight($groupId, $tableId, $columnId) {

        $weight = parent::getWeight($groupId, $tableId);
        if ($weight > self::WEIGHT_NO_MATCH) {
            if ($this->columnId == '*') {
                return $weight + self::WEIGHT_COLUMN_GLOBAL;
            } else if ($this->columnId == $columnId) {
                return $weight + self::WEIGHT_COLUMN_SINGLE;
            } else if (substr($this->columnId, -1) == '*' &&
                       substr($this->columnId, 0, strlen($this->columnId) -1) ==
                       substr($columnId, 0, strlen($this->columnId) -1)) {
                return $weight + self::WEIGHT_COLUMN_PARTIAL;
            }
        }
        return self::WEIGHT_NO_MATCH;
    }

    /**
     * Checks a rule
     * @param string
     * @param string
     * @param string
     * @param array
     */
    function checkRule($groupId, $tableId, $columnId, &$weights) {
    
        $this->log->debug("Checking rule " . get_class($this) 
                          . " (group " . $this->groupId 
                          . ", table " . $this->tableId
                          . ", column " . $this->columnId . ")");

        $weight = $this->getWeight($groupId, $tableId, $columnId);

        $this->addWeight($weight, $weights);        
    }

    /**
     * Executes a rule on a column
     *
     * Parameters are taken from the weights structure. 
     * @param Table
     * @param string
     * @param array
     * @see TableRule::addWeight()
     */
    function applyRule($table, $columnId, $params) {}

    /**
     * Applies a set of rules on a column
     * @param array array of ColumnRule
     * @param string
     * @param string
     * @param Table
     */
    static function applyRules($rules, $groupId, $columnId, $table) {
        $weights = array();
        foreach ($rules as $rule) {
            $rule->checkRule($groupId, $table->tableId, $columnId, $weights);
        }
        foreach ($weights as $weight) {
            $weight['rule']->applyRule($table, $columnId,
                                       array_keys($weight['weights']));
        }
    }
}

/**
 * Base rule for rules that will be applied on cells
 * @package CorePlugins
 */
abstract class CellRule extends ColumnRule {    
}

/**
 * Rule to keep only a set of columns (include)
 * @package CorePlugins
 */
class ColumnSelector extends TableRule {
    
    /**
     * @var array
     */
    public $columnIds;

    /**
     * @param string
     * @param string
     * @param array
     */
    function __construct($groupId, $tableId, $columnIds) {
        parent::__construct();
        $this->groupId   = $groupId;
        $this->tableId   = $tableId;
        $this->columnIds = $columnIds;        
    }
    
    /** 
     * Keeps or exclude columns
     */
    protected function selectColumns($table, $exclude = false) {

        $indexes = $this->getIndexes($table);
                
        if ($exclude) {
            $ids = array_diff($table->columnIds, $this->columnIds);
            if (in_array('row_id', $this->columnIds)) {
                $table->noRowId = true;
            }
        } else {
            $ids = array_intersect($table->columnIds, $this->columnIds);
            if (!in_array('row_id', $this->columnIds)) {
                $table->noRowId = true;
            }
        }
        $newIds = array();
        $newTitles = array();
        $oldIndexes = array();
        foreach ($ids as $columnId) {
            $newIds[] = $columnId;
            $newTitles[] = $table->columnTitles[$indexes[$columnId]];
            $oldIndexes[] = $indexes[$columnId];
        }

        $table->columnIds = $newIds;
        $table->columnTitles = $newTitles;
        
        foreach($table->rows as $key => $row) {
            $newCells = array();
            foreach($oldIndexes as $oldIndex) {
                $newCells[] = $row->cells[$oldIndex];
            }
            $row->cells = $newCells;
        }
    }
    
    /**
     * Executes a rule on a table
     * @param Table
     * @param array
     */
    function applyRule($table, $params) {
        
        $this->selectColumns($table);
    }
}

/**
 * Rule to keep only a set of columns (exclude)
 * @package CorePlugins
 */
class ColumnUnselector extends ColumnSelector {

    /**
     * Executes a rule on a table
     * @param Table
     * @param array
     */
    function applyRule($table, $params) {
        
        $this->selectColumns($table, true);
    }
}

/**
 * Rule to modify group title
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('group_id', 'group_title')
 *   return 'group_new_title' 
 * </pre>
 * @package CorePlugins
 */
class GroupFilter extends GroupRule {

    /**
     * Callback method
     *
     * Syntax is:
     * <pre>
     * array('PluginClass', 'myCallbackMethod')
     * </pre>
     * @var array
     */
    public $callback;
    
    /**
     * @param string
     * @param array
     */
    function __construct($groupId, $callback) {
        parent::__construct();
        $this->groupId  = $groupId;
        $this->callback = $callback;        
    }    

    /**
     * Execute a rule on a group
     * @param TableGroup
     * @param array
     */
    function applyRule($group, $params) {
        $group->groupTitle = call_user_func($this->callback,
                                            $group->groupId,
                                            $group->groupTitle);
    }
}

/**
 * Rule to modify table title
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('table_id', 'table_title')
 *   return 'table_new_title' 
 * </pre>
 * @package CorePlugins
 */
class TableFilter extends TableRule {

    /**
     * Callback method
     *
     * Syntax is:
     * <pre>
     * array('PluginClass', 'myCallbackMethod')
     * </pre>
     * @var array
     */
    public $callback;
    
    /**
     * @param string
     * @param string
     * @param array
     */
    function __construct($groupId, $tableId, $callback) {
        parent::__construct();
        $this->groupId   = $groupId;
        $this->tableId  = $tableId;
        $this->callback = $callback;        
    }    

    /**
     * Execute a rule on a table
     * @param Table
     * @param array
     */
    function applyRule($table, $params) {
        $table->tableTitle = call_user_func($this->callback,
                                            $table->tableId,
                                            $table->tableTitle);
    }
}

/**
 * Rule to modify columns title
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('table_id', 'column_id', 'column_title')
 *   return 'column_new_title'
 * </pre>
 * @package CorePlugins
 */
class ColumnFilter extends ColumnRule {
    
    /**
     * Callback method
     *
     * Syntax is:
     * <pre>
     * array('PluginClass', 'myCallbackMethod')
     * </pre>
     * @var array
     */
    public $callback;

    /**
     * @param string
     * @param string
     * @param string
     * @param array
     */
    function __construct($groupId, $tableId, $columnId, $callback) {
        parent::__construct();
        $this->groupId   = $groupId;
        $this->tableId  = $tableId;
        $this->columnId = $columnId;
        $this->callback = $callback;        
    }    

    /**
     * Execute a rule on a column
     * @param Table
     * @param string
     * @param array
     */
    function applyRule($table, $columnId, $params) {

        $indexes = $this->getIndexes($table);
        $table->columnTitles[$indexes[$columnId]] =
                    call_user_func($this->callback, $table->tableId, $columnId,
                                   $table->columnTitles[$indexes[$columnId]]);
    }
} 

/**
 * Rule to modify content of cells one by one
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('table_id', 'column_id',
 *                                  array ('column_1' => 'value_1',
 *                                         'column_2' => 'value_2'))
 *   return 'cell_value'
 * </pre>
 * @package CorePlugins
 */
class CellFilter extends CellRule {
    
    /**
     * Ids of columns used by callback method to compute new value
     * @var array
     */
    public $inputColumnIds;

    /**
     * Callback method
     *
     * Syntax is:
     * <pre>
     * array('PluginClass', 'myCallbackMethod')
     * </pre>
     * @var array
     */
    public $callback;

    /**
     * @param string
     * @param string
     * @param string
     * @param array
     * @param array
     */
    function __construct($groupId, $tableId, $columnId,
                         $inputColumnIds, $callback) {
        parent::__construct();
        $this->groupId        = $groupId;
        $this->tableId        = $tableId;
        $this->columnId       = $columnId;
        $this->inputColumnIds = $inputColumnIds;
        $this->callback       = $callback;        
    }    

    /**
     * Execute a rule on cells of a column
     * @param Table
     * @param string
     * @param array
     */
    function applyRule($table, $columnId, $params) {
        
        if ($table->numRows == 0) {
            return;
        }
        $indexes = $this->getIndexes($table);
        if (is_null($this->inputColumnIds))
            $this->inputColumnIds = array_merge($table->columnIds, array('row_id'));
        foreach ($table->rows as $row) {           
            $inputValues = array(); 
            foreach ($row->cells as $index => $value) {
                if (in_array($table->columnIds[$index],
                             $this->inputColumnIds)) {
                    $inputValues[$table->columnIds[$index]] = $value;
                }
            } 
            if (in_array('row_id', $this->inputColumnIds)) {
                $inputValues['row_id'] = $row->rowId;
            }
            $row->cells[$indexes[$columnId]] =
                call_user_func($this->callback, $table->tableId,
                               $columnId, $inputValues);
        }
    }
}

/**
 * Rule to modify content of all cells
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('table_id', 'column_id',
 *                                  array (
 *                                  '0' => array (
 *                                         'column_1' => 'value_1_row_1',
 *                                         'column_2' => 'value_2_row_1'),
 *                                  '1' => array (
 *                                         'column_1' => 'value_1_row_2',
 *                                         'column_2' => 'value_2_row_2') ) )
 *   return array ('0' => 'cell_value_row_1', '1' => 'cell_value_row_2') 
 * </pre>
 * @package CorePlugins
 * @see CellFilter
 */
class CellFilterBatch extends CellFilter {
    
    /**
     * Execute a rule on cells of a column
     * @param Table
     * @param string
     * @param array
     */
    function applyRule($table, $columnId, $params) {
    
        if ($table->numRows == 0) {
            return;
        }
        $indexes = $this->getIndexes($table);
        $inputValues = array();
        if (is_null($this->inputColumnIds))
            $this->inputColumnIds = array_merge($table->columnIds, array('row_id'));
        foreach ($table->rows as $row) {
            $inputValuesRow = array();
            foreach ($row->cells as $index => $value) {
                if (in_array($table->columnIds[$index], $this->inputColumnIds)) {
                    $inputValuesRow[$table->columnIds[$index]] = $value;
                }
            } 
            if (in_array('row_id', $this->inputColumnIds)) {
                $inputValuesRow['row_id'] = $row->rowId;
            }
            $inputValues[] = $inputValuesRow;
        }
        $result = call_user_func($this->callback, $table->tableId, 
                                 $columnId, $inputValues);
        foreach ($result as $key => $resultValue) {
            $table->rows[$key]->cells[$indexes[$columnId]] = $resultValue;
        }
    }
}

/**
 * Rule to remove a set of rows in a table
 */
class RowUnselector extends TableRule {

    /**
     * @var array list of values for which the rows will be removed if it matched 
     *  in column columnId.
     */
    private $rowIds;

    /**
     * @param string
     * @param string
     * @param string
     * @param array
     */
    function __construct($groupId, $tableId, $columnId, $rowIds) {
        parent::__construct();
        $this->groupId   = $groupId;
        $this->tableId   = $tableId;
        $this->columnId  = $columnId;        
        $this->rowIds    = $rowIds;        
    }    
    
    /**
     * Execute a rule on each rows of a table
     * @param Table
     * @param string
     * @param array
     */
    function applyRule($table, $columnId) {
        if ($table->numRows == 0) {
            return;
        }

        $indexes = $this->getIndexes($table);
        $isRowId = $this->columnId == 'row_id';
        if (!$isRowId)
            $columnIndex = $indexes[$this->columnId];

        $rows = array();
        foreach ($table->rows as $index => $row) {
            $value = $isRowId ? $row->rowId : $row->cells[$columnIndex];
            if (in_array($value, $this->rowIds)) {            
                $table->numRows--;
            } else {
                $rows[] = $row;
            }
        }
        $table->rows = $rows;
    }
}

/**
 * Rule to add one or more columns and compute content of cells one by one
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('table_id',
 *                                  array ('column_1' => 'value_1',
 *                                         'column_2' => 'value_2'))
 *   return array ('new_column_1' => 'cell_value_1',
 *                 'new_column_2' => 'cell_value_2') 
 * </pre>
 * @package CorePlugins
 * @see CellFilter
 */
class ColumnAdder extends TableFilter {
    
    /**
     * @var ColumnPosition
     */
    public $columnPosition;
    
    /**
     * @var array
     */
    public $newColumnIds;
    
    /**
     * @var array
     */
    public $inputColumnIds;

    /**
     * @param string
     * @param string
     * @param ColumnPosition
     * @param array
     * @param array
     * @param array
     */
    function __construct($groupId, $tableId, $columnPosition, $newColumnIds,
                         $inputColumnIds, $callback) {
        parent::__construct($groupId, $tableId, $callback);
        $this->columnPosition = $columnPosition;        
        $this->newColumnIds   = $newColumnIds;        
        $this->inputColumnIds = $inputColumnIds;        
    }    

    /**
     * Adds new columns into table definition of columns
     * @param Table
     * @param array
     */
    protected function addNewColumns($table, $columnIds) {
        $newColumnIds = array();
        $newColumnTitles = array();
        $index = null;
        if ($this->columnPosition->type == ColumnPosition::TYPE_ABSOLUTE) {
            $index = $this->columnPosition->index;            
            if ($index < 0) {
                $index += count($table->columnTitles) + 1;
            }
        } else {
            $i = 0;
            foreach ($table->columnIds as $columnId) {
                if ($columnId == $this->columnPosition->columnId) {
                    $index = $i;
                }
                $i++;
            }
            $index += $this->columnPosition->index;
        }
        if ($index < 0) {
            $index = 0;
        }
        if ($index < count($table->columnTitles)) {
            $i = 0;
            $indexes = $this->getIndexes($table);
            foreach ($table->columnIds as $columnId) {
                if ($i == $index) {
                    foreach ($columnIds as $newColumnId) {
                        $newColumnIds[] = $newColumnId;
                        $newColumnTitles[] = $newColumnId;
                    }
                }
                $i++;
                $newColumnIds[] = $columnId;
                $newColumnTitles[] = $table->columnTitles[$indexes[$columnId]];
            }
        } else {
            $newColumnIds = $table->columnIds;
            $newColumnTitles = $table->columnTitles;
            foreach ($columnIds as $newColumnId) {
                $newColumnIds[] = $newColumnId;
                $newColumnTitles[] = $newColumnId;
            }
        }
        $table->columnIds = $newColumnIds;
        $table->columnTitles = $newColumnTitles;
    }

    /**
     * Execute a rule on table
     * @param Table
     * @param array
     */
    function applyRule($table, $params) {
        
        $oldColumnIds = $table->columnIds;
        $oldIndexes = $this->getIndexes($table);
        $this->addNewColumns($table, $params);           
 
        if ($table->numRows == 0) {
            return;
        }
        if (is_null($this->inputColumnIds))
            $this->inputColumnIds = array_merge($oldColumnIds, array('row_id'));
        foreach ($table->rows as $row) {           
            $inputValues = array(); 
            foreach ($oldColumnIds as $columnId) {
                if (in_array($columnId, $this->inputColumnIds)) {
                    $inputValues[$columnId] = $row->cells[$oldIndexes[$columnId]];
                }
            } 
            if (in_array('row_id', $this->inputColumnIds)) {
                $inputValues['row_id'] = $row->rowId;
            }
            $result = call_user_func($this->callback, $table->tableId, $inputValues);
            $newCells = array();
            foreach ($table->columnIds as $index => $columnId) {
                if (array_key_exists($columnId, $result)) {
                    $newCells[$index] = $result[$columnId];
                } else {
                    $newCells[$index] = $row->cells[$oldIndexes[$columnId]];
                }
            }
            $row->cells = $newCells;
        }
    }

    /**
     * Stores computed weights
     *
     * Weights are stored in an array which has the structure described in
     * {@link TableRule::addWeight()}.
     *
     * When a rule with a greater weight is found, column Id is removed from
     * old rule and added to current rule. With standard rules, only one rule
     * is executed for each type of rule. ColumnAdder rule can be executed
     * several times, but only once with the same column Id.
     * @param int
     * @param array 
     */
    protected function addWeight($weight, &$weights) {

        foreach ($this->newColumnIds as $newColumnId) {
            $oldWeight = self::WEIGHT_NO_MATCH;
            $oldArrayKey = -1;
            $currentArrayKey = -1;
            foreach ($weights as $key => $ruleweight) {
                if (array_key_exists($newColumnId, $ruleweight['weights'])) {
                    $oldWeight = $ruleweight['weights'][$newColumnId];
                    $oldArrayKey = $key;
                }
                if ($ruleweight['rule'] == $this) {
                    $currentArrayKey = $key;
                }
            }
            if ($weight > self::WEIGHT_NO_MATCH && $weight >= $oldWeight) {
                
                if ($oldArrayKey >= 0) {
                    // remove column from old rule
                    $array = $weights[$oldArrayKey]['weights'];
                    unset($array[$newColumnId]);
                    if (count($array) > 0) {
                        $weights[$oldArrayKey]['weights'] = $array;
                    } else {
                        // Removed column was last column for this rule
                        unset($weights[$oldArrayKey]);
                    }
                }
                
                // Add column to current rule
                if ($currentArrayKey >= 0) {
                    $weights[$currentArrayKey]['weights'][$newColumnId] = $weight;
                } else {
                    $weights[] = array('rule' => $this, 
                                       'weights' => array($newColumnId => $weight));
                }
            }  
        }
    }
}

/**
 * Rule to add one or more columns and compute content of all cells
 *
 * Callback method should have the following signature:
 * <pre>
 * static function myCallbackMethod('table_id',
 *                                  array (
 *                                  '0' => array (
 *                                         'column_1' => 'value_1_row_1',
 *                                         'column_2' => 'value_2_row_1'),
 *                                  '1' => array (
 *                                         'column_1' => 'value_1_row_2',
 *                                         'column_2' => 'value_2_row_2') ) )
 *   return array (
 *          '0' => array (
 *                 'new_column_1' => 'cell_value_1_row_1',
 *                 'new_column_2' => 'cell_value_2_row_1'),
 *          '1' => array ( 
 *                 'new_column_1' => 'cell_value_1_row_2',
 *                 'new_column_2' => 'cell_value_2_row_2') ) )
 * </pre>
 * @package CorePlugins
 * @see CellFilter
 */
class ColumnAdderBatch extends ColumnAdder {

    /**
     * Execute a rule on table
     * @param Table
     * @param array
     */
    function applyRule($table, $params) {

        $oldColumnIds = $table->columnIds;
        $oldIndexes = $this->getIndexes($table);
        $this->addNewColumns($table, $params);
        
        if ($table->numRows == 0) {
            return;
        }
        $inputValues = array();
        if (is_null($this->inputColumnIds))
            $this->inputColumnIds = array_merge($oldColumnIds, array('row_id'));
        foreach ($table->rows as $row) {
            $inputValuesRow = array();
            foreach ($oldColumnIds as $columnId) {
                if (in_array($columnId, $this->inputColumnIds)) {
                    $inputValuesRow[$columnId] = $row->cells[$oldIndexes[$columnId]];
                }
            } 
            if (in_array('row_id', $this->inputColumnIds)) {
                $inputValuesRow['row_id'] = $row->rowId;
            }
            $inputValues[] = $inputValuesRow;
        }
        $result = call_user_func($this->callback, $table->tableId, $inputValues);
        foreach ($result as $key => $resultValue) {
            $newCells = array();
            foreach ($table->columnIds as $index => $columnId) {
                if (array_key_exists($columnId, $resultValue)) {
                    $newCells[$index] = $resultValue[$columnId];
                } else {
                    $newCells[$index] =
                        $table->rows[$key]->cells[$oldIndexes[$columnId]];
                }
            }
            $table->rows[$key]->cells = $newCells;
        }
    }
}

/**
 * Table rules registry
 *
 * Stores and executes table rules. The table rules allow to modify a Table
 * object. This is the list of existing types of rules:
 * <ul>
 * <li>ColumnSelector: keeps only a set of columns</li>
 * <li>TableFilter: modifies table title</li>
 * <li>ColumnFilter: modifies columns title</li>
 * <li>CellFilter: modifies content of cells one by one</li>
 * <li>CellFilterBatch: modifies content of all cells</li>
 * <li>ColumnAdder: adds one or more columns and computes content
 *     of cells one by one</li>
 * <li>ColumnAdderBatch: adds one or more columns column and computes content
 *     of all cells</li>
 * </ul>
 * @package CorePlugins
 */
class TableRulesRegistry {
    
    /**
     * All rules
     * <pre>
     * array ('Class_1' => array ('0' => Rule1, '1' => Rule2),
              'Class_2' => array ('0' => Rule3, '1' => Rule4) )
     * </pre>
     * @var array
     */
    private $rules = array();
    
    /**
     * @var Logger
     */
    private $log;

    function __construct() {
        $this->log =& LoggerManager::getLogger(get_class($this));
    }

    /**
     * Adds a rule in list
     * @param BaseRule
     */
    private function addRule($rule) {
        $ruleClass = get_class($rule);
        if (!array_key_exists($ruleClass, $this->rules)) {
            $this->rules[$ruleClass] = array();
        }
        $this->rules[$ruleClass][] = $rule;
    }
    
    /**
     * Adds a ColumnSelector rule
     * @param string
     * @param string
     * @param array
     */
    public function addColumnSelector($groupId, $tableId, $columnIds) {
       $rule = new ColumnSelector($groupId, $tableId, $columnIds);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a ColumnUnselector rule
     * @param string
     * @param string
     * @param array
     */
    public function addColumnUnselector($groupId, $tableId, $columnIds) {
       $rule = new ColumnUnselector($groupId, $tableId, $columnIds);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a GroupFilter rule
     * @param string
     * @param array
     */
    public function addGroupFilter($groupId, $callback) {
       $rule = new GroupFilter($groupId, $callback);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a TableFilter rule
     * @param string
     * @param string
     * @param array
     */
    public function addTableFilter($groupId, $tableId, $callback) {
       $rule = new TableFilter($groupId, $tableId, $callback);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a ColumnFilter rule
     * @param string
     * @param string
     * @param string
     * @param array
     */
    public function addColumnFilter($groupId, $tableId, $columnId, $callback) {
       $rule = new ColumnFilter($groupId, $tableId, $columnId, $callback);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a CellFilter rule
     * @param string
     * @param string
     * @param string
     * @param array
     * @param array
     */
    public function addCellFilter($groupId, $tableId, $columnId,
                                  $inputColumnIds, $callback) {
       $rule = new CellFilter($groupId, $tableId, $columnId,
                              $inputColumnIds, $callback);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a CellFilterBatch rule
     * @param string
     * @param string
     * @param string
     * @param array
     * @param array
     */
    public function addCellFilterBatch($groupId, $tableId, $columnId,
                                       $inputColumnIds, $callback) {
       $rule = new CellFilterBatch($groupId, $tableId, $columnId,
                                   $inputColumnIds, $callback);
       $this->addRule($rule); 
    }
    
    /**
     * Adds a RowUnselector rule
     * @param string
     * @param string
     * @param string
     * @param array
     */
    public function addRowUnselector($groupId, $tableId, $columnId, $rowIds) {
       $rule = new RowUnselector($groupId, $tableId, $columnId, $rowIds);
       $this->addRule($rule); 
    }
        
    /**
     * Adds a ColumnAdder rule
     * @param string
     * @param string
     * @param ColumnPosition
     * @param array
     * @param array
     * @param array
     */
    public function addColumnAdder($groupId, $tableId, $columnPosition, 
                                   $newColumnIds, $inputColumnIds, $callback) {
       $rule = new ColumnAdder($groupId, $tableId, $columnPosition, 
                               $newColumnIds, $inputColumnIds, $callback);
       $this->addRule($rule); 
    }

    /**
     * Adds a ColumnAdderBatch rule
     * @param string
     * @param string
     * @param ColumnPosition
     * @param array
     * @param array
     * @param array
     */
    public function addColumnAdderBatch($groupId, $tableId, $columnPosition, 
                                        $newColumnIds, $inputColumnIds,
                                        $callback) {
       $rule = new ColumnAdderBatch($groupId, $tableId, $columnPosition, 
                                    $newColumnIds, $inputColumnIds, $callback);
       $this->addRule($rule); 
    }

    /**
     * Main method to apply rules on a table
     *
     * Applies all table rules on tables and all column rules on columns.
     * @param array
     */
    public function applyRules($groups) {

        if (!is_array($groups)) {
            if (!($groups instanceof TableGroup)) {
                throw new CartocommonException("Argument type was wrong (should be a TableGroup)");
            }        
            $groups = array($groups);
        }

        // All groups
        foreach ($groups as $group) {
        
            // All types of class            
            foreach ($this->rules as $class => $rules) {

                if ($rules[0] instanceof ColumnRule) {
                
                    // All tables
                    foreach ($group->tables as $table) {
                        foreach ($table->columnIds as $columnId) {
                            call_user_func(array($class, 'applyRules'), $rules,
                                           $group->groupId, $columnId, $table);
                        }
                    }         
                } else if ($rules[0] instanceof TableRule) {
                
                    // All tables
                    foreach ($group->tables as $table) {
                        call_user_func(array($class, 'applyRules'), $rules, 
                                       $group->groupId, $table);
                    }
                } else if ($rules[0] instanceof GroupRule) {
                    call_user_func(array($class, 'applyRules'), $rules, $group);
                }        
            }
        }
        return $groups;
    } 
}

?>