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
    }

    public function handleHttpGetRequest($request) {}

    public function buildMapRequest($mapRequest) {
        $request = new ProjectpluginRequest();
        if (array_key_exists(self::PROJECTPLUGIN_INPUT, $_REQUEST)) { 
            $request->message = $_REQUEST[self::PROJECTPLUGIN_INPUT];
        } else {
            $request->message = '';
        }
        $mapRequest->projectpluginRequest = $request;
    }

    public function initializeResult($result) {
        $result = Serializable::unserializeObject($result, NULL, 'ProjectpluginResult');
        $this->message = $result->shuffledMessage;
    }

    public function handleResult($result) {}
    
    public function renderForm(Smarty $template) {
        
        $template->assign('projectplugin_active', true);
        $template->assign('projectplugin_message', "message: " . $this->message . 
                          " count: " . $this->count);
    }
}

?>