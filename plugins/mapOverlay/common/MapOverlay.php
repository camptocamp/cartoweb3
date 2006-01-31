<?php
/**
 * MapOverlay plugin CwSerializable objects
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
 * PositionOverlay
 * @package Plugins
 */
class PositionOverlay extends CwSerializable {

    /**
     * Type constants
     */
    const TYPE_ABSOLUTE = 0;
    const TYPE_RELATIVE = 1;
    
    /**
     * Type of position (absolute or relative)
     * @var int
     */
    public $type;
    
    /**
     * Absolute index or offset (if type is relative)
     * @var int
     */
    public $index;
    
    /**
     * If type is relative, id to count the offset from
     * @var string
     */
    public $id;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->type  = self::unserializeValue($struct, 'type', 'int');
        $this->index = self::unserializeValue($struct, 'index', 'int');
        $this->id    = self::unserializeValue($struct, 'id');
    }    
}

/**
 * BasicOverlay
 * @package Plugins
 */
abstract class BasicOverlay extends CwSerializable {

    const ACTION_UPDATE = 1;
    const ACTION_SEARCH = 2;
    const ACTION_INSERT = 3;
    const ACTION_REMOVE = 4;

    /**
     * @var int
     */ 
    public $action;
    
    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $index;

    /**
     * @var int
     */
    public $copyIndex;
        
    /**
     * @var PositionOverlay
     */
    public $position;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Default action is update
        $this->action = BasicOverlay::ACTION_UPDATE;
    }
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->action    = self::unserializeValue($struct, 'action', 'int');
        $this->id        = self::unserializeValue($struct, 'id');
        $this->index     = self::unserializeValue($struct, 'index', 'int');
        $this->copyIndex = self::unserializeValue($struct, 'copyIndex', 'int');
        $this->position  = self::unserializeObject($struct, 'position', 'PositionOverlay');
    }    
}

/**
 * ColorOverlay
 * @package Plugins
 */
class ColorOverlay extends BasicOverlay {
    
    /**
     * @var int
     */
    public $red;

    /**
     * @var int
     */
    public $green;

    /**
     * @var int
     */
    public $blue;


    /**
     * Initializes color from red, green, blue values
     * @param integer red value
     * @param integer green value
     * @param integer blue value
     */
    public function setFromRGB($red, $green, $blue) {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    /**
     * Initializes color from an hexadecimal color, this string must
     * look like '#rrggbb'. Passing an empty string unset the color
     * @param string hexadecimal color
     */
    public function setFromHex($hex) {
        if (!empty($hex)) {
            $this->red = hexdec(substr($hex, 1, 2));
            $this->green = hexdec(substr($hex, 3, 2));
            $this->blue = hexdec(substr($hex, 5, 2));
        } else {
            $this->red = $this->green = $this->blue = NULL;
        }
    }

    /**
     * Returns the hexadecimal representation of the color or an empty
     * string if not applicable. For example, returns '#ff0000' if
     * red=255, green=0, blue=0.
     *
     * @return string 
     */
    public function getHex() {
        if ($this->isValid()) {
            return sprintf("#%02x%02x%02x", $this->red, $this->green, $this->blue); 
        } else {
            return '';
        }
    }

    /**
     * Returns check if the color is a valid color
     * @return boolean
     */
    public function isValid() {
        return !is_null($this->red) && !is_null($this->green) && !is_null($this->blue) && 
               ($this->red >= 0 && $this->red <= 255) &&
               ($this->green >= 0 && $this->green <= 255) &&
               ($this->blue >= 0 && $this->blue <= 255);
    }

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->red   = self::unserializeValue($struct, 'red', 'int');
        $this->green = self::unserializeValue($struct, 'green', 'int');
        $this->blue  = self::unserializeValue($struct, 'blue', 'int');
        parent::unserialize($struct);
    }  
}

/**
 * StyleOverlay
 * @package Plugins
 */
class StyleOverlay extends BasicOverlay {
        
    /**
     * @var string
     */
    public $symbol;

    /**
     * @var int
     */
    public $size;

    /**
     * @var ColorOverlay
     */
    public $color;
    
    /**
     * @var ColorOverlay
     */
    public $outlineColor;
    
    /**
     * @var ColorOverlay
     */
    public $backgroundColor;

    /**
     * FIXME: the transparency is a layer property !!
     * @var int
     */
    public $transparency;

    /** 
     * Constructor
     */
    public function __construct() {
        $this->color = new ColorOverlay();
        $this->outlineColor = new ColorOverlay();
        $this->backgroundColor = new ColorOverlay();
        parent::__construct();
    }

