<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class Hello extends ClientPlugin {
    private $log;
    private $msg = 'hello world';

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function loadSession($sessionObject) {
        $this->count = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->count = 0;
    }
    function saveSession() {
        return $this->count;
    }
    
    function handleHttpRequest($request) {
        $this->count = $this->count + 1;
    }

    function buildMapRequest($mapRequest) {}

    function handleMapResult($mapResult) {}

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $template->assign('hello_active', true);
        $template->assign('hello_message', "message " . $this->msg . 
                          " count: " . $this->count);
    }
}
?>