<?php
/**
 * Interface helpers for server plugins
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>  
 * @version $Id$
 */

/**
 * Helper for server plugin interfaces
 *
 * Helpers are called by {@link PluginManager::callPluginsImplementing()}.
 * They implement functionnalities common to all plugins implementing an
 * interface.
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>  
 */
class ServerPluginHelper {
    
    /**
     * @var Logger
     */
    protected $log;
    
    function __construct() {
        $this->log =& LoggerManager::getLogger(get_class($this));
    }
    
    /**
     * Common function caller
     *
     * Retrieves plugin request and saves result in MapResult.
     * @param ServerPlugin plugin
     * @param string function name
     */
    protected function callHandleFunction($plugin, $functionName) {
        
        $this->log->debug(sprintf("Calling callHandleFunction for plugin %s", 
                                  $plugin->getName()));
        
        $serverContext = $plugin->getServerContext();
        $mapRequest = $serverContext->mapRequest;
        $request = $plugin->getRequest(true, $mapRequest);

        $requestName = $plugin->getName() . 'Request';
        $resultName = $plugin->getName() . 'Result';

        if (is_null($request)) {
            $this->log->warn("request variable $requestName not present: skipping plugin " .
                       get_class($plugin));
            return;
        }

        $this->log->warn("calling function $functionName");
        $result = $plugin->$functionName($request);

        $this->log->debug("plugin result: $resultName = " . $result);
        $request = $serverContext->mapRequest->$requestName;

        if ($resultName) {
            if (!$result) {
                $this->log->warn(sprintf("plugin %s getResult returned false, " .
                        "not storing the information", $plugin->getName()));
            } else {
                if (isset($serverContext->mapResult->$resultName))
                    throw new CartoserverException(sprintf('result for plugin %s " .
                            "already stored, data collision', $plugin->getName())); 
                $serverContext->mapResult->$resultName = $result;
            }
        }
    }
}

/**
 * Helper for {@link ClientResponder}
 * @package Server
 */
class ClientResponderHelper extends ServerPluginHelper {

    /**
     * @param ServerPlugin
     */
    final function initializeRequestHelper($plugin) {
        $this->callHandleFunction($plugin, 'initializeRequest');
    }

    /**
     * @param ServerPlugin
     */
    final function handlePreDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handlePreDrawing');
    }

    /**
     * @param ServerPlugin
     */
    final function handleDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleDrawing');
    }

    /**
     * @param ServerPlugin
     */
    final function handlePostDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handlePostDrawing');
    }
}

/**
 * Helper for {@link InitProvider}
 * @package Server
 */
class InitProviderHelper extends ServerPluginHelper {

    /**
     * Calls plugin's {@link InitProvider::getInit} and stores the result
     * in MapInfo
     * @param ServerPlugin
     */
    final function getInitHelper($plugin) {
        $this->log->debug(sprintf("Calling getInit for plugin %s", get_class($plugin)));
        
        $initName = $plugin->getName() . 'Init';
        
        $init = $plugin->getInit();
        
        $plugin->getServerContext()->getMapInfo()->$initName = $init;
    }    
}

/**
 * Helper for {@link CoreProvider}
 * @package Server
 */
class CoreProviderHelper extends ServerPluginHelper {

    /**
     * @param ServerPlugin
     */
    final function handleCorePluginHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleCorePlugin');
    }
}

?>