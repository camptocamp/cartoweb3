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
 * A request for layers.
 *
 * @package CorePlugins
 */
class LayersRequest extends Serializable {
    
    /**
     * @var array
     */
    public $layerIds;

    /**
     * @var int
     */
    public $resolution;

    function unserialize($struct) {
        $this->layerIds   = self::unserializeArray($struct, 'layerIds');
        $this->resolution = self::unserializeValue($struct, 'resolution',
                                                   'int');
    }
}

/**
 * Result of a layers request. It is empty.
 *
 * @package CorePlugins
 */
class LayersResult {}

?>
