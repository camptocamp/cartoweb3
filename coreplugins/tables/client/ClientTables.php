<?php
/**
 * @package CorePlugins
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
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

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    
    /**
     * Returns current table rules registry
     * @return TableRulesRegistry
     */
    function getTableRulesRegistry() {
        if (is_null($this->tableRulesRegistry)) {
            $this->tableRulesRegistry =  new TableRulesRegistry();
        }
        return $this->tableRulesRegistry;
    }
    
    /**
     * Adds table(s) to the list of tables to be displayed
     * @param mixed Table or array of Table     
     */
    function addTableGroups($tables) {
        if (!is_array($tables)) {
            $this->tableGroups[] = $tables;
        } else {
            $this->tableGroups = $this->tableGroups + $tables;
        }
    }
    
    function handleHttpPostRequest($request) {
    }

    function handleHttpGetRequest($request) {
    }

    private function assignExportCsv($template) {
    
        $pluginManager = $this->cartoclient->getPluginManager();
        if (!empty($pluginManager->exportCsv)) {
            $template->assign(array('exportcsv_active' => true,
                                    'exportcsv_url' =>
                                    $pluginManager->exportCsv->getExportScriptPath()));
        }
    }

    private function translate($tableGroups) {
        
        foreach ($tableGroups as $key1 => $tableGroup) {
            $tableGroups[$key1]->groupTitle =
                I18n::gt($tableGroup->groupTitle);            
            if (empty($tableGroup->tables)) {
                continue;
            }            
            foreach ($tableGroup->tables as $key2 => $table) {
                $tableGroup->tables[$key2]->tableTitle =
                    I18n::gt($table->tableTitle);
                if (empty($table->columnTitles)) {
                    continue;
                }            
                foreach ($table->columnTitles as $key3 => $columnTitle) {
                    $table->columnTitles[$key3] =
                        I18n::gt($columnTitle);                          
                }    
            }
        }
        return $tableGroups;
    }
    
    function renderForm(Smarty $template) {
    
        $filteredTables = $this->getTableRulesRegistry()
                               ->applyRules($this->tableGroups);    
        $filteredTables = $this->translate($filteredTables);
    
        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $smarty->assign('tables', $filteredTables);
        
        $this->assignExportCsv($smarty);
        
        $output = $smarty->fetch('tables.tpl');
        
        $template->assign('tables_result', $output);
    }
}

?>
