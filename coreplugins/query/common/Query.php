<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');


/**
 * @package CorePlugins
 */
class QueryRequest extends Serializable {

    public $bbox;
    
    // if empty: server should select layers from layersRequest  
    public $layerIds;
    
    // retrieveType: fetch attributes ? fetch shapes ?
    // resultProperties: layer * maxResult * startIndex
    
    function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        $this->layerIds = self::unserializeArray($struct, 'layerIds');
    }
}

/**
 * To be deleted when selection will use Tables!!
 * @package CorePlugins
 */
class ResultElement extends Serializable {

    public $id;
    public $values;

    function unserialize($struct) {
        $this->id = Serializable::unserializeValue($struct, 'id');
        $this->values = Serializable::unserializeArray($struct, 'values');
    }
}

/**
 * To be deleted when selection will use Tables!!
 * @package CorePlugins
 */
class LayerResult extends Serializable {
    
    public $layerId;
    public $numResults;
    public $fields;
    public $resultElements;

    function unserialize($struct) {
        $this->layerId = Serializable::unserializeValue($struct, 'layerId');
        $this->numResults = Serializable::unserializeValue($struct, 
                                                        'numResults', 'int');
        $this->fields = Serializable::unserializeArray($struct, 
                                                        'fields');
        $this->resultElements = Serializable::unserializeObjectMap($struct, 
                                        'resultElements', 'ResultElement');
    }
}

/**
 * @package CorePlugins
 */
class QueryResult extends Serializable {

    public $tableGroup;
    
    function unserialize($struct) {
        $this->tableGroup = Serializable::unserializeObject($struct, 
                                        'tableGroup', 'TableGroup');
    }
}

?>