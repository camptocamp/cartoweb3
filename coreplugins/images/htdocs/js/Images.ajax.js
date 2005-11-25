AjaxPlugins.Images = {

	htmlHeight: 0,
	htmlWidth: 0,
	  
	
	handleResponse: function(pluginOutput, argObject) {
		/* Plugin general behaviour */
		    
		// Update src tag of img elements

		if (argObject.actionName == 'Pan') {
			var newRaster = new Image();
			AjaxHelper.addEvent(newRaster, 'load', function(e) {
				xHide('map_raster_img');
				$('map_raster_img').src = newRaster.src;
				AjaxPlugins.Location.Actions.Pan.placeRaster(e);
				setTimeout("xShow('map_raster_img')", 1);
			});
			newRaster.src = pluginOutput.variables.mainmap_path;
		} else {
			$('map_raster_img').src = pluginOutput.variables.mainmap_path;
		}
		$('keymap').src = pluginOutput.variables.keymap_path;
		$('scalebar').src = pluginOutput.variables.scalebar_path;    
		
		this.htmlHeight = pluginOutput.variables.mainmap_height + 'px';
		this.htmlWidth = pluginOutput.variables.mainmap_width + 'px';
	}
};

/*
 * Images plugin provides no action (yet)
 */

AjaxPlugins.Images.Actions = {};