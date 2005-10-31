<?php
/*
 * Created on Sep 30, 2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 class AjaxException extends CartowebException {

}
 
 
 /**
 * Contains the output of a plugin.
 * This object is used by plugins in AjaxPlugin::ajaxResponse() to
 * feed the pluginResponse XML file. 
 * @package Client
 */
 class AjaxPluginResponse {
 	protected $htmlCode;
 	protected $variables;
 	
 	public function __construct() {
 		$this->htmlCode = array();
 		$this->variables = array();
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
 
 class PluginsDirectives {
 	
 	/**
 	 * @var PluginManager
 	 */
 	protected $cartoclient;

 	public function __construct(&$cartoclient) {
 		if (!$cartoclient instanceof Cartoclient)
 			die('PlugingDirectives object: constructor: given $cartoclient is not a Cartoclient instance object.');
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
    	$coreplugins = array();
 		foreach ($this->getPlugins() as $plugin) {
 			if (in_array($plugin->getName(), $this->getCorePluginNames()))
 				$coreplugins[] = $plugin;
 		}
 		return $coreplugins;
    }

    public function getPlainPlugins() {
    	$plainplugins = array();
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
 		$enabledPluginNames = array();
 		foreach ($this->getPlugins() as $loadedPlugin) {
 			if ($loadedPlugin->isEnabled())
 				$enabledPluginNames[] = $loadedPlugin->getName();
 		}
 		return $enabledPluginNames;
 	}
 	
 	public function disableCorePlugin($pluginName) {
 		if (!$this->isCoreplugin($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . ' is not a coreplugin.');
 		if (!$this->isLoaded($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . ' is not loaded. You can only disable loaded coreplugins.');
 		$this->getPlugin($pluginName)->disable(); 		
 	}

 	public function disableCorePlugins() {
 		foreach ($this->getCorepluginS() as $coreplugin) {
 			$this->disableCorePlugin($coreplugin->getName());
 		}
 	}

 	public function enableCorePlugin($pluginName) {
 		if (!$this->isCoreplugin($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . ' is not a coreplugin.');
 		if (!$this->isLoaded($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . ' is not loaded. You can only disable loaded coreplugins.');
 		$this->getPlugin($pluginName)->enable(); 		
 	}

 	public function enablePlugin($pluginName) {
 		if (!$this->isPlainPlugin($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . 'is a coreplugin. Use disableCoreplugin() to disable it.');
 		if (!$this->isLoaded($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . 'is not loaded. You can only disable loaded plugins.');
 		$this->getPlugin($pluginName)->enable();
 	}

 	public function enablePlugins() {
 		foreach ($this->getPlainPlugins() as $plainplugins) {
 			$this->enablePlugin($plainplugins);
 		}
 	}
 	 	
 	public function disablePlugin($pluginName) {
 		if (!$this->isPlainPlugin($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . 'is a coreplugin. Use disableCoreplugin() to disable it.');
 		if (!$this->isLoaded($pluginName))
 			throw new AjaxException('Plugin ' . $pluginName . 'is not loaded. You can only disable loaded plugins.');
 		$this->getPlugin($pluginName)->disable();
 	}

 	public function disablePlugins() {
 		foreach ($this->getPlainPlugins() as $plainplugin) {
 			$this->disablePlugin($plainplugin->getName());
 		}
 	}
 	 	
/*
 	public function addDirective($pluginName, $directiveName) {
 		$plugin = $this->pluginManager->getPlugin($pluginName);
 		
 		// Checks
 		if ($plugin == null)
 			throw new AjaxException('Plugin ' . $pluginName . ' is not loaded. You can only add directives to loaded plugins.');
 		if ($directiveName != false && !$plugin->directiveExists($directiveName))
 			throw new AjaxException('Directive ' . $directiveName . ' is not defined in plugin ' . $pluginName);
 		
 		// Add directive
 		$this->getPlugin($pluginName)->setDirective($directiveName);	 	
 	}
 	
 	public function getDirectives() {
 		return $this->directives;
 	}

 	public function getPluginDirectives($pluginName) {
 		return $this->directives[$pluginName];
 	}
 */
 }
?>
