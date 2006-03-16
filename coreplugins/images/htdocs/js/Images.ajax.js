AjaxPlugins.Images = {

    /* HTML element's id definitions */
    mainmapId: 'map_raster_img',
    keymapId: 'keymap',
    scalebarId: 'scalebar',
    
        
    handleResponse: function(pluginOutput, argObject) {    
        /* Plugin general behaviour */

        AjaxPlugins.Common.doClearWaitingMessage = false;
        
        // Updates mainmap image, preventing a shift
        var newRaster = new Image();
        // When the new image is loaded...
        AjaxHelper.addEvent(newRaster, 'load', function(e) {
            xHide(AjaxPlugins.Images.mainmapId);
            $(AjaxPlugins.Images.mainmapId).src = newRaster.src;
            AjaxPlugins.Location.Actions.Pan.placeRaster(e);
            setTimeout("xShow(AjaxPlugins.Images.mainmapId)", 1);
            AjaxPlugins.Common.clearWaitingMessage();
            AjaxPlugins.Common.doClearWaitingMessage = true;
        });
        newRaster.src = pluginOutput.variables.mainmap_path;
        
        // Updates keymap and scalebar images
        AjaxHandler.updateDomElement(this.keymapId, 'src', pluginOutput.variables.keymap_path);
        AjaxHandler.updateDomElement(this.scalebarId, 'src', pluginOutput.variables.scalebar_path);
        
        // Make the dhtml drawing pane visible again
        AjaxHandler.updateDomElement('map_drawing', 'style.display', 'block');
    }
};

/*
 * Images plugin provides no action (yet)
 */

AjaxPlugins.Images.Actions = {};