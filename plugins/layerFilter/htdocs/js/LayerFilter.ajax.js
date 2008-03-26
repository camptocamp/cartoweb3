AjaxPlugins.LayerFilter = { 
    handleResponse: function(pluginOutput) {}   
};

/*
 *  * LayerFilter plugin's Actions
 *   */
 
AjaxPlugins.LayerFilter.Actions = {}; 

AjaxPlugins.LayerFilter.Actions.Apply = { 
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }   
};
