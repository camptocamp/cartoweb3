<?php
/**
 * Search plugin CwSerializable objects
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
 * @copyright 2007 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

class SearchUtils {
    
    /**
     * Reads a variable, returns array if multiple
     * @param string initial variable name
     * @param string initial content
     * @return value or array
     */
    static public function getValue($var, $val) {

        // Finds if variable is an array. Better way?
        if (substr($var, -1) == 's') {
            return Utils::parseArray($val);                    
        } else {
            return $val;
        }
    }
    
    /**
     * Gets Result provider or Response formatter frmo a config structure
     * @param string 'ResultProvider' or 'ResponseFormatter'
     * @param config structure
     * @param array default values
     * @param optional parameter for class constructor
     * @return ResultProvider/ResponseFormatter
     */
    static public function getFromConfig($type, $config,
                                         $defaultValues, $plugin) {

        if (!isset($config->type)) {
            throw new CartocommonException("$type has no type");
        }
        
        $className = ucfirst($config->type) . $type;
        if (!class_exists($className)) {
            throw new CartocommonException("$type $className does not exist");
        }
        
        $newObject = new $className($plugin);
        
        foreach ($config as $var => $val) {
            if ($var != 'type') {
                $newObject->$var = SearchUtils::getValue($var, $val);
            }
        }
        
        // Default values
        foreach ($defaultValues as $var => $val) {
            if (array_key_exists($var, get_class_vars($className)) &&
                !isset($newObject->$var)) {
                $newObject->$var = $val;                
            }
        }        
        
        return $newObject;
    }
}

/**
 * Provides results
 * Allows to use several sources (i.e. DB, shapefiles, etc.) for one application.
 */
abstract class ResultProvider {
    
    /**
     * @var PluginBase plugin
     */
    protected $plugin;
        
    /**
     * @var int maximum number of results
     */
    public $maxNumber;
        
    /**
     * @var string ID column
     */
    public $id;
    
    /**
     * @var string[] column names
     */
    public $columns;
    
    /**
     * @param PluginBase plugin
     */
    public function __construct(PluginBase $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Generates search results from a request
     * @param SearchRequest
     * @return SearchResult
     */
    abstract public function getResult(SearchRequest $request);
    
    /**
     * Creates a ResultProvider from a config structure
     * @param config structure
     * @return ResultProvider
     */
    static public function getProviderFromConfig($config,
                                                 $defaultValues,
                                                 $plugin) {
                               
        if (isset($config->type) && $config->type == 'server') {
            return NULL;
        }
        
        return SearchUtils::getFromConfig('ResultProvider', $config,
                                          $defaultValues, $plugin);
    }    
}

/**
 * Provides result from a DB
 * @see ResultProvider
 */
class DbResultProvider extends ResultProvider {
    
    /**
     * @var string Database type (pgsql, sqlite, ...)
     */
    public $dbType;
    
    /**
     * @var string Database connection string
     */
    public $dbConnection;
    
    /**
     * @var string Database file path
     */
    public $dbFile;
    
    /**
     * @var string DSN
     */
    public $dsn;
    
    /**
     * @var string context for the encoding
     */
    public $encodingContext;
    
    /**
     * @var string SQL query
     */
    public $sql;
    
    /**
     * @var string default sort column
     */
    public $sortColumn;
    
    /**
     * @var string default sort direction
     */
    public $sortDirection;
    
    /**
     * @var array defines order for other sort columns 
     */
    public $sortPriorities;
     
    /**
     * @var DB connection
     */
    protected $db;
    
    /**
     * @return string
     */
    protected function getDsn() {
        
        if (!is_null($this->dsn)) {
            return $this->dsn;
        }
        $dsn = '';
        if (is_null($this->dbType)) {
            throw new CartocommonException('Either DSN or DB type must be defined');
        }
        $dsn .= $this->dbType . '://';
        if (!is_null($this->dbConnection)) {
            $dsn .= $this->dbConnection;
        } else if (!is_null($this->dbFile)) {
            $dsn .= '/' . CARTOWEB_HOME . $this->dbFile;
        }        
        return $dsn;
    }
    
    /**
     * @param SearchRequest
     * @return string
     */
    protected function getOrderBy(SearchRequest $request) {

        $sortColumn = $request->getParameter('sort_column');
        $sortDirection = $request->getParameter('sort_direction');
        if (is_null($sortColumn)) {
            if (is_null($this->sortColumn)) {
                return '';
            }
            $sortColumn = $this->sortColumn;
        }
        if (is_null($sortDirection)) {
            if (is_null($this->sortDirection)) {                
                $sortDirection = '';
            }
            $sortDirection = $this->sortDirection;
        }
        $sortClause = $sortColumn . ' ' . $sortDirection;
        if (count($this->sortPriorities) == 0) {
            return $sortClause;
        }
        foreach ($this->sortPriorities as $column) {
            if ($column != $sortColumn) {
                $sortClause .= ', ' . $column;
            }
        }                        
        return $sortClause;
    }
    
