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

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Initializes plugin
     * 
     * Internal call, use {@link PluginBase::initialize()} to extend
     * initialization in custom plugins.
     * @param mixed
     */
    abstract function initializeConfig($initArgs);
    
    /**
     * @return string
     */
    public function getBasePath() {
        if (!$this->basePath)
            throw new CartocommonException("Base path not defined");
        return $this->basePath;
    }

    /**
     * @param string
     */
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * @param string
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * @param string
     */
    public function setExtendedName($extendedName) {
        $this->extendedName = $extendedName;
    }

    /**
     * @return string
     */
    public function getExtendedName() {
        return $this->extendedName;
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
    public function getRequest($isRequest, $mapRequest) {

        $type = $isRequest ? 'Request' : 'Result';
        
        $name = $this->getName();
        $field = "${name}${type}";

        if (empty($mapRequest->$field))
            return NULL;

        return $mapRequest->$field;
    }

    /** 
     * Returns name of parent plugin in case of plugin extension
     *
     * Must be overridden in child plugin class
     * @return string
     */
    public function replacePlugin() {
        return null;
    }

    /**
     * Plugin initialization (can be extended in custom plugins)
     */
    public function initialize() {}
    
}

?>
