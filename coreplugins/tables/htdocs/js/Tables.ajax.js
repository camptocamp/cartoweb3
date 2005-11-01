AjaxPlugins.Tables = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
	
	$('tables_result').innerHTML = pluginOutput.htmlCode.tableResult;
  }
};

/*
 * Tables plugin's Actions
 */
 
AjaxPlugins.Tables.Actions = {};

AjaxPlugins.Tables.Actions.myAction = {
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