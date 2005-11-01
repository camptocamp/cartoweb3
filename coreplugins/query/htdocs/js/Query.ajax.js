AjaxPlugins.Query = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
/*
	ajaxQueryDivElement = document.createElement('div');
	ajaxQueryDivElement.id = 'ajaxQuery';
	ajaxQueryDivElement.style.position = 'absolute';
	ajaxQueryDivElement.style.zIndex = 100;
	ajaxQueryDivElement.style.top = 0;
	ajaxQueryDivElement.style.left = 0;						
	ajaxQueryDivElement.style.padding = 5;
	ajaxQueryDivElement.style.color = 'black';
	ajaxQueryDivElement.style.border = '3px solid blue';

	ajaxQueryDivElement.innerHTML = pluginOutput.htmlCode.queryResult 
		+ pluginOutput.htmlCode.queryResult;

	ajaxQueryDivElement.onclick = function() {
		// TODO: Remove this error div when clicked
	};
	$('carto_form').appendChild(ajaxQueryDivElement);
*/
  }  
};

/*
 * Images plugin's Actions
 */
 
AjaxPlugins.Query.Actions = {};

AjaxPlugins.Query.Actions.perform = {
  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
	AjaxPlugins.Common.clearDhtmlDrawings();
	AjaxPlugins.Common.clearDhtmlStoredFeatures();
  }
};

AjaxPlugins.Query.Actions.clear = {
  buildPostRequest: function(argObject) {
  	return 'query_clear=' + '&' + AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  }
};