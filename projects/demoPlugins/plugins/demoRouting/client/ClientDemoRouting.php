<?php
/**
 * Routing plugin, client extension
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
 * @package Plugins
 * @version $Id$
 */

/**
 * Example client routing plugin which uses Postgres
 * @package Plugins
 */
class ClientDemoRouting extends ClientRouting {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * List of possible towns
     * @var array
     */
    protected $vertices_options;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->routingState = new RoutingState();
        $this->log =& LoggerManager::getLogger(__CLASS__);
        require_once('DB.php');
    }
    
    /**
     * @see PluginManager::replacePlugin()
     */
    public function replacePlugin() {
        return 'routing';
    }
    
    /**
     * Database connection
     * @param boolean
     */
    protected function getDb() {
        if (!isset($this->db)) {
            $dsn = $this->getConfig()->dsn;
            $this->db = DB::connect($dsn);
            Utils::checkDbError($this->db, 'Error connecting names database'); 
        }
        return $this->db;
    }
    
    /**
     * Retrieves list of names from database
     * @return array
     */
    protected function TownsList (){
        $stepName = $this->getConfig()->stepName;
        $postgresRoutingVerticesTable = $this->getConfig()
                                             ->postgresRoutingVerticesTable;
        $sql = sprintf("SELECT %s, geom_id FROM %s WHERE %s != '' ORDER BY %s",
                       $stepName,
                       $postgresRoutingVerticesTable,
                       $stepName,
                       $stepName);
        $this->getDb();
        $res = $this->db->query($sql);
        Utils::checkDbError($res, 'Error quering routing database');
        while ($res->fetchInto($row)) {
            $list[$row[1]] = $row[0];
        }
        
        return $list;
    }
       

    /**
     * @see ClientRouting::drawRouting()
     */ 
    protected function drawRouting() {
        $this->vertices_options = $this->TownsList();
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('routing_options_values' => array(0,1),
                              'routing_options_labels' => array(I18n::gt('fastest'),
                                                                I18n::gt('shortest'))));        
        $smarty->assign('routing_from',     $this->routingState->from);
        $smarty->assign('routing_to',       $this->routingState->to);
        $smarty->assign('routing_options',  $this->routingState->options);
        $smarty->assign('vertices_options', $this->vertices_options);
        return $smarty->fetch('routing.tpl');
    }
    
    
}

?>
