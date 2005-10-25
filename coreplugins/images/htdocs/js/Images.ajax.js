AjaxPlugins.Images = {

  rootLayerTop: 0,
  rootLayerleft: 0,

  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
    
	// Update src tag of img elements
	$('map_raster_img').src = pluginOutput.variables.mainmap_path;
	$('keymap').src = pluginOutput.variables.keymap_path;
	$('scalebar').src = pluginOutput.variables.scalebar_path;    
  }
};

