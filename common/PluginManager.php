<?php
/**
 * Plugin management tools
 * @package Common
 * @version $Id$
 */

/**
 * Class used to manage pool of plugins
 * @package Common
 */
class PluginManager {
    
    /**
     * @var Logger
     */
    private $log;

    const CLIENT_PLUGINS = 1;
    const SERVER_PLUGINS = 2;

    /**
     * Plugin objects storage
     * @var array
     */
    private $plugins = array();
    
    private $helpers = array();
    
    /**
     * @var ProjectHandler
     */
    private $projectHandler;
    
    /**
     * @return array
     */
    function getPlugins() {
        return $this->plugins;
    }
    
    /**
     * @param ProjectHandler
     */
    function __construct($projectHandler) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->projectHandler = $projectHandler;
    }

    /**
     * Returns full plugin base path
     * @param string path to CartoWeb root
     * @param string path to plugins root
     * @param string plugin name
     * @return string
     */
    private function getBasePluginPath($basePath, $relativePath, $name) {
            return $basePath . $relativePath . $name . '/';
    }

    /**
     * Returns plugin's main class file path
     *
     * Also depends on the project.
     * @param string path to CartoWeb root
     * @param string path to plugins root
     * @param int type (client or server)
     * @param string plugin name
     * @return string
     */
    private function getPath($basePath, $relativePath, $type, $name) {
        $lastPath = $type == self::CLIENT_PLUGINS ? 
            'client/' : 'server/';
        return $basePath .
            $this->projectHandler->getPath($basePath, $relativePath .
                $name . '/' . $lastPath . $this->getClassName($type, $name) . '.php', '');
    }

    /**
     * Returns plugin's common file path 
     *
     * Also depends on the project.
     * @param string path to CartoWeb root
     * @param string path to plugins root
     * @param string plugin name
     * @return string
     */
    private function getCommonPath($basePath, $relativePath, $name) {
        return $basePath .
            $this->projectHandler->getPath($basePath, $relativePath .
                $name . '/' . 'common/' . ucfirst($name) . '.php', '');
    }

    /**
     * Constructs a plugin class name
     *
     * Class names are in the form ClientMyPlugin or ServerMyPlugin.
     * @param int
     * @param string
     * @return string
     */
    private function getClassName($type, $name) {
        $prefix = $type == self::CLIENT_PLUGINS ? 
            'Client' : 'Server';       
        return $prefix . ucfirst($name);
    }
    
    /**
     * Loads plugins
     * 
     * Includes all plugin files and creates plugin object.
     * @param string path to CartoWeb root
     * @param string path to plugins root
     * @param int type (client or server)
     * @param array array of plugin names
     * @param mixed optional initialization arguments
     */
    public function loadPlugins($basePath, $relativePath, $type, $names, $initArgs=NULL) {

        // TODO: load per plugin configuration file
        //  manage plugin dependency, ...

        if (empty($names)) {
            $this->log->warn('no plugin to load');
            return;
        }        

        $path = $basePath . $relativePath; 
        foreach ($names as $name) {
            $className = $this->getClassName($type, $name);

            $includePath = $this->getPath($basePath, $relativePath, $type, $name);
            $this->log->debug("trying to load class $includePath");
            $this->log->debug($this->getCommonPath($basePath, $relativePath, $name));

            // FIXME: this won't work in case of non absolute paths
            if (is_readable($this->getCommonPath($basePath, $relativePath, $name)))
                include_once($this->getCommonPath($basePath, $relativePath, $name));

            if (is_readable($includePath))
                include_once($includePath);

            if (!class_exists($className)) {
                $this->log->warn("Couldn't load plugin $className");
                continue;
            }

            $plugin = new $className();
            $plugin->setBasePath($this->getBasePluginPath($basePath, $relativePath, $name));
            $plugin->setName($name);

            if ($initArgs !== NULL) {
                $plugin->initialize($initArgs);
            }

            $this->plugins[] = $plugin;
            $this->$name = $plugin;
        }
    }

    function callPluginImplementing($plugin, $interface, $functionName, $args = array()) {

        if ($plugin instanceof $interface) {
            $helperClass = $interface . 'Helper';
            if (class_exists($helperClass)) {
                $helperMethod = $functionName . 'Helper';
                if (is_callable(array($helperClass, $helperMethod))) {
                    if (!array_key_exists($interface, $this->helpers)) {
                        $this->helpers[$interface] = new $helperClass;
                    }
                    $helperArgs = array_merge(array($plugin), $args);
                    return call_user_func_array(array($this->helpers[$interface],
                                                $helperMethod), $helperArgs);
                }
            }
            return call_user_func_array(array($plugin, $functionName), $args);
        }
    }

    /**
     * Calls a function on plugins implementing an interface
     * @param string interface name
     * @param string function name
     * @param array function arguments
     */
    function callPluginsImplementing($interface, $functionName, $args = array()) {

        foreach ($this->plugins as $plugin) {
            $this->callPluginImplementing($plugin, $interface, $functionName, $args);
        }
    }
    
    /**
     * Returns plugin object for a plugin name
     * @param string name
     * @return PluginBase 
     */
    function getPlugin($pluginName) {
        
        foreach ($this->plugins as $plugin) {
            if ($pluginName == $plugin->getName()) {
                return $plugin;
            }
        }
        return NULL;        
    }
    
    /**
     * Returns current plugin objet
     *
     * Plugin name is found using URL.
     * @return PluginBase
     */
    function getCurrentPlugin() {
        
        ereg('(\/.*)*\/(.*)\/(.*).php', $_SERVER['PHP_SELF'], $match);
        return $this->getPlugin($match[2]);
    }
}

?>