<?php
/**
 * @package Plugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');
 
/**
 * @package Plugins
 */
class SelectionRequest extends Serializable {

    const POLICY_XOR = 'POLICY_XOR';
    const POLICY_UNION = 'POLICY_UNION';
    const POLICY_INTERSECTION = 'POLICY_INTERSECTION';

    public $policy;

    public $bbox;

    public function unserialize($struct) {
        $this->policy = self::unserializeValue($struct, 'policy');
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * @package Plugins
 */
class SelectionResult extends Serializable {

    // FIXME: type may depend on the kind of selection
    public $selectedIds;   

    public function unserialize($struct) {
        $this->layerid = self::unserializeValue($struct, 'layerId');
        $this->selectedIds = self::unserializeArray($struct, 'selectedIds');
    }
}

?>