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
     * The list of layers to draw
     * @var array
     */
    public $layerIds;

    /**
     * Resolution used to draw the images. Another good place for this would
     * have been in ImagesRequest.
     * @var int
     */
    public $resolution;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
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
