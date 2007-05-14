<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ProjectLocationRequest extends CwSerializable {

    public $locationRequest;
    public $projectRequest;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {

        $this->locationRequest = self::unserializeObject($struct, 'locationRequest');        
        $this->projectRequest = self::unserializeValue($struct, 'projectRequest');
    }
}

/**
 * @package Tests
 */
class ProjectLocationResult extends CwSerializable {

    public $locationResult;
    public $projectResult;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        
        $this->locationResult = self::unserializeObject($struct, 'locationResult');        
        $this->projectResult = self::unserializeValue($struct, 'projectResult');
    }
}

?>
