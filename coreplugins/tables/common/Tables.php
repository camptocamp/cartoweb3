<?php
/**
 * Classes to be used for tables management
 * @package CorePlugins
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');


/**
 * Flags used in requests for querying tables
 *
 * Plugins which wants to retrieve tables should use this class to tell
 * to Tables server plugin what it must return.
 * 
 * Add flags and management on server
 * @package CorePlugins
 */
class TableFlags extends Serializable {
       
    /**
     * If false, returns Ids only
     * @var boolean
     */
    public $returnAttributes;
    
    /**
     * If false, returns nothing
     * @var boolean
     */
    public $returnTable;
       
    function unserialize($struct) {

        $this->returnAttributes = Serializable::unserializeValue($struct,
                                        'returnAttributes', 'boolean');
        $this->returnTable = Serializable::unserializeValue($struct,
                                        'returnTable', 'boolean');
    }
}

/**
 * A table row
 * @package CorePlugins
 */
class TableRow extends Serializable {

    /**
     * @var string
     */
    public $rowId;

    /**
     * @var array array of string
     */
    public $cells;

    function unserialize($struct) {
    
        $this->rowId = Serializable::unserializeValue($struct, 'rowId');
        $this->cells = Serializable::unserializeArray($struct, 'cells');
    }
}

/**
 * A table
 * @package CorePlugins
 */
class Table extends Serializable {
    
    /**
     * @var string
     */
    public $tableId;
    
    /**
     * @var string
     */
    public $tableTitle;
    
    /**
     * @var int
     */
    public $numRows = 0;
    
    /**
     * @var int
     */
    public $totalRows = 0;
    
    /**
     * @var int
     */
    public $offset = 0;
    
    /**
     * @var array array of Ids
     */
    public $columnIds = array();
    
    /**
     * @var array array of titles
     */
    public $columnTitles = array();
        
    /**
     * @var boolean
     */
    public $noRowId;
    
    /**
     * @var array array of TableRow
     */
    public $rows = array();

    function unserialize($struct) {
    
        $this->tableId       = Serializable::unserializeValue($struct,
                                                             'tableId');
        $this->tableTitle    = Serializable::unserializeValue($struct,
                                                             'tableTitle');
        $this->numRows       = Serializable::unserializeValue($struct, 
                                                             'numRows',
                                                             'int');
        $this->totalRows     = Serializable::unserializeValue($struct, 
                                                             'totalRows',
                                                             'int');
        $this->offset        = Serializable::unserializeValue($struct, 
                                                             'offset',
                                                             'int');
        $this->columnIds     = Serializable::unserializeArray($struct,
                                                             'columnIds');
        $this->columnTitles  = Serializable::unserializeArray($struct,
                                                             'columnTitles');
        $this->noRowId       = Serializable::unserializeValue($struct, 
                                                             'noRowId',
                                                             'boolean');
        $this->rows          = Serializable::unserializeObjectMap($struct,
                                                                 'rows',
                                                                 'TableRow');
    }
    
    public function getIds() {
        $ids = array();
        if (!is_null($this->rows)) {
            foreach($this->rows as $row) {
                if (!empty($row->rowId)) {
                    $ids[] = $row->rowId;
                }
            }
        }
        return $ids;
    }
}


/**
 * A group of tables
 * @package CorePlugins
 */
class TableGroup extends Serializable {
    
    /**
     * @var string
     */
    public $groupId;

    /**
     * @var string
     */
    public $groupTitle;

    /**
     * @var array
     */
    public $tables;

    function unserialize($struct) {    
        $this->groupId    = Serializable::unserializeValue($struct,        
                                                        'groupId');
        $this->groupTitle = Serializable::unserializeValue($struct,        
                                                        'groupTitle');
        $this->tables     = Serializable::unserializeObjectMap($struct,
                                                               'tables',
                                                               'Table');
    }
}

?>