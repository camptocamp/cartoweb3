<?php
/**
 * Base class for plugins
 * @package Common
 * @version $Id$
 */

/**
 * Base class for plugins
 * @package Common
 */
abstract class PluginBase {

    /** 
     * @var Logger
     */
    private $log;

    /**
     * Root path for plugin files
     * @var string
     */
    private $basePath;
    
    /**
     * @var string
     */
    private $name;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @var mixed
     */
    abstract function initialize($initArgs);
    
    /**
     * @return string
     */
    function getBasePath() {
        if (!$this->basePath)
            throw new CartoclientException("Base path not defined");
        return $this->basePath;
    }

    /**
     * @param string
     */
    function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * @param string
     */
    function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    function getName() {
        return $this->name;
    }
    
    /**
     * Get plugin's request or result out of MapRequest or MapResult
     *
     * The name of the request|result field is selected according to this
     * current plugin name (see {@link getName()}). Although this is called
     * getRequest, it is not tied to a request. Unserialization is done
     * globally (not for each plugin). 
     *
     * @param boolean true if a mapRequest, false if mapResult
     * @param mixed mapRequest or mapResult
     * @return mixed mapRequest|Result or NULL if no such request|result
     */
    function getRequest($isRequest, $mapRequest) {

        $type = $isRequest ? 'Request' : 'Result';
        
        $name = $this->getName();
        $field = "${name}${type}";

        if (empty($mapRequest->$field))
            return NULL;

        return $mapRequest->$field;
    }
}

?>