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
 * 
 * @package Plugins
 */
 
class ClientDemoRouting extends ClientRouting {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var List of possible towns
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
     * Extension declaration
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
            //TODO set the dsn in the routing.ini
            $dsn = $this->getConfig()->dsn;
            $this->db = DB::connect($dsn);
                if (PEAR::isError($this->db)) {
                    throw new CartoclientException('Error connecting names database');
                    return;
                }
        }
        return $this->db;
    }
    
   /**
    * Retrieves list of names from database
    * @return array
     */
    protected function TownsList (){
        // FIXME: table name and col name should be in config
        $namestep = $this->getConfig()->namestep;
        $postgresRoutingVerticesTable = $this->getConfig()->postgresRoutingVerticesTable;
        $sql = "SELECT $namestep, geom_id FROM $postgresRoutingVerticesTable WHERE $namestep != '' ORDER BY $namestep";
        $this->getDb();
        $res = $this->db->query($sql);
        //print_r($sql);
        //Utils::checkDbError($res);
        if (PEAR::isError($res)) {
            throw new CartoclientException('Error quering routing database');
            return;
        }
        while ($res->fetchInto($row)) {
            $list[$row[1]] = $row[0];
        }
        
        return $list;
    }
       

    /**
     * Draws routing specific template
     * @return string
     */ 
    protected function drawRouting() {
        $this->vertices_options = $this->TownsList();
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('routing_options_values' => array(0,1),
                              'routing_options_labels' => array(I18n::gt('fastest'),
                                                                I18n::gt('shortest'))));        
        $smarty->assign('routing_from',    $this->routingState->from);
        $smarty->assign('routing_to',      $this->routingState->to);
        $smarty->assign('routing_options', $this->routingState->options);
        $smarty->assign('vertices_options', $this->vertices_options);
        return $smarty->fetch('routing.tpl');
    }
    
    
}

?>