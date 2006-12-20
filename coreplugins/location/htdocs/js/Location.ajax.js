AjaxPlugins.Location = {

    /* HTML element's id definitions */
    recenterScaleId: 'recenter_scale',
    recenterScaleDivId: 'recenter_scale_div',
    recenterIdsId: 'id_recenter_ids',
    shortcutIdId: 'shortcut_id',
    measureDistanceUnit: 'measure_distance_unit',
    measureSurfaceUnit: 'measure_surface_unit',
    locationInfoId: 'location_info',
    currentScaleId: 'currentScale',


    handleResponse: function(pluginOutput) {
        /* Plugin general behaviour */
        // Redefine the map extent in mainmap object
        Logger.trace('Updating dhtmlAPI\'s bbox and factor properties...');        
        var bboxMinX = pluginOutput.variables.bboxMinX;
        var bboxMinY = pluginOutput.variables.bboxMinY;
        var bboxMaxX = pluginOutput.variables.bboxMaxX;
        var bboxMaxY = pluginOutput.variables.bboxMaxY;
        // There is no "var" keyword before variable "factor" below: it is global.
        factor = pluginOutput.variables.factor;
        mainmap.setExtent(bboxMinX, bboxMinY, bboxMaxX, bboxMaxY);
        Logger.confirm('Done: new bbox (' + bboxMinX + ', ' + bboxMinY + ', '
                       + bboxMaxX + ', ' + bboxMaxY + ') , factor ('
                       + factor + ').');

        // Updates the location/scale info string
        AjaxHandler.updateDomElement(this.recenterScaleDivId, 'innerHTML',
                                     pluginOutput.htmlCode.scales);        

        AjaxHandler.updateDomElement(this.currentScaleId, 'innerHTML',
                                     '1:' + pluginOutput.variables.currentScale);        
        
        // Redraw the scale select
        AjaxHandler.updateDomElement(this.locationInfoId, 'innerHTML',
                                     pluginOutput.htmlCode.location_info);

        // Updates distance/surface measure tools units
        if ($('distanceValueLabel')) {
            var distanceLabel = $('distanceValueLabel').innerHTML;
            var distanceUnit = factor == 1000 ? 'km' : 'm';        
            mainmap.distanceUnits = '<span id="distanceValueLabel">'
                + distanceLabel + '</span>' + " %s " + distanceUnit;
            mainmap.getDisplay('map').clearLayer('distance');                        
            mainmap.distanceTag.style.display = "none";            
        }
        if ($('surfaceValueLabel')) {
            var surfaceLabel = $('surfaceValueLabel').innerHTML;
            var surfaceUnit = factor == 1000 ? 'km&sup2;' : 'm&sup2;';        
            mainmap.surfaceUnits = '<span id="surfaceValueLabel">' 
                + surfaceLabel + '</span>' + " %s " + surfaceUnit;            
            mainmap.getDisplay('map').clearLayer('surface');
            mainmap.surfaceTag.style.display = "none";           
        } 

        // Selects the first element of the shortcuts dropdown
        if ($(this.shortcutIdId)) {
            var optionsElm = $(this.shortcutIdId).getElementsByTagName('option');
            var firstOption = optionsElm[0];
            var timeoutFn = function() { firstOption.selected = true };
            setTimeout(timeoutFn, 1500);
        }
    }
};

/*
 * Location plugin's Actions
 */

AjaxPlugins.Location.Actions = {};

AjaxPlugins.Location.Actions.FullExtent = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    },
    onAfterAjaxCall: function(argObject) {
        document.carto_form.recenter_bbox.name = 'recenter_none';
    }
};

AjaxPlugins.Location.Actions.Recenter = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    },
    onAfterAjaxCall: function(argObject) {
        AjaxHandler.updateDomElement(AjaxPlugins.Location.recenterIdsId, 'value', '');
        document.carto_form.shortcut_doit.value=0;
    }
};

AjaxPlugins.Location.Actions.Zoom = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};

AjaxPlugins.Location.Actions.Pan = {
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
                'keymap.y=' + clickedPos.y + '&';
            break;
            default:
                postRequest += '';
            break;
        }
        return postRequest + AjaxHandler.buildPostRequest();
    },

    init: function() {
        this.initPanButtons();
        this.initKeymap();
        this.initMap();
    },
    initPanButtons: function() {
        // Attach an action on the click event of the pan buttons
        AjaxHandler.attachAction('pan_n', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_nw', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_w', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_sw', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_s', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_se', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_e', 'click', 'Location.Pan', {source: 'button'});
        AjaxHandler.attachAction('pan_ne', 'click', 'Location.Pan', {source: 'button'});    
    },
    initKeymap: function() {
        // Attach an action on the click event of the keymap div tag
        AjaxHandler.attachAction('keymap', 'click', 'Location.Pan', {source: 'keymap'});
    },
    initMap: function(timesExecuted) {
        if (typeof(timesExecuted) == 'undefined')
            timesExecuted = 0;
        // TODO: debug AjaxHandler.waitFor() and use it.
        if ($('map_raster_img') == undefined) {
            if (timesExecuted < 20)
                setTimeout(this.initMap, 500);
        } else {
            // The code below will execute only when map_raster_img exists

            // Attach an listener on the load event of the raster img tag
            // This will reposition the raster after on a pan by drag,
            //AjaxHelper.addEvent($('map_raster_img'), 'load', AjaxPlugins.Location.Actions.pan.placeRaster);
            
            // Save the map_rootLayer's initial position
            AjaxPlugins.Location.Actions.Pan.mapRootLayerTop = $('map_rootLayer').style.top;
            AjaxPlugins.Location.Actions.Pan.mapRootLayerLeft = $('map_rootLayer').style.left;
        }
    },

    mapRootLayerTop: 0,
    mapRootLayerLeft: 0,    
    placeRaster: function(e) {        
        // Reposition the map Raster layer on top left (when dragged).
        // TODO: Try parseInt(variable) to cast it, instead of *1
        rootPos = {
            top: parseInt(AjaxPlugins.Location.Actions.Pan.mapRootLayerTop.substring(0, AjaxPlugins.Location.Actions.Pan.mapRootLayerTop.length-2)),
            left: parseInt(AjaxPlugins.Location.Actions.Pan.mapRootLayerLeft.substring(0, AjaxPlugins.Location.Actions.Pan.mapRootLayerLeft.length-2))
        };
        xMoveTo($('map_rootLayer'), rootPos.left, rootPos.top);
        xMoveTo($('map_eventPad'), 0, 0);

        // Remove the clip style property, it will be reset on next drag
        // by dhtmlAPI.js
        xClip('map_rootLayer', 'none');
    }
};
