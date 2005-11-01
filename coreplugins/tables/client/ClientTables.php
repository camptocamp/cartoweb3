<?php
/**
 * Client tables plugin
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
 * Table rules management
 */
require_once(CARTOWEB_HOME . 'coreplugins/tables/common/TableRulesRegistry.php');

/**
 * Client part of Tables plugin
 * 
 * Provides table rules registry to client plugins and manages display of 
 * tables.
 * @package CorePlugins
 */
class ClientTables extends ClientPlugin
                   implements GuiProvider, AjaxPlugin {
                   
    /**
     * @var Logger
     */
    private $log;

    /**
     * Registry which contains all client side rules
     * @var TableRulesRegistry
     */ 
    protected $tableRulesRegistry = null;
    
    /**
     * Tables to be displayed
     * @var array array of Table
     */
    protected $tableGroups = array();

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
     * @param mixed TableGroup or array of TableGroup's     
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
     * Returns all table groups
     * @return Table
     */
    public function getTableGroups () {
        return $this->tableGroups;
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {}

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}

    /**
     * Gets CSV export URL from main exportCsv plugin. 
     * @return string
     */
    protected function getUrl() {
        return $this->cartoclient->getPluginManager()
                    ->getPlugin('exportCsv')->getUrl()
               . '&amp;';
    }

    /**
     * Sets URL to CSV export.
     * @param Smarty
     */
    protected function assignExportCsv(Smarty $template) {
    
        $pluginManager = $this->cartoclient->getPluginManager();
        if (!is_null($pluginManager->getPlugin('exportCsv'))) {
            $template->assign(array('exportcsv_active' => true,
                                    'exportcsv_url'    => $this->getUrl(),
                                    ));
        }
    }

    /**
     * Translates and decodes all tables strings
     * @param array
     * @return array array of translated table groups
     */
    protected function translate($tableGroups) {
        
        foreach ($tableGroups as $tableGroup) {
            if (!empty($tableGroup->groupTitle))
                $tableGroup->groupTitle = I18n::gt($tableGroup->groupTitle);            
            if (empty($tableGroup->tables)) {
                continue;
            }            
            foreach ($tableGroup->tables as $table) {
                if (!empty($table->tableTitle))
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
    
    public function renderFormPrepare() {
        
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign('tables', $this->tableGroups);
        
        $this->assignExportCsv($smarty);
        
        return $smarty->fetch('tables.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $output = $this->renderFormPrepare();        
        $template->assign('tables_result', $output);
    }

    public function ajaxResponse($ajaxPluginResponse) {
    	$ajaxPluginResponse->addHtmlCode('tableResult', $this->renderFormPrepare());
    }

	public function ajaxHandleAction($actionName, $pluginsDirectives) {
		switch ($actionName) {
			case 'Query.perform':
				
			break;
		}
	}	
  }

?>
