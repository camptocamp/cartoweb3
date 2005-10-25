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
 	 * @var array Array containing directives per plugin
 	 *            (array[pluginName][directiveName])
 	 */
 	protected $directives;
 	
 	/**
 	 * @var PluginManager
 	 */
 	protected $pluginManager;

 	public function __construct(&$pluginManager) {
 		if (!$pluginManager instanceof PluginManager)
 			die('PlugingDirectives object: constructor: given $pluginManager is not a PluginManager!');
		$this->pluginManager = $pluginManager;
		$this->directives = array();
 	}

 	public function add($pluginName, $directiveName = false) {
 		$plugin = $this->pluginManager->getPlugin($pluginName);
 		
 		// Checks
 		if ($plugin == null)
 			throw new AjaxException('Plugin ' . $pluginName . ' is not loaded.');
 		if ($directiveName != false && !$plugin->directiveExists($directiveName))
 			throw new AjaxException('Directive ' . $directiveName . ' is not defined in plugin ' . $pluginName);
 		
 		// Add directive
 		if ($directiveName === false)
	 		$this->directives[$pluginName] = array();
	 	else
	 		$this->directives[$pluginName][$directiveName] = true;	 	
 	}
 	
 	public function getDirectives() {
 		return $this->directives;
 	}

 	public function getPluginDirectives($pluginName) {
 		return $this->directives[$pluginName];
 	}
 }
?>
