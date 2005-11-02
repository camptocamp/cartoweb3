AjaxPlugins.Images = {

	rootLayerTop: 0,
	rootLayerleft: 0,
	  
	htmlHeight: 0,
	htmlWidth: 0,
	  
	
	handleResponse: function(pluginOutput) {
		/* Plugin general behaviour */
		    
		// Update src tag of img elements
		$('map_raster_img').src = pluginOutput.variables.mainmap_path;
		$('keymap').src = pluginOutput.variables.keymap_path;
		$('scalebar').src = pluginOutput.variables.scalebar_path;    
		
		this.htmlHeight = pluginOutput.variables.mainmap_height + 'px';
		this.htmlWidth = pluginOutput.variables.mainmap_width + 'px';
	}
};

/*
 * Images plugin's Actions
 */

AjaxPlugins.Images.Actions = {};

// This is bogous
// TODO: Debug this :-)
AjaxPlugins.Images.Actions.changeMapSize = {
	buildPostRequest: function(argObject) {
		return AjaxHandler.buildPostRequest();
	},
	buildGetRequest: function(argObject) {
		return '';
	},
	onBeforeAjaxCall: function(argObject) {
	},
	onAfterAjaxCall: function(argObject) {
		// Adjusts the map width and height
		htmlWidth = AjaxPlugins.Images.htmlWidth;
		htmlHeight = AjaxPlugins.Images.htmlHeight;

		$('map').style.width = htmlWidth;
		$('map').style.height = htmlHeight;
		$('map_rootLayer').style.width = htmlWidth;
		$('map_rootLayer').style.height = htmlHeight;
		$('map_raster').style.width = htmlWidth;
		$('map_raster').style.height = htmlHeight;
		$('map_drawing').style.width = htmlWidth;
		$('map_drawing').style.height = htmlHeight;
		$('map_eventPad').style.width = htmlWidth;
		$('map_eventPad').style.height = htmlHeight;
		$('loadbarDiv').style.width = htmlWidth;
		$('loadbarDiv').style.height = htmlHeight;

		// Remove the clip style property, it will be reset on next drag
		// by dhtmlAPI.js
		//xClip('map_rootLayer', 'none');
		//xClip('map_raster', 'none');
		
		// Adjust the clip for the eventPad
		//xClip('map_eventPad', 0, 430, 300, 0);
	}
};