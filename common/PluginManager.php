<?php

class PluginManager {
    private $log;

    const CLIENT_PLUGINS = 1;
    const SERVER_PLUGINS = 2;

    private $plugins = array();
    
    function getPlugins() {
    	return $this->plugins;
    }
    
    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    private function getBasePluginPath($path, $name) {
        return $path . $name . '/';
    }

    private function getPath($path, $type, $name) {
        $lastPath = $type == self::CLIENT_PLUGINS ? 
            'client/' : 'server/';

        $phpFile = ucfirst($name) . '.php';

        return $this->getBasePluginPath($path, $name) . $lastPath;
    }

    private function getCommonPath($path, $name) {
        return $this->getBasePluginPath($path, $name) . 'common/' . 
            ucfirst($name) . '.php';
    }

    private function getClassName($type, $name) {

        $prefix = $type == self::CLIENT_PLUGINS ? 
            'Client' : 'Server';
        
        return $prefix . ucfirst($name);
    }
    
    public function loadPlugins($path, $type, $names, $initArgs=NULL) {

        // TODO: load per plugin configuration file
        //  manage plugin dependency, ...

        foreach ($names as $name) {
            $className = $this->getClassName($type, $name);

            $includePath = $this->getPath($path, $type, $name) . $className . '.php';
            $this->log->debug("trying to load class $includePath");

			// FIXME: this won't work in case of non absolute paths
            if (is_readable($this->getCommonPath($path, $name)))
            	include_once($this->getCommonPath($path, $name));

            if (is_readable($includePath))
            	include_once($includePath);

            if (!class_exists($className)) {
                $this->log->warn("Couldn't load plugin $className");
                continue;
            }

            $plugin = new $className();
            $plugin->setBasePath($this->getBasePluginPath($path, $name));
            $plugin->setName($name);

            if ($initArgs !== NULL) {
                $plugin->initialize($initArgs);
            }

            $this->plugins[] = $plugin;
            $this->$name = $plugin;
        }
    }

    function callPlugins($functionName, $args) {

        foreach ($this->plugins as $plugin) {
            call_user_func_array(array($plugin, $functionName), $args);
        }
    }
}
?>