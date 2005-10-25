AjaxPlugins.Layers = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
	$('folder1').innerHTML = pluginOutput.htmlCode.layers;
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