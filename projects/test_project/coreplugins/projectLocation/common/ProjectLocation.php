<?
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ProjectLocationRequest extends Serializable {

    public $locationRequest;
    public $projectRequest;

    function unserialize($struct) {

        $this->locationRequest = self::unserializeObject($struct, 'locationRequest');        
        $this->projectRequest = self::unserializeValue($struct, 'projectRequest');
    }
}

/**
 * @package CorePlugins
 */
class ProjectLocationResult extends Serializable {

    public $locationResult;
    public $projectResult;

    public function unserialize($struct) {
        
        $this->locationResult = self::unserializeObject($struct, 'locationResult');        
        $this->projectResult = self::unserializeValue($struct, 'projectResult');
    }
}

?>