<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * @package Common
 */
class PluginManager {
    private $log;

    const CLIENT_PLUGINS = 1;
    const SERVER_PLUGINS = 2;

    private $plugins = array();
    
    private $projectHandler;
    
    function getPlugins() {
        return $this->plugins;
    }
    
    function __construct($projectHandler) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->projectHandler = $projectHandler;
    }

    private function getBasePluginPath($basePath, $relativePath, $name) {
            return $basePath . $relativePath . $name . '/';
    }

    private function getPath($basePath, $relativePath, $type, $name) {
        $lastPath = $type == self::CLIENT_PLUGINS ? 
            'client/' : 'server/';
        return $basePath .
            $this->projectHandler->getPath($basePath, $relativePath .
                $name . '/' . $lastPath . $this->getClassName($type, $name) . '.php', '');
    }

    private function getCommonPath($basePath, $relativePath, $name) {
        return $basePath .
            $this->projectHandler->getPath($basePath, $relativePath .
                $name . '/' . 'common/' . ucfirst($name) . '.php', '');
    }

    private function getClassName($type, $name) {
        $prefix = $type == self::CLIENT_PLUGINS ? 
            'Client' : 'Server';       
        return $prefix . ucfirst($name);
    }
    
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

    function callPluginsImplementing($interfaces, $functionName, $args = array()) {

        if (!is_array($interfaces)) {
            $interfaces = array($interfaces);
        }
        foreach ($this->plugins as $plugin) {
            foreach ($interfaces as $interface) {
                if ($plugin instanceof $interface) {
                    call_user_func_array(array($plugin, $functionName), $args);
                    break;
                } 
            }
        }
    }

    function callPlugins($functionName, $args = array()) {

        foreach ($this->plugins as $plugin) {
            call_user_func_array(array($plugin, $functionName), $args);
        }
    }
}
?>