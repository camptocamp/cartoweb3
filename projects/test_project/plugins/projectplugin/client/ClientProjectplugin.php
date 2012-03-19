<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ClientProjectplugin extends ClientPlugin
                          implements Sessionable, GuiProvider,
                                     ServerCaller, InitUser {

    const PROJECTPLUGIN_INPUT = 'projectplugin_input';
    
    private $log;
    private $message;
    private $count;
    private $initMessage;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log = LoggerManager::getLogger(__CLASS__);
    }

    public function initialize() {

        $this->cartoclient->getConfig()->setMapId('projectmap');
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

    public function buildRequest() {
        $request = new ProjectpluginRequest();
        if (array_key_exists(self::PROJECTPLUGIN_INPUT, $_REQUEST)) { 
            $request->message = $_REQUEST[self::PROJECTPLUGIN_INPUT];
        } else {
            $request->message = '';
        }
        return $request;
    }

    public function initializeResult($result) {
        $result = CwSerializable::unserializeObject($result, NULL,
                                                  'ProjectpluginResult');            
        $this->message = $result->shuffledMessage;
    }

    public function handleResult($result) {}
    
    public function renderForm(Smarty $template) {
        
        $template->assign('projectplugin_active', true);
        $template->assign('projectplugin_message',
                          "message: " . $this->message . 
                          " count: " . $this->count . 
                          " init: " . $this->initMessage);
    }
    
    public function handleInit($init) {
        
        $this->initMessage = $init->initMessage;
    }
}
