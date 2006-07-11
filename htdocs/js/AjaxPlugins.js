/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/* The logic contained in this file defines:
 *  - Onload event attachement to trigger AJAX JS logic execution
 *  - Common plugin behaviour for:
 *     - onBeforeAjaxCall: logic called before an AJAX request is sent
 *     - onAfterAjaxCall: logic called after an AJAX response is received
 *       and after plugin have refreshed their UI
 *     - onCartoclientError: logic called in production profile
 *                           when Cartoclient returns an faillure
 *
 * Uses: prototype.js
 * Uses: AjaxHelper.js - for common features (i.e. getHttpPostRequest)
 *
 * Used by: AjaxHandler.js
 */

/*
 * Plugin initialisation
 */
AjaxHelper.addEvent(window, 'load', function() {

    // Fetches the url from the browser and trims the trailing '#' or querystring
    var href = window.location.href;
    lastChar = href.substr(href.length-1);
    if (lastChar == "#"){
        baseUrl = href.substring(0,href.length-1);
    } else {
        baseUrl = href;
    }
    qmark = href.indexOf("?");
    if (qmark >= 0){
        baseUrl = href.substring(0,qmark);
    }
    
    // Initialises plugins on window load    
    if (typeof(AjaxHandler) != 'undefined') {
        AjaxPlugins.Common.init();
        AjaxHandler.setBaseUrl(baseUrl);
    }
});

AjaxPlugins = {};

AjaxPlugins.Common = {

    // Map cursor-style backup
    mapCursorStyle: null,
    
    // Images.ajax.js plugin can set this to false, when it wants to
    // clear the waiting message itself (i.e. after raster is loaded)
    doClearWaitingMessage: true,

    // Number of actions pending (that haven't received a reply yet)
    pendingActions: 0,

    /* Plugins' actions initialisation */
    init: function() {
        Logger.header('Initiating actions');
        AjaxPlugins.Location.Actions.Pan.init();
    },

    /* General plugins behaviour, called before any ajax call */
    onBeforeAjaxCall: function(actionId) {
        this.setWaitingMessage()
        this.setWaitingCursor();
    },

    /* General plugins behaviour, called after any ajax call */
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
        var errorString = 'An error has occured. Press OK to reload this application';
        var doReload = confirm(errorString);
        if (doReload) {
            var sURL = unescape(window.location.pathname);
            window.location.replace( sURL );
        }
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
        Logger.note('Clearing DHTML drawings...');
        var dhtmlDrawingDivId = 'map_drawing';
        // remove drawed layers on mainmap object by deleting all childs of mapDrawing
        // TODO use the proper dhtmlAPI method, if exists...
        if ($(dhtmlDrawingDivId) == null) {
            Logger.warn('Element id' + dhtmlDrawingDivId + ' not found.' +
                        'Unable to clear DHTML drawings');
        } else {
            mapDrawingLayer = $(dhtmlDrawingDivId);
            var childNodesLength = mapDrawingLayer.childNodes.length;
            for (i=0; i<childNodesLength; i++) {
                mapDrawingLayer.removeChild(mapDrawingLayer.childNodes[0]);
            }
        }
    },
    
    clearDhtmlStoredFeatures: function() {
        Logger.note('Clearing stored features...');
        // Clear the carto_form's selection_type and selection_coords hidden inputs
        // hidden input fields' value
        if ($('selection_type') == null) {
            Logger.error('Unable to clear stored features: "selection_type" element not found');
        } else {
            $('selection_type').value = '';
        }

        if ($('selection_coords') == null) {
            Logger.error('Unable to clear stored features: "selection_coords" element not found');
        } else {
            $('selection_coords').value = '';
        }

        if ($('features') == null) {
            Logger.warn('Clear stored features: "features" elements not found');
        } else {
            $('features').value = '';            
        }

    },
    
    clearDhtmlOutlineLabel: function() {
        if (typeof hideLabel != 'undefined') {
            hideLabel();
        }
    }

}
