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
     */
    function handleInitializing($requ);

    /**
     * Handles the request just before plugins should draw in the map
     */
    function handlePreDrawing($requ);

    /**
     * Handles the request when the plugin shoud draw on the map
     */
    function handleDrawing($requ);

    /**
     * Handles the request after the plugins have drawn the image
     */
    function handlePostDrawing($requ);
}

/**
 * @package Server
 */
interface InitProvider {

    function getInit();
}

/**
 * @package Server
 */
interface CoreProvider {

    function handleCorePlugin($requ);
}
 
/**
 * @package Server
 */
abstract class ServerPlugin extends PluginBase {
    private $log;

    private $config;

    protected $serverContext;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    public function getServerContext() {
        return $this->serverContext;   
    }

    /**
     * @param initArgs serverContext
     */
    function initialize($initArgs) {
        $this->serverContext = $initArgs;
        
        $this->config = new ServerPluginConfig($this->getName(),
                                               $this->serverContext->projectHandler);
    }

    final function getConfig() {
        return $this->config;
    }
}

abstract class ClientResponderAdapter extends ServerPlugin
                                      implements ClientResponder {

    function handleInitializing($requ) {}

    function handlePreDrawing($requ) {}

    function handleDrawing($requ) {}

    function handlePostDrawing($requ) {}
} 

?>