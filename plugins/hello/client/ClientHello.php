<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class ClientHello extends ClientPlugin {

    const HELLO_INPUT = 'hello_input';
    
    private $log;
    private $message;
    private $count;

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
        $this->message = @$_REQUEST[self::HELLO_INPUT];
    }

    function buildMapRequest($mapRequest) {
        //$mapRequest->helloRequest = @$_REQUEST[self::HELLO_INPUT];
    }

    function handleResult($result) {}

    function renderForm(Smarty $template) {

        $template->assign('hello_active', true);
        $template->assign('hello_message', "message: " . $this->message . 
                          " count: " . $this->count);
    }
}
?>