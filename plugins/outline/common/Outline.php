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
    
    function unserialize($struct) {
        $this->shapes = self::unserializeObjectMap($struct, 'shapes');
    }    
}

?>