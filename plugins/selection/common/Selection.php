<?php
/**
 * @package Plugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');
 
/**
 * @package Plugins
 */
class SelectionRequest extends IdSelection {

    const POLICY_XOR = 'POLICY_XOR';
    const POLICY_UNION = 'POLICY_UNION';
    const POLICY_INTERSECTION = 'POLICY_INTERSECTION';

    public $policy;

    public $bbox;

    public $maskMode;
    public $retrieveAttributes;
    public $returnResults;

    public function unserialize($struct) {
        $this->policy = self::unserializeValue($struct, 'policy');
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        $this->maskMode = Serializable::unserializeValue($struct, 
                                         'maskMode', 'boolean');
        $this->retrieveAttributes = Serializable::unserializeValue($struct, 
                                         'retrieveAttributes', 'boolean');
        $this->returnResults = Serializable::unserializeValue($struct, 
                                         'returnResults', 'boolean');        
        parent::unserialize($struct);        
    }
}

/**
 * @package Plugins
 */
class SelectionResult extends Serializable {

    // FIXME: type may depend on the kind of selection
    public $selectedIds;   
    public $layerResults;
    
    public function unserialize($struct) {
        $this->selectedIds = self::unserializeArray($struct, 'selectedIds');
        $this->layerResults = Serializable::unserializeObjectMap($struct, 
                                        'layerResults', 'LayerResult');
    }
}

?>