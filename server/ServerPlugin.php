<?php
/**
 * @package Server
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');

/**
 * @package Server
 */
interface ClientResponder {
 
    /**
     * Handles the request at the plugin initialisation phase.
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    function initializeRequest($requ);

    /**
     * Handles the request just before plugins should draw in the map
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    function handlePreDrawing($requ);

    /**
     * Handles the request when the plugin should draw on the map
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    function handleDrawing($requ);

    /**
     * Handles the request after the plugins have drawn the image
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    function handlePostDrawing($requ);
}

/**
 * @package Server
 */
interface InitProvider {

    /**
     * Creates plugin init object that will be stored in MapInfo 
     * @return mixed plugin init object
     */
    function getInit();
}

/**
 * @package Server
 */
interface CoreProvider {
    
    /**
     * Handles request at special places for core plugins
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    function handleCorePlugin($requ);
}
 
/**
 * Server plugin
 * @package Server
 */
abstract class ServerPlugin extends PluginBase {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var ServerConfig
     */
    private $config;

    /**
     * @var ServerContext
     */
    protected $serverContext;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @return ServerContext
     */
    public function getServerContext() {
        return $this->serverContext;   
    }

    /**
     * Initializes plugin configuration
     * @param ServerContext
     */
    function initializeConfig($initArgs) {
        $this->serverContext = $initArgs;
        
        $this->config = new ServerPluginConfig($this->getName(),
                                               $this->serverContext->projectHandler);
    }

    /**
     * @return ServerConfig
     */
    final function getConfig() {
        return $this->config;
    }
    
    /**
     * @return boolean
     */
    function useExtendedRequest() {
        return false;
    }
    
    /**
     * @return boolean
     */
    function useExtendedResult() {
        return false;
    }

    /**
     * @return boolean
     */
    function useExtendedInit() {
        return false;
    }
}

/**
 * Adapter for interface {@link ClientResponder}
 *
 * This class implements all interface methods so child classes won't need
 * to add an empty method when they're not implemented.
 * @package Server
 */
abstract class ClientResponderAdapter extends ServerPlugin
                                      implements ClientResponder {

    /**
     * @see ClientResponder::initializeRequest()
     */
    function initializeRequest($requ) {}

    /**
     * @see ClientResponder::handlePreDrawing()
     */
    function handlePreDrawing($requ) {}

    /**
     * @see ClientResponder::handleDrawing()
     */
    function handleDrawing($requ) {}

    /**
     * @see ClientResponder::handlePostDrawing()
     */
    function handlePostDrawing($requ) {}
} 

?>