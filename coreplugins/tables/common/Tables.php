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
       
    function unserialize($struct) {
    }
}

/**
 * A table row
 * @package CorePlugins
 */
class TableRow extends Serializable {

    /**
     * @var array array of string (columnId => value)
     */
    public $cells;

    function unserialize($struct) {
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
    public $numRows;
    
    /**
     * @var int
     */
    public $totalRows;
    
    /**
     * @var int
     */
    public $offset;
    
    /**
     * @var array array of titles (columnId => columnTitle)
     */
    public $columnTitles;
    
    /**
     * @var array array of TableRow
     */
    public $rows;

    function unserialize($struct) {
    
        $this->tableId      = Serializable::unserializeValue($struct,
                                                             'tableId');
        $this->tableTitle   = Serializable::unserializeValue($struct,
                                                             'tableTitle');
        $this->numRows      = Serializable::unserializeValue($struct, 
                                                             'numRows',
                                                             'int');
        $this->totalRows    = Serializable::unserializeValue($struct, 
                                                             'totalRows',
                                                             'int');
        $this->offset       = Serializable::unserializeValue($struct, 
                                                             'offset',
                                                             'int');
        $this->columnTitles = Serializable::unserializeArray($struct,
                                                             'columnTitles');
        $this->rows         = Serializable::unserializeObjectMap($struct,
                                                                 'rows',
                                                                 'TableRow');
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
        $this->rows       = Serializable::unserializeObjectMap($struct,
                                                               'tables',
                                                               'Table');
    }
}

?>