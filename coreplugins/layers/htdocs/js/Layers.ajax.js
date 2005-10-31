AjaxPlugins.Layers = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */

    /* Redraws layers HTML Code */
	$('folder1').innerHTML = pluginOutput.htmlCode.layers;

	/* Reopen open nodes */
	startOpenNodes = pluginOutput.variables.startOpenNodes;
	// Uses layers.tpl + layers.js mecanism
    eval("var openNodes = new Array("+startOpenNodes+");");
    writeOpenNodes(true);
  }  
};

/*
 * Images plugin's Actions
 */
 
AjaxPlugins.Layers.Actions = {};

AjaxPlugins.Layers.Actions.layerShowHide = {
  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  }
};

AjaxPlugins.Layers.Actions.layerDropDownChange = {
  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  }
};