<?
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class OutlineRequest extends Serializable {
    public $shapes;
    public $maskMode;
    
    function unserialize($struct) {
        $this->shapes   = self::unserializeObjectMap($struct, 'shapes');
        $this->maskMode = self::unserializeValue($struct, 'maskMode', 'boolean');
    }    
}

?>