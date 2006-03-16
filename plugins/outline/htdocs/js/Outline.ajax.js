AjaxPlugins.Outline = {
  
    /* HTML element's id definitions */
    outlineFolderId: 'folder6',

    handleResponse: function(pluginOutput) {
        /* Plugin general behaviour */
        AjaxHandler.updateDomElement(this.outlineFolderId, 'innerHTML',
                                     pluginOutput.htmlCode.outline);
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
    }
};

AjaxPlugins.Outline.Actions.ChangeMode = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};

AjaxPlugins.Outline.Actions.Clear = {

    buildPostRequest: function(argObject) {
        return 'outline_clear=1' + '&' + AjaxHandler.buildPostRequest();
    }
};