<?
/**
 * Outline plugin Serializable objects
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
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapes   = self::unserializeObjectMap($struct, 'shapes');
        $this->maskMode = self::unserializeValue($struct, 'maskMode', 'boolean');
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