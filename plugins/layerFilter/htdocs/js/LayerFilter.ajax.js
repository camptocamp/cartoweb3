AjaxPlugins.LayerFilter = { 
    handleResponse: function(pluginOutput) {
        if (pluginOutput.htmlCode.gui) {
            AjaxHandler.updateDomElement('layerFilter', 'innerHTML',
                pluginOutput.htmlCode.gui);
        }
    }   
};

/*
 * LayerFilter plugin's Actions
 */
 
AjaxPlugins.LayerFilter.Actions = {}; 

AjaxPlugins.LayerFilter.Actions.Apply = { 
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }   
};

AjaxPlugins.LayerFilter.Actions.Reset = { 
    buildPostRequest: function(argObject) {
        $('layerFilterReset').value = true;
        return AjaxHandler.buildPostRequest();
    }   
};
