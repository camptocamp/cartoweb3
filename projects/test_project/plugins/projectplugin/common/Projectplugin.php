<?
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ProjectpluginRequest extends Serializable {
    public $message;

    public function unserialize($struct) {
        $this->message = self::unserializeValue($struct, 'message');
    }
}

/**
 * @package Tests
 */
class ProjectpluginResult extends Serializable {
    public $shuffledMessage;

    public function unserialize($struct) {
        $this->shuffledMessage = self::unserializeValue($struct, 'shuffledMessage');
    }
}

/**
 * @package Tests
 */
class ProjectpluginInit extends Serializable {
    public $initMessage;

    public function unserialize($struct) {
        $this->initMessage = self::unserializeValue($struct, 'initMessage');
    }
}

?>