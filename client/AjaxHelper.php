<?php
/**
 * Classes and exception for AJAX mode
 * 
 * These methods and exceptions could be moved to:
 *  - AjaxException to /client/Cartoclient.php
 *  - AjaxPluginResponse to /client/FormRenderer.php
 *  - PluginEnabler to /common/PluginManager.php
 *  - Json to Utils?
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
 * @package Client
 * @version $Id$
 */


/**
 * AJAX specific exception 
 * @package Client
 */
class AjaxException extends CartoclientException {
}


/**
 * Container for plugins' AJAX responses
 * This object is used by plugins in Ajaxable::getAjaxResponse() - called by
 * FormRenderer::showAjaxPluginResponse() to feed the pluginResponse XML docuemt
 * @package Client
 */
class AjaxPluginResponse {
    
    /**
     * @var array array of assigned htmlCode.
     */
    protected $htmlCode = array();

    /**
     * @var array array of assigned variables.
     */
    protected $variables = array();

    /**
     * Constructor
     */
    public function __construct() {
    }

    /**
     * Adds HTML code to AJAX response.
     * @param string id of the HTML code to add
     * @param string HTML code content
     */
    public function addHtmlCode($id, $htmlCode) {
        $this->htmlCode[$id] = $htmlCode;
    }

    /**
     * Returns an array of HTML code.
     * @return array array of added HTML code.
     */
    public function getHtmlCode() {
        return $this->htmlCode;
    }

    /**
     * Adds a value to AJAX response.
     * @param string variable id
     * @param string variable value
     */
    public function addVariable($id, $value) {
        $this->variables[$id] = $value;
    }

    /**
     * Returns an array of variables.
     * @return array array of added variables
     */
    public function getVariables() {
        return $this->variables;
    }

    /**
     * Returns true if both HTML and variables content are empty
     * @return bool true if both HTML and variables content are empty,
     *              false otherwise
     */
    public function isEmpty() {
        return count($this->htmlCode) + count($this->variables) == 0;
    }
}


/**
 * Abstraction class for defining plugins' enable level
 * This object is used by plugins in Ajaxable::ajaxHandleAction()
 * to set plugins' enableLevel in AJAX mode
 * @package Client
 */
class PluginEnabler {

    /**
     * @var Cartoclient Cartoclient instance
     */
    protected $cartoclient;

    /**
     * Constructor
     * @param Cartoclient cartoclient instance
     */
    public function __construct(Cartoclient & $cartoclient) {
        if (!$cartoclient instanceof Cartoclient)
            die('The given $cartoclient is not a Cartoclient instance object.');
        $this->cartoclient = $cartoclient;
    }

    /**
     * Wrapper method for PluginManager::getPlugin().
     * @param string name of the plugin whose instance is to be returned
     * @return ClientPlugin
     * @see PluginManager::getPlugin()
     */
    protected function getPlugin($pluginName) {
        return $this->cartoclient->getPluginManager()->getPlugin($pluginName);
    }

    /**
     * Wrapper method for PluginManager::getPlugins().
     * @return array array of all loaded client plugin objects (ClientPlugin)
     * @see PluginManager::getPlugins()
     */
    protected function getPlugins() {
        return $this->cartoclient->getPluginManager()->getPlugins();
    }

    /**
     * Wrapper method for PluginManager::getCorepluginNames().
     * @return array array of coreplugins names (string)
     * @see PluginManager::getCorepluginNames()
     */
    protected function getCorepluginNames() {
        return $this->cartoclient->getCorePluginNames();
    }

    /**
     * Returns true if the given plugin name is loaded.
     * @param string name of the plugin
     * @return bool true if the given plugin name is loaded
     */
    protected function isLoaded($pluginName) {
        return null != $this->getPlugin($pluginName);
    }
    
    protected function checkLoaded($pluginName) {
        if (!$this->isLoaded($pluginName)) {
            throw new AjaxException("Plugin '$pluginName' is not loaded. " .
                    "You can only set the enable level of loaded plugins.");
        }        
    }
    
    /**
     * Returns true if the given plugin is a coreplugin
     * @param string name of the plugin
     * @return bool true if the given plugin name is a coreplugin
     */
    protected function isCoreplugin($pluginName) {
        return in_array($pluginName, $this->getCorepluginNames());
    }
    

