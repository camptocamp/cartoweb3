/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/*
 * Uses: Prototype-1.3.1.js - for $() and $F() functions
 * Uses: AjaxHelper.js - for common features (i.e. getHttpPostRequest)
 *
 * Used by: AjaxHandler.js
 */

/*
 * Initialises plugins on window load
 */
AjaxHelper.addEvent(window, 'load', function() {
	AjaxPlugins.Common.init();
});

AjaxPlugins = {};

AjaxPlugins.Common = {

	mapCursorStyle: '',

	/* Plugins' actions initialisation */
	init: function() {
		Logger.header('Initiating actions');	
		AjaxPlugins.Common.setBaseUrl();
		AjaxPlugins.Location.Actions.Pan.init();
	},

	/* 
	 * Gets rid of the substring following the first special char
	 * present in specialChars array
	 */
	setBaseUrl: function() {
		specialChars = Array('?', '#'); // Defines what a special char is
		pageUrl = window.location.href;
		specialCharFirstIndex = pageUrl.length;

		// This loop assign the index of the first special char occurence
		// to the specialCharFirstIndex variable
		for (i=0 ; i < specialChars.length ; i++) {
			specialChar = specialChars[i];
			specialCharIndex = pageUrl.indexOf(specialChar);
			if (specialCharIndex != -1 && specialCharIndex < specialCharFirstIndex) {
				specialCharFirstIndex = pageUrl.indexOf(specialChar);
			}
		}		
		// Uses specialCharFirstIndex variable value to trim the
		// pageUrl string
		baseUrl = pageUrl.substring(0,specialCharFirstIndex);
		Logger.note('Setting AjaxHandler.baseUrl to \''+baseUrl+'\'');
		AjaxHandler.setBaseUrl(baseUrl);
	},
	
	/* General plugins behaviour for before and after ajax calls */
	onBeforeAjaxCall: function(actionId) {
		if (this.mapCursorStyle == null)
			this.mapCursorStyle = $("map").style.cursor;
		$("map").style.cursor = "progress";
		document.getElementsByTagName("body")[0].style.cursor = "progress";
		//xShow($('loadbarDiv'));
	},
	onAfterAjaxCall: function(actionId) {
		AjaxPlugins.Common.clearDhtmlDrawings();
		AjaxPlugins.Common.clearDhtmlStoredFeatures();
		AjaxPlugins.Common.clearDhtmlOutlineLabel();
		
		document.getElementsByTagName("body")[0].style.cursor = "default";
		if (this.mapCursorStyle != null) {
			$("map").style.cursor = this.mapCursorStyle;
			this.mapCursorStyle = null;
		}
		//xHide($('loadbarDiv'));
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
		$('selection_type').value = '';
		$('selection_coords').value = '';
		$('features').value = '';
	},
	
	clearDhtmlOutlineLabel: function() {
		xHide($('outlineLabelInputDiv'));
	}
}

/*
 * This is a pseudo plugin, used to retrieve Cartoweb general informations
 */
AjaxPlugins.Cartoweb = {
	handleResponse: function(pluginOutput) {
		// Shows developer and user messages in jsTrace debugger window
		if (pluginOutput.htmlCode.developerMessages != '') {
            Logger.note ('Developer messages: <br />' + pluginOutput.htmlCode.developerMessages);
        }
		if (pluginOutput.htmlCode.userMessages != '') {
            Logger.note ('User messages: <br />' + pluginOutput.htmlCode.userMessages);
        }
	}
}
