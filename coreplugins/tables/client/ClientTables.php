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

    function renderForm(Smarty $template) {
    
        $filteredTables = $this->getTableRulesRegistry()
                               ->applyRules($this->tableGroups);
    
        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $smarty->assign('tables', $filteredTables);
        $output = $smarty->fetch('tables.tpl');
        
        $template->assign('tables_result', $output);
    }
}

?>
