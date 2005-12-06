AjaxPlugins.Images = {

	/* HTML element's id definitions */
	mainmapId: 'map_raster_img',
	keymapId: 'keymap',
	scalebarId: 'scalebar',
	
		
	handleResponse: function(pluginOutput, argObject) {	
		/* Plugin general behaviour */
		
		// Updates mainmap image, preventing a shift
		if (argObject.actionName == 'Pan') {
			var newRaster = new Image();
			// When the new image is loaded...
			AjaxHelper.addEvent(newRaster, 'load', function(e) {
				xHide(AjaxPlugins.Images.mainmapId);
				$(AjaxPlugins.Images.mainmapId).src = newRaster.src;
				AjaxPlugins.Location.Actions.Pan.placeRaster(e);
				setTimeout("xShow(AjaxPlugins.Images.mainmapId)", 1);
			});
			newRaster.src = pluginOutput.variables.mainmap_path;
		} else {
			$(this.mainmapId).src = pluginOutput.variables.mainmap_path;
		}
		
		// Updates keymap and scalebar images
		AjaxHandler.updateDomElement(this.keymapId, 'src', pluginOutput.variables.keymap_path);
		AjaxHandler.updateDomElement(this.scalebarId, 'src', pluginOutput.variables.scalebar_path);
		
	}
};

/*
 * Images plugin provides no action (yet)
 */

AjaxPlugins.Images.Actions = {};