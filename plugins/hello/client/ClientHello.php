<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class ClientHello extends ClientPlugin
                  implements Sessionable, GuiProvider {

    const HELLO_INPUT = 'hello_input';
    
    private $log;
    private $message;
    private $count;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    public function loadSession($sessionObject) {
        $this->count = $sessionObject;
    }

    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->count = 0;
    }
    public function saveSession() {
        return $this->count;
    }
    
    public function handleHttpPostRequest($request) {
        $this->count = $this->count + 1;
        $this->message = @$_REQUEST[self::HELLO_INPUT];
    }

    public function handleHttpGetRequest($request) {}

    public function buildMapRequest($mapRequest) {
        //$mapRequest->helloRequest = @$_REQUEST[self::HELLO_INPUT];
    }

    public function handleResult($result) {}

    public function renderForm(Smarty $template) {

        $template->assign('hello_active', true);
        $template->assign('hello_message', "message: " . $this->message . 
                          " count: " . $this->count);
    }
}
?>
