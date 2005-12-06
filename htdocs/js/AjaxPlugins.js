/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/*
 * Uses: Prototype-1.3.1.js - for $() and $F() functions
 * Uses: AjaxHelper.js - for common features (i.e. getHttpPostRequest)
 *
 * Used by: AjaxHandler.js
 */

// Initialises plugins on window load
AjaxHelper.addEvent(window, 'load', function() {
	AjaxPlugins.Common.init();
});

AjaxPlugins = {};

AjaxPlugins.Common = {

	/* Plugins' actions initialisation */
	init: function() {
		Logger.header('Initiating actions');
	
		AjaxPlugins.Location.Actions.Pan.init();
	},

	/* General plugins behaviour for before and after ajax calls */
	onBeforeAjaxCall: function(actionId) {
		xShow($('loadbarDiv'));
	},
	onAfterAjaxCall: function(actionId) {
		AjaxPlugins.Common.clearDhtmlDrawings();
		AjaxPlugins.Common.clearDhtmlStoredFeatures();
		AjaxPlugins.Common.clearDhtmlOutlineLabel();
		xHide($('loadbarDiv'));
	},

	
	/* Helper methods */
	clearDhtmlDrawings: function() {
		var dhtmlDrawingDivId = 'map_drawing';
	  	// remove drawed layers on mainmap object by deleting all childs of mapDrawing
	  	// TODO use the proper dhtmlAPI method, if exists...
	  	mapDrawingLayer = $(dhtmlDrawingDivId);
	  	var childNodesLength = mapDrawingLayer.childNodes.length;
		for (i=0; i<childNodesLength; i++) {
			mapDrawingLayer.removeChild(mapDrawingLayer.childNodes[0]);
		}	
	},
	
	clearDhtmlStoredFeatures: function() {
		// Clear the carto_form's selection_type and selection_coords hidden inputs
		// hidden input fields' value
		$('selection_type').value = null;
		$('selection_coords').value = null;
		$('features').value = null;	
	},
	
	clearDhtmlOutlineLabel: function() {
		xHide($('outlineLabelInputDiv'));
	}
}