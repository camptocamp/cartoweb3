<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class HilightRequest extends Serializable {
    // maybe use a the IdSelection Object (which should also be used by the selection module)
    
    public $layerId;
    public $idAttribute;
    public $idType; // (string|integer) 
    public $selectedIds;
    public $maskMode;
    
    function unserialize($struct) {
        $this->layerId     = Serializable::unserializeValue($struct, 'layerId');
        $this->idAttribute = Serializable::unserializeValue($struct, 'idAttribute');
        $this->idType      = Serializable::unserializeValue($struct, 'idType');
        $this->selectedIds = Serializable::unserializeArray($struct, 'selectedIds');
        $this->maskMode    = Serializable::unserializeValue($struct, 'maskMode', 'boolean');
    }
}

/* no HilightResult */

?>