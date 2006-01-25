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

require_once CARTOWEB_HOME . 'plugins/mapOverlay/common/MapOverlay.php';

/**
 * Shape with a style for the shape and the label
 * @package Plugins
 */
class StyledShape extends CwSerializable {

    /**
     * @var StyleOverlay
     */
    public $shapeStyle;
        
    /**
     * @var LabelOverlay
     */
    public $labelStyle;
     
    /**    
     * @var Shape
     */
    public $shape;

    /**
     * @var string
     */
    public $label;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapeStyle = self::unserializeObject($struct, 'shapeStyle', 
                                                    'StyleOverlay');
        $this->labelStyle = self::unserializeObject($struct, 'labelStyle', 
                                                    'LabelOverlay');
        // do not use self::unserializeObject($struct, 'shape', 'Shape'); 
        // because Shape is abstract
        $this->shape      = self::unserializeObject($struct, 'shape');
        $this->label      = self::unserializeValue($struct, 'label');
    }        
}

/**
 * Outline request
 * @package Plugins
 */
class OutlineRequest extends CwSerializable {
    
    /** 
     * Styled shapes to be drawn
     * @var array array of StyledShape
     */
    public $shapes;
    
    /**
     * If true, must draw a mask instead of a standard shape
     * @var boolean
     */    
    public $maskMode;
    
    /**
     * If true, get default values for outline style
     * @var boolean
     */ 
    public $getDefaultvalue;


    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapes   = self::unserializeObjectMap($struct, 'shapes',
                                                     'StyledShape');
        $this->maskMode = self::unserializeValue($struct, 'maskMode', 
                                                 'boolean');
        $this->getDefaultvalue = self::unserializeValue($struct, 'getDefaultvalue', 
                                                 'boolean');
    }    
}

/**
 * Outline result
 * @package Plugins
 */
class OutlineResult extends CwSerializable {
    
    /**
     * Total shapes area
     * @var double
     */
    public $area;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->area = self::unserializeValue($struct, 'area', 'double');
    }
}

/**
 * Outline initialization
 * @package Plugins
 */
class OutlineInit extends CwSerializable {

    /**
     * @var array array of string
     */
    public $point;

    /**
     * @var array array of string
     */
    public $pointLabels;

    /**
     * @var array array of string
     */
    public $line;

    /**
     * @var array array of string
     */
    public $polygon;

    /**
     * @var string
     */
    public $pathToSymbols;

    /**
     * @var string
     */
    public $symbolType;

    /**
     * @var object default values
     */
    public $outlineDefaultValues;



    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->point = self::unserializeArray($struct, 'point');
        $this->pointLabels = self::unserializeArray($struct, 'pointLabels');
        $this->line = self::unserializeArray($struct, 'line');
        $this->polygon = self::unserializeValue($struct, 'polygon');
        $this->pathToSymbols = self::unserializeValue($struct, 'pathToSymbols');
        $this->symbolType = self::unserializeValue($struct, 'symbolType');
        $this->outlineDefaultValues = self::unserializeObject($struct, 'outlineDefaultValues',
                                                                            'OutlineDefaultValues');
    }

}

class OutlineDefaultValues extends CwSerializable {

    /**
     * @var array array of OutlineDefaultValues
     */
    public $outlineDefaultValuesList;

    public function unserialize($struct) {
        //$this->pointLabels = self::unserializeArray($struct, 'defaultValuesList');
        $this->outlineDefaultValuesList = self::unserializeArray($struct, 'outlineDefaultValuesList');
    }
}

class OutlineDefaultValuesList extends CwSerializable {

    /**
     * @var array array of OutlineDefaultValues
     */
    public $outlineDefaultValue;

    public function unserialize($struct) {
        $this->outlineDefaultValue = self::unserializeObject($struct, 'outlineDefaultValue', 
                                                                          'OutlineDefaultValue');
    }
}

/**
 * default values of the outlined shape objects
 * @package Plugins
 */
class OutlineDefaultValue extends CwSerializable {

    /**
     * @var string type of the shape
     */
    public $type;

    /**
     * @var shapeStyle shapeStyle object
     */
    public $shapeStyle;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->type = self::unserializeValue($struct, 'type');
        $this->shapeStyle = self::unserializeObject($struct, 'shapeStyle', 
                                                    'StyleOverlay');
    }
}

?>
