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
 * @package CorePlugins
 */
class ResultElement {

    public $index;
    public $id;
    public $values;
}

/**
 * @package CorePlugins
 */
class LayerResult {
    
    public $layerId;
    public $numResults;
    public $fields;
    public $resultElements;
}

/**
 * @package CorePlugins
 */
class QueryResult {

    public $layerResults;
    
    /* TODO: unserialisation */
}

?>