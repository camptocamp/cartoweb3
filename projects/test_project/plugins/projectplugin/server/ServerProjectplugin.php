<?
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ServerProjectplugin extends ClientResponderAdapter {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function handlePreDrawing($requ) {
        $result = new ProjectpluginResult();
        $result->shuffledMessage = str_rot13($requ->message); 
        return $result;
    }    
}
?>