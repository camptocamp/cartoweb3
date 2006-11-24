<?php
/**
 * Outline plugin CwSerializable objects
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
class OutlineRequest extends CwSerializable {

    /**
     * SwissimageMode constants
     */
    const MODE_LEVEL1 = 'MODE_LEVEL1'; 
    const MODE_LEVEL2_25 = 'MODE_LEVEL2_25'; 
    const MODE_LEVEL2_50_200 = 'MODE_LEVEL2_50_200'; 
    
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
     * Swisstopo specific
     */
    public $wholeDataLayer;
    
    public $swissimageMode;
    public $swissimageLevel2_25;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapes   = self::unserializeObjectMap($struct, 'shapes');
        $this->maskMode = self::unserializeValue($struct, 'maskMode', 
                                                 'boolean');
        $this->labelMode = self::unserializeValue($struct, 'labelMode', 
                                                 'boolean');
        $this->wholeDataLayer = self::unserializeValue($struct, 'wholeDataLayer', 'string');
        
        $this->swissimageMode = self::unserializeValue($struct, 'swissimageMode');
    }    
}

/**
 * Result
 * @package Plugins
 */
class OutlineResult extends CwSerializable {
    
    /**
     * SwissimageStatus constants
     */
    const STATUS_VALID = 'STATUS_VALID'; 
	const STATUS_MIXING_LEVELS = 'STATUS_MIXING_LEVELS';
	const STATUS_OUTSIDE_LEVEL1 = 'STATUS_OUTSIDE_LEVEL1';
	const STATUS_OUTSIDE_LEVEL2 = 'STATUS_OUTSIDE_LEVEL2';
	const STATUS_OUTSIDE_LEVEL2_25 = 'STATUS_OUTSIDE_LEVEL2_25';
    
    /**
     * Total shapes area
     * @var double
     */
    public $area;
    
    /**
     * Swisstopo specific
     */
    public $isOutside;    
    
    /**
     * Swissimage parameters
     */
    public $swissimageStatus;
    public $swissimageInternalStatus;
    public $swissimageLevel2_50Area;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->area = self::unserializeValue($struct, 'area', 'double');
        $this->isOutside = self::unserializeValue($struct, 'isOutside', 'boolean');

        $this->swissimageStatus = self::unserializeValue($struct, 'swissimageStatus');
        $this->swissimageInternalStatus = self::unserializeValue($struct, 'swissimagInternalStatus', 'int');
        $this->swissimageLevel2_50Area = self::unserializeValue($struct, 'swissimageLevel2_50Area', 'double');
    }
}

?>
