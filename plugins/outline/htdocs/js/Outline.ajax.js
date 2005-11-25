AjaxPlugins.Outline = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
    $('outline_plugin').innerHTML = pluginOutput.htmlCode.outline;
  }  
};


/*
 * Outline plugin's Actions
 */
 
AjaxPlugins.Outline.Actions = {};

AjaxPlugins.Outline.Actions.AddFeature = {

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

AjaxPlugins.Outline.Actions.ChangeMode = {

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

AjaxPlugins.Outline.Actions.Clear = {

  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildRequestFrom(argObject.target) + '&' + AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  }
};