    /**
     * Disables all coreplugins: set their enable level
     * to ClientPlugin::ENABLE_LEVEL_LOAD.
     */
    public function disableCoreplugins() {
        $plugins = $this->getPlugins();
        foreach ($plugins as $plugin) {
            if ($this->isCoreplugin($plugin->getName())) {
                $plugin->disable();
            }
        }
    }      

    /**
     * Disables all plugins that are not coreplugins: set their enable level
     * to ClientPlugin::ENABLE_LEVEL_LOAD.
     */
    public function disablePlugins() {
        $plugins = $this->getPlugins();
        foreach ($plugins as $plugin) {
            if (!$this->isCoreplugin($plugin->getName())) {
                $plugin->disable();
            }
        }
    }      
    
    /**
     * Enables the given plugin name: set its enable level to
     * to ClientPlugin::ENABLE_LEVEL_LOAD.
     * @param string name of the plugin to enable
     */
    public function enablePlugin($pluginName) {
        $this->checkLoaded($pluginName);
        $this->getPlugin($pluginName)->enable();
    }

    /**
     * Disables the given plugin name: set its enable level to
     * to ClientPlugin::ENABLE_LEVEL_LOAD.
     * @param string name of the coreplugin to enable
     */
    public function disablePlugin($pluginName) {
        $this->checkLoaded($pluginName);
        $this->getPlugin($pluginName)->enable();
    }  

    /**
     * Sets the enable level of the given plugin name
     * to the given enable level value.
     * to ClientPlugin::ENABLE_LEVEL_LOAD.
     * @param string name of the plugin to enable
     * @param int value of the enable level to set
     * @see ClientPlugin
     */
    public function setEnableLevel($pluginName, $enableLevelValue) {
        $this->checkLoaded($pluginName);
        $plugin = $this->getPlugin($pluginName);
        $plugin->setEnableLevel($enableLevelValue);
    }

}


/**
 * PHP to JSON type converter (incomplete)
 * @package Client
 * @todo Json::objectFromPhp()
 * @todo Json::toPhpArray()
 * @todo Json::toPhpObject()
 */
class Json {

    /**
     * Serializes a PHP variable to a JSON array 
     * @param mixed PHP variable
     * @param bool Escape quotes
     */
    public static function arrayFromPhp($phpVariable, $doEscape = true) {
        if (is_array($phpVariable)) {
            return self::fromPhpArrayToJsonArray($phpVariable, $doEscape);
        } elseif (is_object($phpVariable)) {
            return self::fromPhpObjectToJsonArray($phpVariable, $doEscape);
        } elseif (false) {
            // Other PHP variable types
        } elseif (is_null($phpVariable)) {
            return '[]';
        } else {
            throw new Exception('The JSON serialization of the given ' .
                                'variable type (' . gettype($phpVariable) . ') ' .
                                'is not yet implemented, sorry!');
        }
    }

    /**
     * Serializes a PHP array to a JSON array
     * (warning: keys will be lost)
     * @param array PHP array to be serialized in JSON array
     * @param bool Escape quotes
     */
    private static function fromPhpArrayToJsonArray($phpArray, $doEscape = true) {
        $jsonString = '[ ';
        foreach ($phpArray as $value) {
            if (is_string($value)) {
                $value = $doEscape ? Json::escapeQuotes($value) : $value;
                $jsonString .= '\'' . $value . '\'';
            } elseif (is_bool($value)) {
                $jsonString .= $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $jsonString .= $value;
            } elseif (is_null($value)) {
                $jsonString .= 'null';
            } elseif (is_array($value)) {
                $jsonString .= Json::arrayFromPhp($value);
            } elseif (is_object($value)) {
                $jsonString .= Json::arrayFromPhp($value);
            } else {
                $jsonString .= 'null';
            }
            $jsonString .= ', ';
        }
        
        if (count($phpArray)) {
            $jsonString = substr($jsonString, 0, -2);
        }
        $jsonString .= ' ]';
        return $jsonString;
    }

