<?php
/**
 * @package CorePlugins
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Table rules management
 */
require_once(CARTOCLIENT_HOME . 'coreplugins/tables/common/TableRulesRegistry.php');

/**
 * Client part of Tables plugin
 * 
 * Provides table rules registry to client plugins and manages display of 
 * tables.
 * @package CorePlugins
 */
class ClientTables extends ClientPlugin
                   implements GuiProvider {
                   
    /**
     * @var Logger
     */
    private $log;

    /**
     * Registry which contains all client side rules
     * @var TableRulesRegistry
     */ 
    private $tableRulesRegistry = null;
    
    /**
     * Tables to be displayed
     * @var array array of Table
     */
    private $tableGroups = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    
    /**
     * Returns current table rules registry
     * @return TableRulesRegistry
     */
    public function getTableRulesRegistry() {
        if (is_null($this->tableRulesRegistry)) {
            $this->tableRulesRegistry =  new TableRulesRegistry();
        }
        return $this->tableRulesRegistry;
    }
    
    /**
     * Adds table(s) to the list of tables to be displayed
     * @param mixed Table or array of Table     
     */
    public function addTableGroups($tables) {
        $newTableGroups = array();
        if (!is_array($tables)) {
            $newTableGroups[] = $tables;
        } else {
            $newTableGroups = $tables;
        }
        $newTableGroups = $this->getTableRulesRegistry()
                               ->applyRules($newTableGroups);    
        $newTableGroups = $this->translate($newTableGroups);
        $this->tableGroups = array_merge($this->tableGroups, $newTableGroups);
    }
    
    /**
     * Returns a table
     * @param string
     * @param string
     * @return Table
     */
    public function getTable($groupId, $tableId) {
        foreach ($this->tableGroups as $tableGroup) {
            if ($tableGroup->groupId == $groupId) {
                foreach ($tableGroup->tables as $table) {
                    if ($table->tableId == $tableId) {
                        return $table;
                    }
                }
            }
        }
        return null;
    }
    
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
    }

    /**
     * Sets URL to CSV export
     * @param Smarty
     */
    private function assignExportCsv($template) {
    
        $pluginManager = $this->cartoclient->getPluginManager();
        if (!empty($pluginManager->exportCsv)) {
            $template->assign(array('exportcsv_active' => true,
                                    'exportcsv_url' => $pluginManager->
                                                         exportCsv->
                                                         getExportScriptPath(),
                                    ));
        }
    }

    /**
     * Translates and decodes all tables strings
     * @param array
     * @return array array of translated table groups
     */
    private function translate($tableGroups) {
        
        foreach ($tableGroups as $tableGroup) {
            $tableGroup->groupTitle = I18n::gt($tableGroup->groupTitle);            
            if (empty($tableGroup->tables)) {
                continue;
            }            
            foreach ($tableGroup->tables as $table) {
                $table->tableTitle = I18n::gt($table->tableTitle);
                foreach ($table->columnTitles as $key => $columnTitle) {
                    $table->columnTitles[$key] = I18n::gt($columnTitle);                          
                }
                if ($table->numRows == 0) {
                    continue;
                }    
                foreach ($table->rows as $row) {            
                    $row->rowId = Encoder::decode($row->rowId);
                    $row->cells = Encoder::decode($row->cells);
                }
            }
        }
        return $tableGroups;
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        
        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $smarty->assign('tables', $this->tableGroups);
        
        $this->assignExportCsv($smarty);
        
        $output = $smarty->fetch('tables.tpl');
        
        $template->assign('tables_result', $output);
    }
}

?>