    /**
     * @param SearchRequest
     * @return string
     */
    protected function getSql(SearchRequest $request) {
        
        $orderBy = $this->getOrderBy($request);
        if ($orderBy != '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }        
        return $sql;
    }
    
    /**
     * @see ResultProvider::getResult()
     */
    public function getResult(SearchRequest $request) {
        
        $sql = $this->getSql($request);
        foreach ($request->parameters as $parameter) {
            $sql = str_replace('@' . $parameter->key . '@', 
                               Encoder::decode($parameter->value, 
                                               $this->encodingContext),
                               $sql);
        }

        $number = $request->getParameter('number');
        if (!is_null($this->maxNumber) && 
            (is_null($number) || $number > $this->maxNumber)) {
                
            // Global max number was set and number not set or larger
            $number = $this->maxNumber;
        }
        $offset = $request->getParameter('offset');
        $page = $request->getParameter('page');
        if (!is_null($page)) {
            $offset = ($page - 1) * $number;            
        } else if (is_null($offset)) {
            $offset = 0;            
        }

        Utils::getDb($this->db, $this->getDsn());
        $totalNumber = NULL;
        if (!is_null($number)) {
            $dbResult = $this->db->limitQuery($sql, $offset, $number);

            // Retrieving total number of rows            
            $dbTotalResult = $this->db->query($sql);
            Utils::checkDbError($dbTotalResult, 'Failed executing total SQL query');
            $totalNumber = $dbTotalResult->numRows();
        } else {
            
            // Simple query without pages
            $dbResult = $this->db->query($sql);
        }    
        Utils::checkDbError($dbResult, 'Failed executing search SQL query');
                
        $table = new Table();
        $table->tableId = 'search';
        $table->columnIds = $this->columns;
        $table->noRowId = false;
        $table->rows = array();
        $table->numRows = $dbResult->numRows();  
        if (!is_null($totalNumber)) {
            $table->totalRows = $totalNumber;
        } else {
            $table->totalRows = $table->numRows;
        } 
        $table->offset = $offset;
        if (!is_null($number)) {
            
            // Generates pages information
            $table->rowsPage = $number;
            $table->totalPages = floor(($table->totalRows - 1) / $table->rowsPage) + 1;
            $table->page = floor($table->offset / $table->rowsPage) + 1;
        }
        $row = NULL;
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $newRow = new TableRow();
            $newRow->rowId = $row[$this->id];
            foreach ($this->columns as $column) {
                $newRow->cells[] = Encoder::encode($row[$column],
                                                   $this->encodingContext);
            }
            $table->rows[] = $newRow;
        }

        $result = new SearchResult();
        $result->table = $table;

        return $result;
    }
}

/**
 * Provides result from a table
 * @see DbResultProvider
 */
class TableResultProvider extends DbResultProvider {
    
    /**
     * @var string table name
     */
    public $table;
    
    /**
     * @var string where clause
     */
    public $where;
    
    /**
     * @return string
     */
    protected function getWhere(SearchRequest $request) {
        return $this->where;
    }
    
    protected function getSql(SearchRequest $request) {
        
        $columns = array_merge(array($this->id), $this->columns);
        $columns = implode(', ', $columns);
        $sql = 'SELECT DISTINCT ' . $columns . ' FROM ' . $this->table;
        $where = $this->getWhere($request);
        if ($where != '') {
            $sql .= ' WHERE ' . $where;
        }            
        $orderBy = $this->getOrderBy($request);
        if ($orderBy != '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return $sql; 
    }
}

/**
 * Provides result from a table with full text search in fields
 * @see TableResultProvider
 */
class FulltextTableResultProvider extends TableResultProvider {
    
    /**
     * @var string[] list of columns for full text search
     */
    public $fulltextColumns;
    
    /**
     * @return string
     */
    protected function getWhere($request) {
        
        $where = '';
        foreach ($this->fulltextColumns as $column) {
            if ($where != '') {
                $where .= ' AND ';
            }
            $where .= "$column LIKE '%@$column@%'";
        }
        return $where;
    }
}

class SearchParameter extends CwSerializable {
    
    /**
     * @var string Parameter key
     */
    public $key;
    
    /**
     * @var string Parameter value
     */
    public $value;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->key = self::unserializeValue($struct, 'key');
        $this->value = self::unserializeValue($struct, 'value');
    }    
}

/**
 * Request
 * @package Plugins
 */
class SearchRequest extends CwSerializable {    
        
    /**
     * @var string Search configuration
     */
    public $config;
        
    /**
     * @var array array of parameters
     */
    public $parameters;
    
    public function __construct() {
        $this->parameters = array();
    }
    
    /**
     * Parameter getter
     * @param string index
     * @return string value
     */
    public function getParameter($index) {
        
        foreach ($this->parameters as $param) {
            if ($param->key == $index) {
                if ($param->value == '') {
                    return NULL;
                }
                return $param->value;
            }
        }
        return NULL;
    }    
    
    /**
     * Parameter setter
     * @param string index
     * @param string value
     */
    public function setParameter($index, $value) {

        foreach ($this->parameters as $param) {
            if ($param->key == $index) {
                $param->value = $value;
                return;
            }
        }
        $newParam = new SearchParameter();
        $newParam->key = $index;
        $newParam->value = $value;
        $this->parameters[] = $newParam;
    }

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->config = self::unserializeValue($struct, 'config');        
        $this->parameters = self::unserializeObjectMap($struct,
                                                       'parameters',
                                                       'SearchParameter');

    }    
}

/**
 * Result
 * @package Plugins
 */
class SearchResult extends CwSerializable {

    /**
     * @var Table
     */
    public $table;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->table = self::unserializeObject($struct, 'table', 'Table');
    }
}

?>