    /** 
     * Clone the style
     * @return StyleOverlay
     */    
    public function __clone() {
        $this->color = clone $this->color;
        $this->outlineColor = clone $this->outlineColor;
        $this->backgroundColor = clone $this->backgroundColor;        
    }

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->symbol = self::unserializeValue($struct, 'symbol');
        $this->size   = self::unserializeValue($struct, 'size', 'int');
        $this->color  = self::unserializeObject($struct, 'color', 
                                                'ColorOverlay');
        $this->outlineColor    = self::unserializeObject($struct, 
                                                         'outlineColor', 
                                                         'ColorOverlay');
        $this->backgroundColor = self::unserializeObject($struct, 
                                                         'backgroundColor', 
                                                         'ColorOverlay');
        $this->transparency    = self::unserializeValue($struct, 
                                                        'transparency', 'int');
        parent::unserialize($struct);        
    }    
}

/**
 * LabelOverlay
 * @package Plugins
 */
class LabelOverlay extends BasicOverlay {

    /**
     * @var string
     */
    public $font;
    
    /**
     * @var int
     */
    public $size;
    
    /**
     * @var ColorOverlay
     */
    public $color;

    /**
     * @var ColorOverlay
     */
    public $outlineColor;

    /**
     * @var ColorOverlay
     */
    public $backgroundColor;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->font = self::unserializeValue($struct, 'font');
        $this->size = self::unserializeValue($struct, 'size', 'int');
        $this->color = self::unserializeObject($struct, 'color', 'ColorOverlay');
        $this->outlineColor = self::unserializeObject($struct, 'outlineColor', 
                                                      'ColorOverlay');
        $this->backgroundColor = self::unserializeObject($struct, 'backgroundColor', 
                                                         'ColorOverlay');
        parent::unserialize($struct);
    }    
}

/**
 * ClassOverlay
 * @package Plugins
 */
class ClassOverlay extends BasicOverlay {

    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string 
     */ 
    public $copyName;
    
    /**
     * @var string
     */
    public $expression;
    
    /**
     * @var LabelOverlay
     */
    public $label;
    
    /**
     * @var array array of StyleOverlay
     */
    public $styles;
    
    /**
     * @var float
     */
    public $minScale;

    /**
     * @var float
     */
    public $maxScale;
    

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->name       = self::unserializeValue($struct, 'name');
        $this->copyName   = self::unserializeValue($struct, 'copyName');
        $this->expression = self::unserializeValue($struct, 'expression');
        $this->label      = self::unserializeObject($struct, 'expression', 
                                                    'LabelOverlay');
        $this->styles     = self::unserializeArray($struct, 'styles', 
                                                   'StyleOverlay');
        $this->minScale   = self::unserializeValue($struct, 'minScale', 
                                                   'float');
        $this->maxScale   = self::unserializeValue($struct, 'maxScale', 
                                                   'float');

        parent::unserialize($struct);
    }    
}

/**
 * MetadataOverlay
 * @package Plugins
 */
class MetadataOverlay extends BasicOverlay {
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string 
     */ 
    public $value;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->name  = self::unserializeValue($struct, 'name');
        $this->value = self::unserializeValue($struct, 'value');
        
        parent::unserialize($struct);
    }
} 
 
/**
 * LayerOverlay
 * @package Plugins
 */
class LayerOverlay extends BasicOverlay {
    
    /**
     * @var array array of ClassOverlay
     */
    public $classes;
    
    /**
     * @var string 
     */
    public $connection;
    
    /**
     * @var string
     */
    public $connectionType;
    
    /**
     * @var string 
     */ 
    public $copyName;
    
    /**
     * @var string 
     */
    public $data;
    
    /**
     * @var string
     */
    public $maxScale;
    
    /**
     * @var array array of MetadataOverlay
     */
    public $metadatas;
    
    /**
     * @var string
     */
    public $minScale;
    
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var int
     */
    public $transparency;
    
    /**
     * @var string
     */
    public $type;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->classes        = self::unserializeArray($struct, 'classes', 
                                                       'ClassOverlay');
        $this->connection     = self::unserializeValue($struct, 'connection');
        $this->connectionType = self::unserializeValue($struct, 'connectionType');
        $this->copyName       = self::unserializeValue($struct, 'copyName');
        $this->data           = self::unserializeValue($struct, 'data');
        $this->maxScale       = self::unserializeValue($struct, 'maxScale');
        $this->metadatas      = self::unserializeArray($struct, 'metadatas',
                                                       'MetadataOverlay');
        $this->minScale       = self::unserializeValue($struct, 'minScale');
        $this->name           = self::unserializeValue($struct, 'name');
        $this->transparency   = self::unserializeValue($struct, 'transparency', 
                                                       'int');
        $this->type           = self::unserializeValue($struct, 'type');
         
        parent::unserialize($struct);
    }    
}

/**
 * MapOverlay
 * @package Plugins
 */
class MapOverlay extends BasicOverlay {

    /** 
     * @var array
     */    
    public $layers;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layers = self::unserializeArray($struct, 'layers', 'LayerOverlay');
        parent::unserialize($struct);
    }    
} 

?>
