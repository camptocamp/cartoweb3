<?php
/**
 * Example of table rules.
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
 * @copyright 2006 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Plugin to test tables rules creation
 * @package Tests
 */
class ClientTestMainTableRules extends ClientPlugin {
    
    /**
     * @var Logger
     */
    private $log;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Formats an external link.
     * @param string table name
     * @param array inputs
     * @return string cell content
     */
    public function computeQueryUrl($tableId, $inputValues) {
        $url = '';
        if (!empty($inputValues['FID'])) {
            $url = sprintf('<a href="%s%d" target="_blank">%s</a>',
                           'http://some/base/url?id=',
                           $inputValues['FID'],
                           I18n::gt('More'));
        }
        return array('url' => $url);
    }

    /**
     * Here comes all the client-side rules.
     */
    public function initialize() {
            
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;        
        $registry = $tablesPlugin->getTableRulesRegistry();

        if ($this->cartoclient->getOutputType() == 
            Cartoclient::OUTPUT_HTML_VIEWER) {
            $registry->addColumnAdder('query', 'POLYGON*',
                new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, -1),
                                   array('url'), array('FID'),
                                   array($this, 'computeQueryUrl'));
        }
    }    
}

?>
