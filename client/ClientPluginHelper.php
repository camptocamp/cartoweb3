<?php
/**
 * Interface helpers for client plugins
 * @package Client
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>  
 * @version $Id$
 */

/**
 * Helper for client plugin interfaces
 *
 * Helpers are called by {@link PluginManager::callPluginsImplementing()}.
 * They implement functionnalities common to all plugins implementing an
 * interface.
 * @package Client
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>  
 */
class ClientPluginHelper {
    
    /**
     * @var Logger
     */
    protected $log;

    /** 
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(get_class($this));
    }
}

/**
 * Helper for {@link ToolProvider}
 * @package Client
 */
class ToolProviderHelper extends ClientPluginHelper {
    
    /**
     * Converts a name one_toto_two ==> OneTotoTwo
     * @param string input name
     * @return string converted name
     */
    private function convertName($name) {
        $n = explode('_', $name);
        $n = array_map('ucfirst', $n);
        return implode($n);
    }

    /**
     * Updates tools info plugin name and weight
     *
     * Weight is read in plugin configuration file.
     * Example: id = my_tool, variable in configuration file = weightMyTool.
     * @param ClientPlugin plugin
     * @param ToolDescription tool to update
     * @return ToolDescription updated tool
     */
    private function updateTool($plugin, ToolDescription $tool) {

        $tool->plugin = $plugin->getName();
    
        $weightConfigName = 'weight' . $this->convertName($tool->id);
        $weight = $plugin->getConfig()->$weightConfigName;
        if ($weight)
            $tool->weight = $weight;

        return $tool;
    }    
    
    /** 
     * Calls plugin's {@link ToolProvider::getTools()}, updates tools
     * and returns them
     * @param ClientPlugin plugin
     * @return array array of {@link ToolDescription}
     */
    final function getToolsHelper($plugin) {

        $tools = $plugin->getTools();
        $result = array();
         
        // update tools
        foreach ($tools as $tool) {
            $tool = $this->updateTool($plugin, $tool);
            if ($tool->weight >= 0) {
                $result[] = $tool;
            }
        }   
        return $result;
    }
}

/**
 * Helper for {@link Sessionable}
 * @package Client
 */
class SessionableHelper extends ClientPluginHelper {

    /**
     * Loads client session and calls plugin's
     * {@link Sessionable::loadSession()}
     * @param ClientPlugin plugin
     */
    final function loadSessionHelper($plugin) {
        
        $cartoclient = $plugin->getCartoclient();        
        $clientSession = $cartoclient->getClientSession();

        $className = get_class($plugin);

        $this->log->debug(isset($clientSession->pluginStorage->$className));
        if (empty($clientSession->pluginStorage->$className)) {
            $this->log->warn("no session to load for plugin $className");
            return;
        }

        $plugin->loadSession(unserialize($clientSession->pluginStorage->
                                                         $className));

        $this->log->debug("plugin $className loads:");
        $this->log->debug(var_export(unserialize($clientSession->pluginStorage
                                                 ->$className), true));
    }

    /**
     * Gets plugin's session data and save it
     * @param ClientPlugin plugin
     */
    final function saveSessionHelper($plugin) {

        $cartoclient = $plugin->getCartoclient();
        $className = get_class($plugin);

        $toSave = $plugin->saveSession();
        $this->log->debug("plugin $className wants to save:");
        $this->log->debug(var_export(serialize($toSave), true));
        if (!$toSave) {
            $this->log->debug("Plugin $className did not return a session to save");
            return;
        }

        $clientSession = $cartoclient->getClientSession();
        $clientSession->pluginStorage->$className = serialize($toSave);
        $cartoclient->setClientSession($clientSession);
    }
}

/**
 * Helper for {@link InitUser}
 * @package Client
 */
class InitUserHelper extends ClientPluginHelper {
  
    /**
     * Unserializes init object specific to plugin
     * @param ClientPlugin plugin
     * @param MapInfo MapInfo
     */
    private function unserializeInit($plugin, $mapInfo) {
        
        $name = $plugin->getName();
        $field = $name . 'Init';
        
        if (empty($mapInfo->$field))
            return NULL;
            
        $result = Serializable::unserializeObject($mapInfo, $field);
        
        if (!is_null($result))                
            $mapInfo->$field = $result;
        
        return $result;
    }

    /**
     * Gets init object and calls plugin's {@link InitProvider::handleInit()}
     * @param ClientPlugin plugin
     * @param MapInfo MapInfo
     */
    final function handleInitHelper($plugin, $mapInfo) {

        $pluginInit = $this->unserializeInit($plugin, $mapInfo);
        
        if (!empty($pluginInit)) {        
            $plugin->handleInit($pluginInit);
        }
    }
}

/**
 * Helper for {@link ServerCaller}
 * @package Client
 */
class ServerCallerHelper extends ClientPluginHelper {

    /**
     * Gets plugin specific result out of {@link MapResult} and calls 
     * plugin's {@link ServerCaller::initializeResult()}
     * @param ClientPlugin plugin
     * @param MapResult complete MapResult
     */
    final function initializeResultHelper($plugin, $mapResult) {
        
        $pluginResult = $plugin->getRequest(false, $mapResult);
        
        $plugin->initializeResult($pluginResult);
    }

    /**
     * Gets plugin specific result out of {@link MapResult} and calls 
     * plugin's {@link ServerCaller::handleResult()}
     * @param ClientPlugin plugin
     * @param MapResult complete MapResult
     */
    final function handleResultHelper($plugin, $mapResult) {
        
        $pluginResult = $plugin->getRequest(false, $mapResult);
        
        $plugin->handleResult($pluginResult);
    }
}

?>
