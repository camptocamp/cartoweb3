<?php
/**
 * Outline plugin Serializable objects
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
 * Request
 * @package Plugins
 */
class OutlineRequest extends Serializable {
    
    /** 
     * Shapes to be drawn
     * @var array
     */
    public $shapes;
    
    /**
     * If true, must draw a mask instead of a standard shape
     * @var boolean
     */    
    public $maskMode;
    
    /**
     * If true, will ask for a label text
     * @var boolean
     */
    public $labelMode;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapes   = self::unserializeObjectMap($struct, 'shapes');
        $this->maskMode = self::unserializeValue($struct, 'maskMode', 
                                                 'boolean');
        $this->labelMode = self::unserializeValue($struct, 'labelMode', 
                                                 'boolean');
    }    
}

/**
 * Result
 * @package Plugins
 */
class OutlineResult extends Serializable {
    
    /**
     * Total shapes area
     * @var double
     */
    public $area;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->area = self::unserializeValue($struct, 'area', 'double');
    }
}

?>
