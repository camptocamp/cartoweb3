AjaxPlugins.Query = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
    
    // Nothing has to be done here: Tables manages to display the results
  }  
};

/*
 * Images plugin's Actions
 */
 
AjaxPlugins.Query.Actions = {};

AjaxPlugins.Query.Actions.Perform = {
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

AjaxPlugins.Query.Actions.Clear = {
  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildRequestFrom(argObject.clickedElement) + '&' + AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  	AjaxHandler.updateDomElement(AjaxPlugins.Tables.tablesResultId, 'innerHTML', '');
  }
};