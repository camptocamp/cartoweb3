AjaxPlugins.LayerReorder = {

    guiContainerId: 'layerReorderDiv',

    handleResponse: function(pluginOutput) {
        AjaxHandler.updateDomElement(this.guiContainerId, 'innerHTML',
            pluginOutput.htmlCode.gui);
    }  
};

/*
 * LayerReorder plugin's Actions
 */
 
AjaxPlugins.LayerReorder.Actions = {};

AjaxPlugins.LayerReorder.Actions.Apply = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};