    /**
     * Serializes a PHP object to a JSON array 
     * @param mixed PHP object
     * @param bool Escape quotes
     */
    private static function fromPhpObjectToJsonArray($phpObject, $doEscape = true) {
        $jsonString = '[ ';
        $objectProps = get_object_vars($phpObject);
        foreach ($objectProps as $propValue) {
            $value = $propValue;
            if (is_string($value)) {
                $value = $doEscape ? Json::escapeQuotes($value) : $value;
                $jsonString .= '\'' . $value . '\'';
            } elseif (is_bool($value)) {
                $jsonString .= $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $jsonString .= $value;
            } elseif (is_null($value)) {
                $jsonString .= 'null';
            } elseif (is_array($value)) {
                $jsonString .= Json::arrayFromPhp($value);
            } elseif (is_object($value)) {
                $jsonString .= Json::arrayFromPhp($value);
            } else {
                $jsonString .= 'null';
            }
            $jsonString .= ' , ';
        }

        if (count($phpObject)) {
            $jsonString = substr($jsonString, 0, -2);
        }
        $jsonString .= ' ]';
        return $jsonString;
    }



    /**
     * Serializes a PHP variable to a JSON object
     * (warning: properties name will be lost)
     * @param mixed PHP variable
     * @param bool Escape quotes
     */
    public static function objectFromPhp($phpVariable, $doEscape = true) {
        if (is_array($phpVariable)) {
            return self::fromPhpArrayToJsonObject($phpVariable, $doEscape);
        } elseif (is_object($phpVariable)) {
            return self::fromPhpObjectToJsonObject($phpVariable, $doEscape);
        } elseif (false) {
            // Other PHP variable types
        } elseif (is_null($phpVariable)) {
            return '{}';
        } else {
            throw new Exception('The JSON serialization of the given ' .
                                'variable type (' . gettype($phpVariable) . ') ' .
                                'is not yet implemented, sorry!');
        }
    }

    /**
     * Serializes a PHP array to a JSON array 
     * @param array PHP array to be serialized in JSON array
     * @param bool Escape quotes
     */
    private static function fromPhpArrayToJsonObject($phpArray, $doEscape = true) {
        $jsonString = '{ ';
        foreach ($phpArray as $key => $value) {
            $jsonString .= $key . ': ';
            if (is_string($value)) {
                $value = $doEscape ? Json::escapeQuotes($value) : $value;
                $jsonString .= '\'' . $value . '\'';
            } elseif (is_bool($value)) {
                $jsonString .= $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $jsonString .= $value;
            } elseif (is_null($value)) {
                $jsonString .= 'null';
            } elseif (is_array($value)) {
                $jsonString .= Json::objectFromPhp($value);
            } elseif (is_object($value)) {
                $jsonString .= Json::objectFromPhp($value);
            } else {
                $jsonString .= 'null';
            }
            $jsonString .= ', ';
        }
        
        if (count($phpArray)) {
            $jsonString = substr($jsonString, 0, -2);
        }
        $jsonString .= ' }';
        return $jsonString;
    }

    /**
     * Serializes a PHP object to a JSON object 
     * @param mixed PHP object
     * @param bool Escape quotes
     */
    private static function fromPhpObjectToJsonObject($phpObject, $doEscape = true) {
        $jsonString = '{ ';
        $objectProps = get_object_vars($phpObject);
        foreach ($objectProps as $propName => $propValue) {
            $jsonString .= $propName . ': ';                
            $value = $phpObject->$propName;
            if (is_string($value)) {
                $value = $doEscape ? Json::escapeQuotes($value) : $value;
                $jsonString .= '\'' . $value . '\'';
            } elseif (is_bool($value)) {
                $jsonString .= $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $jsonString .= $value;
            } elseif (is_null($value)) {
                $jsonString .= 'null';
            } elseif (is_array($value)) {
                $jsonString .= Json::objectFromPhp($value);
            } elseif (is_object($value)) {
                $jsonString .= Json::objectFromPhp($value);
            } else {
                $jsonString .= 'null';
            }
            $jsonString .= ' , ';
        }

        if (count($phpObject)) {
            $jsonString = substr($jsonString, 0, -2);
        }
        $jsonString .= ' }';
        return $jsonString;
    }


    /**
     * Escapes single quotes (using a backslash) 
     * @param mixed PHP variable
     * @param bool Escape quotes
     */
    private static function escapeQuotes($stringToEncode) {
        return str_replace("'", "\'", $stringToEncode);
    }
}
?>