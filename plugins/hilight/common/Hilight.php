<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Request for hilighting. The IdSelection class which it extends is found 
 * in the location core plugin.
 *
 * @package Plugins
 */
class HilightRequest extends IdSelection {

    public $maskMode;
    public $calculateArea;

    function unserialize($struct) {
        $this->maskMode      = Serializable::unserializeValue($struct, 
                                                        'maskMode', 'boolean');
        $this->calculateArea = Serializable::unserializeValue($struct, 
                                                    'calculateArea', 'boolean');
        parent::unserialize($struct);        
    }
}

/**
 * @package Plugins
 */
class HilightResult extends Serializable {
    public $area;
    
    function unserialize($struct) {
        $this->area = self::unserializeValue($struct, 'area', 'double');
    }
}

?>