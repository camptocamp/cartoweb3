AjaxPlugins.Location = {
  handleResponse: function(pluginOutput) {

    /* Plugin general behaviour */

	// Redefine the map extent in mainmap object
	bboxMinX = pluginOutput.variables.bboxMinX;
	bboxMinY = pluginOutput.variables.bboxMinY;
	bboxMaxX = pluginOutput.variables.bboxMaxX;
	bboxMaxY = pluginOutput.variables.bboxMaxY;
    mainmap.setExtent(bboxMinX, bboxMinY, bboxMaxX, bboxMaxY);

	// Clear the carto_form's selection_type and selection_coords hidden inputs
	// hidden input fields' value
	AjaxPlugins.Common.clearDhtmlStoredFeatures();
  }
};

/*
 * Locations plugin's Actions
 */

AjaxPlugins.Location.Actions = {};

AjaxPlugins.Location.Actions.zoom = {
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
	}
};

AjaxPlugins.Location.Actions.pan = {
	buildPostRequest: function(argObject) {
		var postRequest = '';
		switch (argObject.source) {
			case 'button':
				postRequest += AjaxHandler.buildRequestFrom(argObject.target) + '&';
			break;
			case 'keymap':
				// Find the click coords in the keymap img
				var clickedPos = AjaxHelper.getClickedPos(argObject.event);
				postRequest += 'keymap.x=' + clickedPos.x + '&' + 
				'keymap.y=' +clickedPos.y + '&';
			break;
			case 'map':
				postRequest += '';
			break;
		}
		return postRequest + AjaxHandler.buildPostRequest();
	},
	buildGetRequest: function(argObject) {
		return '';
	},
	onBeforeAjaxCall: function(argObject) {
	},
	onAfterAjaxCall: function(argObject) {
		AjaxPlugins.Common.clearDhtmlDrawings();
	},

	init: function() {
		this.initPanButtons();
		this.initKeymap();
		this.initMap();
	},
	initPanButtons: function() {
		// Attach an action on the click event of the pan buttons
		AjaxHandler.attachAction($('pan_n'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_nw'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_w'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_sw'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_s'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_se'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_e'), 'click', 'Location.pan', {source: 'button'});
		AjaxHandler.attachAction($('pan_ne'), 'click', 'Location.pan', {source: 'button'});	
	},
	initKeymap: function() {
		// Attach an action on the click event of the keymap div tag
		AjaxHandler.attachAction($('keymap'), 'click', 'Location.pan', {source: 'keymap'});
	},
	initMap: function(timesExecuted) {
		if (timesExecuted == undefined)
			timesExecuted = 0;
	    // TODO: debug AjaxHandler.waitFor() and use it.
		if ($('map_rootLayer') == undefined) {
			if (timesExecuted < 20)
				setTimeout(this.initMap, 500);
		} else {
			// Attach an listener on the load event of the raster img tag
			// This will reposition the raster after on a pan by drag,
			// but will wait for the image to be loaded before it get repositioned
			AjaxHelper.addEvent($('map_raster_img'), 'load', AjaxPlugins.Location.Actions.pan.placeRaster);
			
			// Save the map_rootLayer's initial position
			AjaxPlugins.Location.Actions.pan.mapRootLayerTop = $('map_rootLayer').style.top;
			AjaxPlugins.Location.Actions.pan.mapRootLayerLeft = $('map_rootLayer').style.left;
		}
	},

	mapRootLayerTop: 0,
	mapRootLayerLeft: 0,	
	placeRaster: function(e) {
		// Reposition the map Raster layer on top left (when dragged).
		// TODO: Try parseInt(variable) to cast it, instead of *1
		rootPos = {
			top: AjaxPlugins.Location.Actions.pan.mapRootLayerTop.substring(0, AjaxPlugins.Location.Actions.pan.mapRootLayerTop.length-2)*1,
			left: AjaxPlugins.Location.Actions.pan.mapRootLayerLeft.substring(0, AjaxPlugins.Location.Actions.pan.mapRootLayerLeft.length-2)*1
		};
		xMoveTo($('map_rootLayer'), rootPos.left, rootPos.top);
		      
		// Remove the clip style property, it will be reset on next drag
		// by dhtmlAPI.js
		xClip('map_rootLayer', 'none');
	}
};