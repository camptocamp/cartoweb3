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
 * Color
 * @package Plugins
 */
class Color extends Serializable {

    /**
     * @var int
     */ 
    public $r;
    
    /**
     * @var int
     */ 
    public $g;

    /**
     * @var int
     */ 
    public $b;

    public function __construct() {}

    /**
     * Initializes color from red, green, blue values
     */
    public function setFromRGB($r, $g, $b) {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->r = self::unserializeValue($struct, 'r', 'int');
        $this->g = self::unserializeValue($struct, 'g', 'int');
        $this->b = self::unserializeValue($struct, 'b', 'int');
    }    
}

/**
 * Shape's style
 * @package Plugins
 */
class ShapeStyle extends Serializable {

    /**
     * @var mixed
     */
    public $symbol;

    /**
     * @var int
     */
    public $size;

    /**
     * @var Color
     */
    public $color;
    
    /**
     * @var Color
     */
    public $outlineColor;
    
    /**
     * @var Color
     */
    public $backgroundColor;
    
    /**
     * @var int
     */
    public $transparency;
                
    public function __construct() {
        $this->color = new Color();
        $this->outlineColor = new Color();
        $this->backgroundColor = new Color();
    }                
                
    public function __clone() {
        $this->color = clone $this->color; 
        $this->outlineColor = clone $this->outlineColor; 
        $this->backgroundColor = clone $this->backgroundColor; 
    }
    
    /**
     * Merges two shape styles
     *
     * Takes current shape style as a default value
     * @param ShapeStyle
     * @return ShapeStyle
     */
    public function merge($shapeStyle) {
        $result = clone $this;
        if (is_null($shapeStyle)) {
            return $result;
        }
        if (!empty($shapeStyle->symbol)) {
            $result->symbol = $shapeStyle->symbol;
        }
        if (!empty($shapeStyle->size)) {
            $result->size = $shapeStyle->size;
        }
        if (!empty($shapeStyle->color)) {
            $result->color = clone $shapeStyle->color;
        }
        if (!empty($shapeStyle->outlineColor)) {
            $result->outlineColor = clone $shapeStyle->outlineColor;
        }
        if (!empty($shapeStyle->backgroundColor)) {
            $result->backgroundColor = clone $shapeStyle->backgroundColor;
        }
        if (!empty($shapeStyle->transparency)) {
            $result->transparency = $shapeStyle->transparency;
        }
        return $result;
    }
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->symbol          = self::unserializeValue($struct, 'symbol', 'int');
        $this->size            = self::unserializeValue($struct, 'size', 'int');
        $this->color           = self::unserializeObject($struct, 'color', 'Color');
        $this->outlineColor    = self::unserializeObject($struct,
                                                         'outlineColor', 'Color');
        $this->backgroundColor = self::unserializeObject($struct,
                                                         'backgroundColor', 'Color');
        $this->transparency    = self::unserializeValue($struct, 
                                                        'transparency', 'int');
    }
}

/**
 * Label's style
 * @package Plugins
 */
class LabelStyle extends Serializable {

    /**
     * @var string
     */
    public $font;

    /**
     * @var int
     */
    public $size;

    /**
     * @var Color
     */
    public $color;
    
    /**
     * @var Color
     */
    public $outlineColor;

    /**
     * @var Color
     */
    public $backgroundColor;

    public function __construct() {
        $this->color = new Color();
        $this->outlineColor = new Color();
        $this->backgroundColor = new Color();
    }                

    public function __clone() {
        $this->color = clone $this->color; 
        $this->outlineColor = clone $this->outlineColor; 
        $this->backgroundColor = clone $this->backgroundColor; 
    }

    /**
     * Merges two label styles
     *
     * Takes current label style as a default value
     * @param LabelStyle
     * @return LabelStyle
     */
    public function merge($labelStyle) {
        $result = clone $this;
        if (is_null($labelStyle)) {
            return $result;
        }
        if (!empty($labelStyle->font)) {
            $result->font = $labelStyle->font;
        }
        if (!empty($labelStyle->size)) {
            $result->size = $labelStyle->size;
        }
        if (!empty($labelStyle->color)) {
            $result->color = clone $labelStyle->color;
        }
        if (!empty($labelStyle->outlineColor)) {
            $result->outlineColor = clone $labelStyle->outlineColor;
        }
        if (!empty($labelStyle->backgroundColor)) {
            $result->backgroundColor = clone $labelStyle->backgroundColor;
        }
        return $result;
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->font            = self::unserializeValue($struct, 'font');
        $this->size            = self::unserializeValue($struct, 'size', 'int');
        $this->color           = self::unserializeObject($struct, 'color', 'Color');
        $this->outlineColor    = self::unserializeObject($struct,
                                                         'outlineColor', 'Color');
        $this->backgroundColor = self::unserializeObject($struct,
                                                         'backgroundColor', 'Color');
        $this->text            = self::unserializeValue($struct, 'text');
    }
}

/**
 * Shape with a style
 * @package Plugins
 */
class StyledShape extends Serializable {

    /**
     * @var ShapeStyle
     */
    public $shapeStyle;
        
    /**
     * @var LabelStyle
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
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapeStyle = self::unserializeObject($struct, 'shapeStyle',
                                                    'ShapeStyle');
        $this->labelStyle = self::unserializeObject($struct, 'labelStyle',
                                                    'LabelStyle');
        $this->shape      = self::unserializeObject($struct, 'shape');
        $this->label      = self::unserializeValue($struct, 'label');
    }        
}

/**
 * Request
 * @package Plugins
 */
class OutlineRequest extends Serializable {
    
    /** 
     * Styled shapes to be drawn
     * @var array
     */
    public $shapes;
    
    /**
     * If true, must draw a mask instead of a standard shape
     * @var boolean
     */    
    public $maskMode;
        
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapes    = self::unserializeObjectMap($struct, 'shapes',
                                                      'StyledShape');
        $this->maskMode  = self::unserializeValue($struct, 'maskMode', 
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
