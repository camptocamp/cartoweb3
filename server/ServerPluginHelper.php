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
                    throw new CartoserverException('result for plugin %s " .
                            "already stored, data collision', $plugin->getName()); 
                $serverContext->mapResult->$resultName = $result;
            }
        }
    }
}

class ClientResponderHelper extends ServerPluginHelper {

    final function handleInitializingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleInitializing');
    }

    final function handlePreDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handlePreDrawing');
    }

    final function handleDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleDrawing');
    }

    final function handlePostDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handlePostDrawing');
    }
}

class InitProviderHelper extends ServerPluginHelper {

    final function getInitHelper($plugin) {
        $this->log->debug(sprintf("Calling getInit for plugin %s", get_class($plugin)));
        
        $initName = $plugin->getName() . 'Init';
        
        $init = $plugin->getInit();
        
        $plugin->getServerContext()->getMapInfo()->$initName = $init;
    }    
}

class CoreProviderHelper extends ServerPluginHelper {

    final function handleCorePluginHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleCorePlugin');
    }
}

?>
