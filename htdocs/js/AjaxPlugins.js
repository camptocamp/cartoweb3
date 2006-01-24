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
  frameSourceUrl = window.location.href;
  lastChar = frameSourceUrl.substr(frameSourceUrl.length-1);
  if (lastChar == "#"){
    baseUrl = frameSourceUrl.substring(0,frameSourceUrl.length-1);
  } else {
    baseUrl = frameSourceUrl;
  }
  qmark = frameSourceUrl.indexOf("?");
  if (qmark >= 0){
    baseUrl = frameSourceUrl.substring(0,qmark);
  }

  if (typeof(AjaxHandler) != 'undefined') {
	AjaxPlugins.Common.init();
	AjaxHandler.setBaseUrl(baseUrl);
  }
});

AjaxPlugins = {};

AjaxPlugins.Common = {

	mapCursorStyle: null,
	doClearWaitingMessage: true,

	/* Plugins' actions initialisation */
	init: function() {
		Logger.header('Initiating actions');	
		AjaxPlugins.Location.Actions.Pan.init();
	},

	/* General plugins behaviour for before and after ajax calls */
	onBeforeAjaxCall: function(actionId) {
		this.setWaitingMessage()
		this.setWaitingCursor();
	},
	onAfterAjaxCall: function(actionId) {
		this.clearDhtmlDrawings();
		this.clearDhtmlStoredFeatures();
		this.clearDhtmlOutlineLabel();
		this.clearWaitingCursor();		
		if (this.doClearWaitingMessage) {
			this.clearWaitingMessage();
		}
	},
	onCartoclientError: function() {
		alert('User error message');
	},
	
	/* Helper methods */
	setWaitingCursor: function() {
		if (this.mapCursorStyle == null)
			this.mapCursorStyle = $("map").style.cursor;
		$("map").style.cursor = "progress";
		document.getElementsByTagName("body")[0].style.cursor = "progress";
	},
	clearWaitingCursor: function() {
		document.getElementsByTagName("body")[0].style.cursor = "default";
		if (this.mapCursorStyle != null) {
			$("map").style.cursor = this.mapCursorStyle;
			this.mapCursorStyle = null;
		}
	},
	
	setWaitingMessage: function() {
		xShow($('loadbarDiv'));
	},	
	clearWaitingMessage: function() {
		xHide($('loadbarDiv'));
	},

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
		if (typeof(hideLabel != 'undefined')) {
			hideLabel();
		}
	}

}

/*
 * This is a pseudo plugin, used to retrieve Cartoweb general informations
 */
AjaxPlugins.Cartoweb = {
	handleResponse: function(pluginOutput) {
		// Shows developer and user messages in jsTrace debugger window
		if (pluginOutput.htmlCode.developerMessages != '')
			Logger.note ('Developer messages: <br />' + pluginOutput.htmlCode.developerMessages);		
		if (pluginOutput.htmlCode.userMessages != '')
			Logger.note ('User messages: <br />' + pluginOutput.htmlCode.userMessages);
	}
};
