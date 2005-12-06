<?php
/**
 * Routing plugin, server
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
 * Example server routing plugin which uses Postgres
 * @package Plugins
 */
class ServerDemoRouting extends ServerPostgresRouting {
    
    /**
     * @see ServerRouting::shortestPathQuery()
     */
    protected function shortestPathQuery($node1, $node2, $parameters) {

        $db = $this->getDb();
        $table = $this->getRoutingTable();
        $prepared = $db->prepare("SELECT a.edge_id  FROM shortest_path('SELECT id,"
                                 . " source, target, cost FROM {$table}_edges', ?,"
                                 . " ?, false, false) AS a LEFT JOIN {$table}"
                                 . " ON vertex_id = gid");
        Utils::checkDbError($prepared);        
        return $db->execute($prepared, array($node1, $node2));        
    }
    
    /**
     * @see ServerRouting::getNodes()
     */
    protected function getNodes(DB_result $result, $resultsId, $timestamp) {
    
        $nodes = array();
        $table = $this->getRoutingTable();
        $routingResultsTable = $this->getRoutingResultsTable();
        
        $db = $this->getDb();

        while ($result->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $node = new Node();
            
            $node->attributes = array();
            $attribute = new Attribute();
            $attribute->set('edge_id', $row['edge_id']);

            // Warning: make sure index on edge_id is present
            $routingResultsTable = $this->getRoutingResultsTable();

            $edgeId = $row['edge_id'];
            $r = $db->query("INSERT INTO $routingResultsTable SELECT $resultsId, " .
                    "$timestamp, gid, the_geom FROM $table WHERE edge_id = $edgeId");
            Utils::checkDbError($r, 'Error quering routing database');
            $nodes[] = $node;
        }
        
        return $nodes;    
    }
    
    /**
     * @see PluginManager::replacePlugin() 
     */
    public function replacePlugin() {
        return 'routing';
    } 
}
?>
