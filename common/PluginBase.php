<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * @package Common
 */
abstract class PluginBase {
    private $log;

    private $basePath;
    private $name;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    abstract function initialize($initArgs);
    
    function getBasePath() {
        if (!$this->basePath)
            throw new CartoclientException("Base path not defined");
        return $this->basePath;
    }

    function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    function setName($name) {
        $this->name = $name;
    }

    function getName() {
        return $this->name;
    }
    
    /**
     * Get a request or a result form a map{Request|Result}. The name of
     * the request|result field is selected according to this current
     * plugin name. @see getName().
     * Also this is called getRequest, it is not tied to a request.
     * Unserialization is done globally (not for each plugin) 
     *
     * @param isRequest true if a mapRequest, false if mapResult
     * @param mapRequest The mapRequest or mapResult
     * 
     * @returns the mapRequest|Result or NULL if no such request|result
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