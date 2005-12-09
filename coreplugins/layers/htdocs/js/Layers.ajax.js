AjaxPlugins.Layers = {

	/* HTML element's id definitions */
	layersFolderId: 'folder2',
	recenterScaleId: 'recenter_scale',  
  
	handleResponse: function(pluginOutput) {
		/* Plugin general behaviour */
		
		/* Redraws layers HTML Code */		
		AjaxHandler.updateDomElement(this.layersFolderId, 'innerHTML',
			pluginOutput.htmlCode.switches+pluginOutput.htmlCode.layers);
		
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

AjaxPlugins.Layers.Actions.LayerShowHide = {
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

AjaxPlugins.Layers.Actions.LayerDropDownChange = {
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