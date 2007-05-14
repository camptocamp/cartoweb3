<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ServerProjectplugin extends ClientResponderAdapter
                          implements InitProvider {
    private $log;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    public function handlePreDrawing($requ) {
        $result = new ProjectpluginResult();
        $result->shuffledMessage = str_rot13($requ->message); 
        return $result;
    }    
    
    public function getInit() {
        $init = new ProjectpluginInit();
        $init->initMessage = 'foobar';
        return $init;
    }
}
?>
