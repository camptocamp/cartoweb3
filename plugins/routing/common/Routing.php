<?php
/**
 * Routing plugin Serializable objects
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
 * A parameter
 */
class Parameter extends Serializable {
    
    /**
     * @var string
     */ 
    public $id;
    
    /**
     * @var string
     */
    public $value; 

    /**
     * @param string
     * @param string
     */
    public function set($id, $value) {
        $this->id    = $id;
        $this->value = $value;
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id    = self::unserializeValue($struct, 'id');
        $this->value = self::unserializeValue($struct, 'value');
    }    
}

/**
 * Request
 * @package Plugins
 */
class RoutingRequest extends Serializable {
    
    /**
     * Opaque string representation of the graph model object used for the 
     *  server display. The client should not try to interpret its content.
     * @var string
     */
    public $graph;
    
    /**
     * Where to stop, including start and end
     *
     * Array of string, each one identifying a point. If null, server will only
     * display path on map.
     * @var array
     */
    public $stops;

    /**
     * Key-value array of parameters
     * @var array
     */
    public $parameters;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->graph      = self::unserializeValue($struct, 'graph');
        $this->stops      = self::unserializeArray($struct, 'stops');
        $this->parameters = self::unserializeObjectMap($struct,
                                                       'parameters',
                                                       'Parameter');
    }    
}

/**
 * An attribute
 */
class Attribute extends Serializable {
    
    /**
     * @var string
     */ 
    public $id;
    
    /**
     * @var string
     */
    public $value; 

    /**
     * @param string
     * @param string
     */
    public function set($id, $value) {
        $this->id    = $id;
        $this->value = $value;
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id    = self::unserializeValue($struct, 'id');
        $this->value = self::unserializeValue($struct, 'value');
    }    
}

/**
 * Step for roadmap
 * @package Plugins
 */
abstract class Step extends Serializable {
  
    /**
     * Key-value attributes
     * @var array
     */
    public $attributes;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->attributes = self::unserializeObjectMap($struct,
                                                       'attributes',
                                                       'Attribute');
    }
}

/**
 * Node
 * @package Plugins
 */
class Node extends Step {
}

/**
 * Edge
 * @package Plugins
 */
class Edge extends Step {
}

/**
 * Result
 * @package Plugins
 */
class RoutingResult extends Serializable {

    /**
     * Opaque string representation of the graph model object used for the 
     *  server display. The client should not try to interpret its content.
     * @var string
     */
    public $graph;
    
    /**
     * Logical path
     * 
     * Attributes for display of nodes/edges information. May include data
     * to be displayed in the roadmap.
     * @var array
     */
    public $steps;

    /**
     * Attributes global to path (e.g. total distance)
     * @var array
     */
    public $attributes;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->graph      = self::unserializeValue($struct, 'graph');
        $this->steps      = self::unserializeObjectMap($struct, 'steps');
        $this->attributes = self::unserializeObjectMap($struct,
                                                       'attributes',
                                                       'Attribute');
    }
}

?>
