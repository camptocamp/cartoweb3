<?php
/**
 * Classes to be used for tables management
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
       
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {

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

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
    
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

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
    
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
    
    /**
     * Returns an array of all row identifiers. Or an empty array of the table
     * has no row identifiers.
     * @return array
     */
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

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {    
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