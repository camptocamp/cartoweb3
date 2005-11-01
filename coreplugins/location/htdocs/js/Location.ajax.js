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

AjaxPlugins.Location.Actions.mapPanByButton = {
  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildRequestFrom(argObject.target) + '&' + AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  },
  init: function() {
    // Initialise only if map_rootLayer element exists, else
    // wait a little while before trying again
    if ($('map_rootLayer') == undefined) {
      setTimeout(AjaxPlugins.Location.Actions.mapPanByButton.init, 500);
    } else {
	  // Attach an action on the click event of the pan buttons
	  AjaxHandler.attachAction($('pan_n'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_nw'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_w'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_sw'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_s'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_se'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_e'), 'click', 'Location.mapPanByButton');
	  AjaxHandler.attachAction($('pan_ne'), 'click', 'Location.mapPanByButton');
	}
  }
};


AjaxPlugins.Location.Actions.mapPanByDrag = {
  /* Predefined methods */
  buildPostRequest: function(argObject) {
  	return AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  },

  placeRaster: function(e) {
      // Reposition the Raster layer on top left (when dragged).
      // TODO: Try parseInt(variable) to cast it, instead of *1
      rootPos = {
      	top: AjaxPlugins.Images.rootLayerTop.substring(0, AjaxPlugins.Images.rootLayerTop.length-2)*1,
      	left: AjaxPlugins.Images.rootLayerLeft.substring(0, AjaxPlugins.Images.rootLayerLeft.length-2)*1
      };
      xMoveTo($('map_rootLayer'), rootPos.left, rootPos.top);
      
      // Remove the clip style property, it will be reset on next drag
      // by dhtmlAPI.js
      xClip('map_rootLayer', 'none');
  },
    
  init: function() {
    // Initialise only if map_rootLayer element exists, else
    // wait a little while before trying again
    if ($('map_rootLayer') == undefined) {
      setTimeout(AjaxPlugins.Location.Actions.mapPanByDrag.init, 500);
    } else {
      // Attach an listener on the load event of the raster img tag
      AjaxHelper.addEvent($('map_raster_img'), 'load', AjaxPlugins.Location.Actions.mapPanByDrag.placeRaster);

      // Save the map_rootLayer's initial position
      AjaxPlugins.Images.rootLayerTop = $('map_rootLayer').style.top;
      AjaxPlugins.Images.rootLayerLeft = $('map_rootLayer').style.left;
    }
  }
};


AjaxPlugins.Location.Actions.mapPanByKeymap = {
  buildPostRequest: function(argObject) {
  	// Find the click coords in the keymap img
  	var clickedPos = AjaxHelper.getClickedPos(argObject.event);
  	return 'keymap.x=' + clickedPos.x + '&' + 
			'keymap.y=' +clickedPos.y + '&' + 
  			AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
  	return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  },
  init: function() {
    // Initialise only if keymap element exists, else
    // wait a little while before trying again
    if ($('keymap') == undefined) {
      setTimeout(AjaxPlugins.Location.Actions.mapPanByKeymap.init, 500);
    } else {
      // Attach an action on the click event of the keymap div tag
	  AjaxHandler.attachAction($('keymap'), 'click', 'Location.mapPanByKeymap');
	}
  }
};


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




/* Initialises js Location plugin */
AjaxPlugins.Location.Actions.mapPanByDrag.init();
AjaxPlugins.Location.Actions.mapPanByButton.init();
AjaxPlugins.Location.Actions.mapPanByKeymap.init();

