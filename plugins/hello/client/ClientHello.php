<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Demo plugin, shows how to output messages and use sessions.
 * @package Plugins
 */
class ClientHello extends ClientPlugin
                  implements Sessionable, GuiProvider {

    const HELLO_INPUT = 'hello_input';

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $count;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Retrieves count number from session.
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->count = $sessionObject;
    }

    /**
     * Initializes session-saved var "count" to 0.
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->count = 0;
    }

    /**
     * Saves count number in session.
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->count;
    }
    
    /**
     * Increments count number and retrieves POST'ed message.
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->count = $this->count + 1;
        $this->message = @$_REQUEST[self::HELLO_INPUT];
    }

    /**
     * Not used.
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}

    /**
     * Draws plugins interface.
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $template->assign('hello_active', true);
        $template->assign('hello_message', "message: " . $this->message . 
                          " count: " . $this->count);
    }
}
?>
