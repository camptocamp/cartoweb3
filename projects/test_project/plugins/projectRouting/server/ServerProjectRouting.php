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
 * @package Plugins
 */
class ServerProjectRouting extends ServerRouting {    

    /**
     * May convert stops identifiers sent by client to nodes identifiers
     * useable by external routing module
     * @param array
     * @return array
     */
    protected function convertNodeIds($stops) {
        $newStops = array();
        foreach ($stops as $stop) {
            $newStops[] = strtoupper(str_replace(' ', '',
                                     str_replace('.', '', $stop)));
        }
        return $newStops;
    }

    /**
     * May generate a list of shapes to draw path on map using plugin Outline
     * @param array array of Step
     * @param array array of StyledShape 
     */
    protected function drawRoutingResult($steps) {

        if (count($steps) == 0) {
            $this->getServerContext()->addMessage($this, 'noPathFound',
                                               I18nNoop::gt('Path not found'));        
            return NULL;
        }
        
        $shape = NULL;
        $points = array();
        foreach ($steps as $step) {
            if (!($step instanceof Node)) {
                continue;
            }
            $point = new Point();
            $point->x = $step->attributes["x"];
            $point->y = $step->attributes["y"];
            $points[] = $point;
        }
        
        if (count($points) > 0) {
            $shape = new StyledShape();
            $shape->shape = new Line();
            $shape->shape->points = $points;
        }
        if ($shape) {
            $shape = array($shape);
        }
        return $shape;
    }
    
    public function replacePlugin() {
        return 'routing';
    } 
}

?>