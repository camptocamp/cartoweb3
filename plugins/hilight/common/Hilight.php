<?php
/**
 * @package Plugins
 * @version $Id$
 */

require_once(CARTOCOMMON_HOME . 'coreplugins/query/common/Query.php');

/**
 * Request for hilighting. The IdSelection class which it extends is found 
 * in the location core plugin.
 *
 * @package Plugins
 */
class HilightRequest extends IdSelection {

    public $maskMode;
    public $retrieveAttributes;

    function unserialize($struct) {
        $this->maskMode      = Serializable::unserializeValue($struct, 
                                                        'maskMode', 'boolean');
        $this->retrieveAttributes = Serializable::unserializeValue($struct, 
                                               'retrieveAttributes', 'boolean');
        parent::unserialize($struct);        
    }
}

/**
 * @package Plugins
 */
class HilightResult extends Serializable {
    public $layerResults;
    
    function unserialize($struct) {
        $this->layerResults = Serializable::unserializeObjectMap($struct, 
                                        'layerResults', 'LayerResult');
    }
}

?>