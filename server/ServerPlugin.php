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
    
    const TYPE_CORE = 1;

    const TYPE_INIT = 2;
    const TYPE_PRE_DRAWING = 3;
    const TYPE_DRAWING = 4;
    const TYPE_POST_DRAWING = 5;


    protected $serverContext;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @param initArgs serverContext
     */
    function initialize($initArgs) {
        $this->serverContext = $initArgs;
        
        $this->config = new ServerPluginConfig($this->getName(),
                                               $this->serverContext->projectHandler);
    }

    abstract function getType();

    abstract function getResultFromRequest($requ);

    function getInitValues() {
        return NULL;
    }

    final function getConfig() {
        return $this->config;
    }

    /**
     * if type==null, is always done
     */
    final function getResult($type=NULL) {
        
        $this->log->debug(sprintf("Calling getResult for plugin %s, type %s", 
                                  get_class($this), var_export($type, true)));

        if ($type != NULL && $type != $this->getType())
            return;

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

        $result = $this->getResultFromRequest($request);

        $this->log->debug("plugin result: $resultName = " . $result);
        $request = $this->serverContext->mapRequest->$requestName;

        if ($resultName) {
            if (!$result)
                $this->log->warn(sprintf("plugin %s getResult returned false", get_class($this)));
            $this->serverContext->mapResult->$resultName = $result;
        }
    }
    
    final function getInit() {
        $this->log->debug(sprintf("Calling getInit for plugin %s", get_class($this)));
        
        $initName = $this->getName() . 'Init';
        
        $init = $this->getInitValues();
        
        $this->serverContext->mapInfo->$initName = $init;
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

    function getType() {
        return ServerPlugin::TYPE_CORE;
    }
}

?>