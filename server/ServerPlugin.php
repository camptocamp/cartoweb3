<?php

abstract class ServerPlugin extends PluginBase {
    private $log;

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
     * @initArgs serverContext
     */
    function initialize($initArgs) {
        $this->serverContext = $initArgs;
    }

    abstract function getType();

    abstract function getResultFromRequest($requ);

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

        $requestName = $this->getName() . 'Request';
        $this->log->debug("request name is $requestName");
        $request = @$this->serverContext->mapRequest->$requestName;
        if (!$request) {
            $this->log->warn("request variable $requestName not present: skipping plugin " .
                       get_class($this));
            return;
        }
        
        $result = $this->getResultFromRequest($request);

        $resultName = $this->getName() . 'Result';

        $this->log->debug("plugin result: $resultName = " . $result);
        $request = $this->serverContext->mapRequest->$requestName;

        if ($resultName) {
            if (!$result)
                $this->log->warn(sprintf("plugin %s getResult returned false", get_class($this)));
            $this->serverContext->mapResult->$resultName = $result;
        }
    }
}

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