<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ClientProjectplugin extends ClientPlugin
                          implements Sessionable, GuiProvider, ServerCaller {

    const PROJECTPLUGIN_INPUT = 'projectplugin_input';
    
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
    
    function handleHttpPostRequest($request) {
        $this->count = $this->count + 1;
    }

    function handleHttpGetRequest($request) {}

    function buildMapRequest($mapRequest) {
        $request = new ProjectpluginRequest();
        if (array_key_exists(self::PROJECTPLUGIN_INPUT, $_REQUEST)) { 
            $request->message = $_REQUEST[self::PROJECTPLUGIN_INPUT];
        } else {
            $request->message = '';
        }
        $mapRequest->projectpluginRequest = $request;
    }

    function initializeResult($result) {
        $result = Serializable::unserializeObject($result, NULL, 'ProjectpluginResult');
        $this->message = $result->shuffledMessage;
    }

    function handleResult($result) {}
    
    function renderForm(Smarty $template) {
        
        $template->assign('projectplugin_active', true);
        $template->assign('projectplugin_message', "message: " . $this->message . 
                          " count: " . $this->count);
    }
}

?>