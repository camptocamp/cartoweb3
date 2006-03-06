<?php

/**
 * Classes and exception for asynchronous mode
 * 
 * These methods and exceptions could be moved to:
 *  - AjaxException to /client/Cartoclient.php
 *  - AjaxPluginResponse to /client/FormRenderer.php
 *  - PluginEnabler to /common/PluginManager.php
 *  - Json to ??
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

class AjaxException extends CartoclientException {

}

/**
 * Container for plugins' asynchronous responses
 * This object is used by plugins in Ajaxable::getAjaxResponse() - called by
 * FormRenderer::showAjaxPluginResponse() to feed the pluginResponse XML docuemt
 * @package Client
 */
class AjaxPluginResponse {
	protected $htmlCode;
	protected $variables;

	public function __construct() {
		$this->htmlCode = array ();
		$this->variables = array ();
	}

	public function addHtmlCode($id, $htmlCode) {
		$this->htmlCode[$id] = $htmlCode;
	}

	public function getHtmlCode() {
		return $this->htmlCode;
	}

	public function addVariable($id, $value) {
		$this->variables[$id] = $value;
	}

	public function getVariables() {
		return $this->variables;
	}

	public function isEmpty() {
		return !count($this->htmlCode) and !count($this->variables);
	}
}

/**
 * Abstraction class for defining plugins' enable level
 * This object is used by plugins in Ajaxable::ajaxHandleAction() - called by
 * Cartoclient::doMainAsync() - to set plugins' enableLevel in async mode
 * @package Client
 */
class PluginEnabler {

	/**
	 * @var Cartoclient
	 */
	protected $cartoclient;

	public function __construct(Cartoclient & $cartoclient) {
		if (!$cartoclient instanceof Cartoclient)
			die('The given $cartoclient is not a Cartoclient instance object.');
		$this->cartoclient = $cartoclient;
	}

	/* Helper methods BEGIN */
	private function getPlugin($pluginName) {
		return $this->cartoclient->getPluginManager()->getPlugin($pluginName);
	}

	private function getPlugins() {
		return $this->cartoclient->getPluginManager()->getPlugins();
	}

	private function getCorePluginNames() {
		return $this->cartoclient->getCorePluginNames();
	}

	public function getCoreplugins() {
		$coreplugins = array ();
		foreach ($this->getPlugins() as $plugin) {
			if (in_array($plugin->getName(), $this->getCorePluginNames()))
				$coreplugins[] = $plugin;
		}
		return $coreplugins;
	}

	public function getPlainPlugins() {
		$plainplugins = array ();
		foreach ($this->getPlugins() as $plugin) {
			if (!in_array($plugin->getName(), $this->getCorePluginNames()))
				$plainplugins[] = $plugin;
		}
		return $plainplugins;
	}

	protected function isLoaded($pluginName) {
		return null != $this->getPlugin($pluginName);
	}
	protected function isPlainPlugin($pluginName) {
		return !$this->isCoreplugin($pluginName);
	}
	private function isCoreplugin($pluginName) {
		return in_array($pluginName, $this->getCorepluginNames());
	}
	/* Helper methods END */

	public function getEnabledPluginNames() {
		$enabledPluginNames = array ();
		foreach ($this->getPlugins() as $loadedPlugin) {
			if ($loadedPlugin->isEnabled())
				$enabledPluginNames[] = $loadedPlugin->getName();
		}
		return $enabledPluginNames;
	}

	public function disableCorePlugin($pluginName) {
		if (!$this->isCoreplugin($pluginName))
			throw new AjaxException('Plugin '.$pluginName.' is not a coreplugin.');
		if (!$this->isLoaded($pluginName))
			throw new AjaxException('Plugin '.$pluginName.' is not loaded. ' .
                    'You can only disable loaded coreplugins.');
		$this->getPlugin($pluginName)->disable();
	}

	public function disableCorePlugins() {
		foreach ($this->getCoreplugins() as $coreplugin) {
			$this->disableCorePlugin($coreplugin->getName());
		}
	}

	public function enableCorePlugin($pluginName) {
		if (!$this->isCoreplugin($pluginName))
			throw new AjaxException('Plugin '.$pluginName.' is not a coreplugin.' .
                    'Use disablePlugin() to disable it.');

		if (!$this->isLoaded($pluginName))
			throw new AjaxException('Plugin '.$pluginName.' is not loaded. ' .
                    'You can only disable loaded coreplugins.');
		$this->getPlugin($pluginName)->enable();
	}

	public function enablePlugin($pluginName) {
		if (!$this->isPlainPlugin($pluginName))
			throw new AjaxException('Plugin '.$pluginName.'is a coreplugin. ' . 
                    'Use disableCoreplugin() to disable it.');
		if (!$this->isLoaded($pluginName))
			throw new AjaxException('Plugin '.$pluginName.'is not loaded. ' .
                    'You can only disable loaded plugins.');
		$this->getPlugin($pluginName)->enable();
	}

	public function enablePlugins() {
		foreach ($this->getPlainPlugins() as $plainplugins) {
			$this->enablePlugin($plainplugins);
		}
	}

	public function disablePlugin($pluginName) {
		if (!$this->isPlainPlugin($pluginName))
			throw new AjaxException('Plugin '.$pluginName.'is a coreplugin. ' .
                    'Use disableCoreplugin() to disable it.');
		if (!$this->isLoaded($pluginName))
			throw new AjaxException('Plugin '.$pluginName.'is not loaded. ' .
                    'You can only disable loaded plugins.');
		$this->getPlugin($pluginName)->disable();
	}

	public function disablePlugins() {
		foreach ($this->getPlainPlugins() as $plainplugin) {
			$this->disablePlugin($plainplugin->getName());
		}
	}

	public function setEnableLevel($pluginName, $enableLevelValue) {
		if (!$this->isLoaded($pluginName))
			throw new AjaxException('Plugin '.$pluginName.'is not loaded. ' .
                    'You can only set enable level on loaded plugins.');
		$plugin = $this->getPlugin($pluginName);
		$plugin->setEnableLevel($enableLevelValue);
	}

}

class Json {

	public static function toPhpArray($jsonString) {
		// TODO
	}

	public static function fromPhpArrayToObject($phpArray) {
        // TODO
	}

    public static function fromPhpArrayToArray($phpArray) {
        if (!is_array($phpArray) || count($phpArray) < 1) return '[]';
        $jsonString = '[ ';
        foreach ($phpArray as $value) {
            $value = addslashes($value);
            if (is_string($value)) $jsonString .= '\''.$value.'\'';
            elseif (is_bool($value)) $jsonString .= $value ? 'true' : 'false';
            elseif (is_numeric($value)) $jsonString .= $value;
            elseif (is_null($value)) $jsonString .= 'null';
            elseif (is_array($value)) $jsonString .= Json::fromPhpArray($value);
            else $jsonString .= 'null';
            $jsonString .= ' , ';
        }
        $jsonString = substr($jsonString, 0, -2);
        $jsonString .= ' ]';
        return $jsonString;
    }

}
?>