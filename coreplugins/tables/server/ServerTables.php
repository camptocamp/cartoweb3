<?php
/**
 * @package CorePlugins
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Table rules management
 */
require_once(CARTOSERVER_HOME . 'coreplugins/tables/common/TableRulesRegistry.php');

/**
 * Server side of Tables plugin
 *
 * Provides table rules registry to server plugins.
 * @package CorePlugins
 */
class ServerTables extends ServerPlugin {

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
     * Applies rules on tables
     * @param array
     * @return array array of {@link TableGroup}
     */
    public function applyRules($tables) {
        return $this->getTableRulesRegistry()->applyRules($tables);
    }    
}

?>
