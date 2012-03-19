AjaxPlugins.Outline = {
  
    /* HTML element's id definitions */
    outlineFolderId: 'folder6',
    outlineArea: 'outline_area',

    handleResponse: function(pluginOutput) {
        /* Plugin general behaviour */
        
        if (pluginOutput.variables.outlineFolderId) {
            this.outlineFolderId = pluginOutput.variables.outlineFolderId;
        }

        AjaxHandler.updateDomElement(this.outlineFolderId, 'innerHTML',
                                     pluginOutput.htmlCode.outline);

        // Also update outline_area in pure ajax mode
         if ( $(this.outlineArea) ) {
        	AjaxHandler.updateDomElement(this.outlineArea, 'innerHTML',
                                     pluginOutput.variables.outlineArea);
        }
        
	}  
};


/*
 * Outline plugin's Actions
 */
 
AjaxPlugins.Outline.Actions = {};

AjaxPlugins.Outline.Actions.AddFeature = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    },
    onAfterAjaxCall: function(argObject) {
        Logger.note('Clearing outline dhtml layers...');
        mainmap.getDisplay('map').clearLayer('outline_poly');
        mainmap.getDisplay('map').clearLayer('outline_line');
        mainmap.getDisplay('map').clearLayer('outline_rectangle');
        mainmap.getDisplay('map').clearLayer('outline_point');
        mainmap.getDisplay('map').clearLayer('outline_circle');
    }
};

AjaxPlugins.Outline.Actions.ChangeMode = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};

AjaxPlugins.Outline.Actions.Clear = {

    buildPostRequest: function(argObject) {
        return 'outline_circle_radius=0' + '&' +'outline_clear=1' + '&' + AjaxHandler.buildPostRequest();
    }
};