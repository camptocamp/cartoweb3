<?php
/**
 * Plugin management tools
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
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
    
    const CLIENT = 1;
    const SERVER = 2;

    /**
     * Plugin objects storage
     * @var array
     */
    private $plugins = array();
    
    private $helpers = array();
    
    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var int
     */
    private $kind;
    
    /**
     * @var ProjectHandler
     */
    private $projectHandler;
    
    static private $replacePlugin = NULL;
    
    /**
     * Constructor
     * @param ProjectHandler
     */
    public function __construct($rootPath, $kind, $projectHandler) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->rootPath = $rootPath;
        $this->kind = $kind;
        $this->projectHandler = $projectHandler;
    }

    /**
     * Tells what plugin the current one replaces.
     * @param string replacement plugin name
     */
    static public function replacePlugin($name) {
        self::$replacePlugin = $name;
    }

    /**
     * Returns the plugins objects list.
     * @return array
     */
    public function getPlugins() {
        return $this->plugins;
    }
    
    /**
     * Returns full plugin base path
     * @param string path to plugins root
     * @param string plugin name
     * @return string
     */
    private function getBasePluginPath($relativePath, $name) {
        return $this->rootPath . $relativePath . $name . '/';
    }

    /**
     * Returns plugin's main class file name
     *
     * Also depends on the project.
     * @param string path to plugins root
     * @param string plugin name
     * @return string
     */
    private function getPluginFilename($relativePath, $name) {
        
        $lastPath = $this->kind == self::CLIENT ? 
            'client/' : 'server/';
        return $this->rootPath .
            $this->projectHandler->getPath($relativePath .
                $name . '/' . $lastPath . $this->getClassName($name) . '.php');
    }

    /**
     * Returns plugin's common file path 
     *
     * Also depends on the project.
     * @param string path to plugins root
     * @param string plugin name
     * @return string
     */
    private function getCommonPath($relativePath, $name) {
        
        return $this->rootPath .
            $this->projectHandler->getPath($relativePath .
                $name . '/' . 'common/' . ucfirst($name) . '.php');
    }

    /**
     * Constructs a plugin class name
     *
     * Class names are in the form ClientMyPlugin or ServerMyPlugin.
     * @param string
     * @return string
     */
    private function getClassName($name) {
        $prefix = $this->kind == self::CLIENT ? 
            'Client' : 'Server';       
        return $prefix . ucfirst($name);
    }

    /**
     * Returns the relative path to the plugin parent directory. The directory
     * layout is as follow:
     * 
     * CARTOWEB_HOME / relativePath / pluginName / {client,common,server,...}
     * 
     */    
    public function getRelativePath($name) {
        
        $pluginPath = $this->projectHandler->getPath('coreplugins/' . $name);
        $isCorePlugin = is_dir(CARTOWEB_HOME . $pluginPath);
        return $isCorePlugin ? 'coreplugins/' : 'plugins/';
    }
    
    /**
     * Tries to include plugin PHP scripts
     * @param string plugin name
     */
    private function includeClassFiles($name) {
        
        $relativePath = $this->getRelativePath($name);
        
        $includePath = $this->getPluginFilename($relativePath, $name);
        $this->log->debug("trying to load class $includePath");
        $this->log->debug($this->getCommonPath($relativePath, $name));

        // FIXME: this won't work in case of non absolute paths
        if (is_readable($this->getCommonPath($relativePath, $name)))
            include_once($this->getCommonPath($relativePath, $name));

        if (is_readable($includePath))
            include_once($includePath);
    }
    
    /**
     * Loads plugins
     * 
     * Includes all plugin files and creates plugin object.
     * @param array array of plugin names
     * @param mixed optional initialization arguments
     */
    public function loadPlugins($names, $initArgs=NULL) {

        // TODO: manage plugin dependency, ...

        if (empty($names)) {
            $this->log->warn('no plugin to load');
            return;
        }        

        foreach ($names as $name) {
        
            $className = $this->getClassName($name);

            if (isset($this->$name)) {   
                $msg = "Plugin $className already loaded";
                throw new CartocommonException($msg);
            }

            $this->includeClassFiles($name);

            if (!class_exists($className)) {
                $msg = "Couldn't load plugin $className";
                throw new CartocommonException($msg);
            }

            $plugin = new $className();

            $extendedName = $name;
            if (!is_null($plugin->replacePlugin())) {
                $name = $plugin->replacePlugin();
            }

            $plugin->setBasePath($this->getBasePluginPath(
                                    $this->getRelativePath($name), $name));
            $plugin->setName($name);
            $plugin->setExtendedName($extendedName);
            
            if ($initArgs !== NULL) {
                $plugin->initializeConfig($initArgs);
            }

            $found = NULL;
            if (!is_null($plugin->replacePlugin())) {
                foreach ($this->plugins as $key => $oldPlugin) {
                    if ($oldPlugin->getName() == $plugin->replacePlugin()) {
                        $found = $key;
                        break;
                    }
                }
                if (!is_null($found)) {
                    $this->plugins[$found] = $plugin;
                }
            }
            if (is_null($found)) {
                $this->plugins[] = $plugin;
            }
            $this->$name = $plugin;
        }
    }

    /**
     * Calls a function on the given plugin that implements the given 
     * interface.
     * @param PluginBase
     * @param string interface name
     * @param string function name
     * @param array function arguments
     */
    public function callPluginImplementing($plugin, $interface, $functionName, 
                                    $args = array()) {

        if ($plugin instanceof $interface) {
            $helperClass = $interface . 'Helper';
            if (class_exists($helperClass)) {
                $helperMethod = $functionName . 'Helper';
                if (is_callable(array($helperClass, $helperMethod))) {
                    if (!array_key_exists($interface, $this->helpers)) {
                        $this->helpers[$interface] = new $helperClass;
                    }
                    if (!is_array($args)) {
                        $args = array($args);
                    }
                    $helperArgs = array_merge(array($plugin), $args);
                    return call_user_func_array(array(
                                                    $this->helpers[$interface],
                                                    $helperMethod), 
                                                $helperArgs);
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
    public function callPluginsImplementing($interface, $functionName, 
                                     $args = array()) {

        foreach ($this->plugins as $plugin) {
            $this->callPluginImplementing($plugin, $interface, $functionName, 
                                          $args);
        }
    }

    /**
     * Calls a function on plugins
     * @param string function name
     * @param array function arguments
     */
    public function callPlugins($functionName, $args = array()) {

        foreach ($this->plugins as $plugin) {
            call_user_func_array(array($plugin, $functionName), $args);
        }
    }
    
    /**
     * Returns plugin object for a plugin name
     * @param string name
     * @return PluginBase 
     */
    public function getPlugin($pluginName) {
        
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
     * @deprecated This method is deprecated and should not be used any more.
     *   See the corresponding page on how to update your plugin.
     */
    public function getCurrentPlugin() {
        
        if (preg_match('#^(.*)(\/?)([a-z0-9_-]*)(\/?)([a-z0-9_-]*).php$#iU', 
                       $_SERVER['PHP_SELF'],
                       $match)) {
            $plugin = $match[3];
        } else {
            $plugin = false;
        }

        if (!$plugin)
            throw new CartocommonException('Failed to get current plugin id');

        return $this->getPlugin($plugin);
    }
}
?>
