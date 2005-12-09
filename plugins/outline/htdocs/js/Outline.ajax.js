AjaxPlugins.Outline = {
  
	/* HTML element's id definitions */
	outlineFolderId: 'folder6',

	handleResponse: function(pluginOutput) {
		/* Plugin general behaviour */
		AjaxHandler.updateDomElement(this.outlineFolderId, 'innerHTML',
			pluginOutput.htmlCode.outline);
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
		return 'outline_clear=1' + '&' + AjaxHandler.buildPostRequest();
	},
	buildGetRequest: function(argObject) {
		return '';
	},
	onBeforeAjaxCall: function(argObject) {
	},
	onAfterAjaxCall: function(argObject) {
	}
};