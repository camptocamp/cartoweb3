<?php
/**
 * @package Server
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');

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

    private function callHandleFunction($functionName) {
        
        $this->log->debug(sprintf("Calling callHandleFunction for plugin %s", 
                                  $this->getName()));
        
        if (!$this->log) {
            throw new CartoserverException('parent plugin not initialized');
        }

        $mapRequest = $this->serverContext->mapRequest;
        $request = $this->getRequest(true, $mapRequest);

        $requestName = $this->getName() . 'Request';
        $resultName = $this->getName() . 'Result';

        if (is_null($request)) {
            $this->log->warn("request variable $requestName not present: skipping plugin " .
                       get_class($this));
            return;
        }

        $this->log->warn("calling function $functionName");
        $result = $this->$functionName($request);

        $this->log->debug("plugin result: $resultName = " . $result);
        $request = $this->serverContext->mapRequest->$requestName;

        if ($resultName) {
            if (!$result) {
                $this->log->warn(sprintf("plugin %s getResult returned false, " .
                        "not storing the information", $this->getName()));
            } else {
                if (isset($this->serverContext->mapResult->$resultName))
                    throw new CartoserverException('result for plugin %s " .
                            "already stored, data collision', $this->getName()); 
                $this->serverContext->mapResult->$resultName = $result;
            }
        }
    }

    /**
     * Handles the request at the plugin initialisation phase.
     * Should be overriden by server plugins to handle the request at this stage.
     */
    function handleInit($requ){}
    /**
     * Handles the request just before plugins should draw in the map
     * Should be overriden by server plugins to handle the request at this stage.
     */
    function handlePreDrawing($requ){}
    /**
     * Handles the request when the plugin shoud draw on the map
     * Should be overriden by server plugins to handle the request at this stage.
     */
    function handleDrawing($requ){}
    /**
     * Handles the request after the plugins have drawn the image
     * Should be overriden by server plugins to handle the request at this stage.
     */
    function handlePostDrawing($requ){}

    final function internalHandleInit() {
        $this->callHandleFunction('handleInit');
    }
    final function internalHandlePreDrawing() {
        $this->callHandleFunction('handlePreDrawing');
    }
    final function internalHandleDrawing() {
        $this->callHandleFunction('handleDrawing');
    }
    final function internalHandlePostDrawing() {
        $this->callHandleFunction('handlePostDrawing');
    }
    final function internalHandleCorePlugin() {
        $this->callHandleFunction('handleCorePlugin');
    }
    
    function getInitValues() {
        return NULL;
    }
    
    final function getInit() {
        $this->log->debug(sprintf("Calling getInit for plugin %s", get_class($this)));
        
        $initName = $this->getName() . 'Init';
        
        $init = $this->getInitValues();
        
        $this->serverContext->getMapInfo()->$initName = $init;
    }
}

/**
 * @package Server
 */
abstract class ServerCorePlugin extends ServerPlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    
    abstract function handleCorePlugin($requ);
}

?>