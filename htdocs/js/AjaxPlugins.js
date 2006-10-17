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
    lastChar = href.substr(href.length - 1);
    if (lastChar == "#") {
        baseUrl = href.substring(0, href.length - 1);
    } else {
        baseUrl = href;
    }
    qmark = href.indexOf("?");
    if (qmark >= 0) {
        baseUrl = href.substring(0, qmark);
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

    // set this to false to disable the dhtml drawing clear
    doClearDhtmlDrawings: true,

    // Number of actions pending (that haven't received a reply yet)
    pendingActions: 0,

    /* Plugins' actions initialisation */
    init: function() {
        Logger.header('Initiating actions');
        AjaxPlugins.Location.Actions.Pan.init();
        if (typeof AjaxPlugins.ToolTips != 'undefined') {
            AjaxPlugins.ToolTips.init();
        }
    },

    /* General plugins behaviour, called before any ajax call */
    onBeforeAjaxCall: function(actionId) {
        this.setWaitingMessage()
        this.setWaitingCursor();
    },

    /* General plugins behaviour, called after any ajax call */
    onAfterAjaxCall: function(actionId) {
        if (this.doClearDhtmlDrawings) {
            this.clearDhtmlDrawings();
        }
        this.clearDhtmlStoredFeatures();
        this.clearDhtmlOutlineLabel();
        this.clearWaitingCursor();        
        if (this.doClearWaitingMessage) {
            this.clearWaitingMessage();
        }
    },

    onCartoclientError: function() {
        if (confirm('An error has occured. Press OK to reload this application')) {
            var sURL = unescape(window.location.pathname);
            window.location.replace(sURL);
        }
    },
    
    /* Helper methods */
    setWaitingCursor: function() {
        if (this.mapCursorStyle == null) {
            this.mapCursorStyle = $("map").style.cursor;
        }
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
        var timeoutFn = function() {
            xShow($('loadbarDiv'));
        }
        setTimeout(timeoutFn, 10);
    },
 
    clearWaitingMessage: function() {
        xHide($('loadbarDiv'));
    },
    
    clearDhtmlDrawings: function() {
        Logger.note('Clearing DHTML drawings...');
        // remove drawed layers on mainmap object by deleting all childs of mapDrawing
        // TODO use the proper dhtmlAPI method, if exists...
        try {
            $A($('map_drawing').childNodes).each(function(e) {e.parentNode.removeChild(e);});
        } catch (e) {
            Logger.warn('Unable to clear DHTML drawings: ' + e.message);
        }
    },
    
    clearDhtmlStoredFeatures: function() {
        Logger.note('Clearing stored features...');
        // Clear the carto_form's selection_type and selection_coords hidden inputs
        // hidden input fields' value
        try {
            $('selection_type').value = '';
        } catch (e) {
            Logger.error('Unable to clear selection type: ' + e.message);
        }
        
        try {
            $('selection_coords').value = '';
        } catch (e) {
            Logger.error('Unable to clear selection coords: ' + e.message);
        }
        
        try {
            $('features').value = '';
        } catch (e) {
            Logger.warn('Clear stored features: ' + e.message);
        }
       
    },
    
    clearDhtmlOutlineLabel: function() {
        if (typeof hideLabel != 'undefined') {
            hideLabel();
        }
    }
}
