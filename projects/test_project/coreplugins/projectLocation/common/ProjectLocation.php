<?
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ProjectLocationRequest extends Serializable {

    public $locationRequest;
    public $projectRequest;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {

        $this->locationRequest = self::unserializeObject($struct, 'locationRequest');        
        $this->projectRequest = self::unserializeValue($struct, 'projectRequest');
    }
}

/**
 * @package Tests
 */
class ProjectLocationResult extends Serializable {

    public $locationResult;
    public $projectResult;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        
        $this->locationResult = self::unserializeObject($struct, 'locationResult');        
        $this->projectResult = self::unserializeValue($struct, 'projectResult');
    }
}

?>