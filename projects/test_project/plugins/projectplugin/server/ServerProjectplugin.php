<?
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ServerProjectplugin extends ServerPlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getType() {
        return ServerPlugin::TYPE_PRE_DRAWING;
    }

    function getResultFromRequest($requ) {
        $result = new ProjectpluginResult();
        $result->shuffledMessage = str_shuffle($requ->message); 
        return $result;
    }    
}
?>