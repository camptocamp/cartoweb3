<?php
/**
 * @package Plugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');
// this plugin depends on the layer plugin for IdSelection type and
//  query plugin for the LayerResults.
require_once(CARTOCOMMON_HOME . 'coreplugins/layers/common/Layers.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/query/common/Query.php');
 
/**
 * Selection request. It extends the IdSelection of the location core plugin.
 * See this plugin documentation for the attributes which can be used.
 * 
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
 * Result of a selection. There will be no results if the returnResults
 * attribute is false in the request.
 * If the request attribute retrieveAttributes is true, there will be additional
 * attributes returned. For now, there are the area and the object name.
 * 
 * See the LayerResult class of the Query core plugin for the type of the
 * layerResults field.
 * 
 * @package Plugins
 */
class SelectionResult extends Serializable {

    // FIXME: type may depend on the kind of selection (integer of string)
    public $selectedIds;   
    public $layerResults;
    
    public function unserialize($struct) {
        $this->selectedIds = self::unserializeArray($struct, 'selectedIds');
        $this->layerResults = Serializable::unserializeObjectMap($struct, 
                                        'layerResults', 'LayerResult');
    }
}

?>