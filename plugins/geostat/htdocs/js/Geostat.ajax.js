AjaxPlugins.Geostat = {

    /* HTML element's id definitions */
    folderId: 'folder8',

    handleResponse: function(pluginOutput) {
        if (pluginOutput.variables.geostatFolderId) {
            this.folderId = pluginOutput.variables.geostatFolderId;
        }

        AjaxHandler.updateDomElement(this.folderId, 
            'innerHTML', pluginOutput.htmlCode.geostat);
    }

};

/*
 * Plugin actions
 */
AjaxPlugins.Geostat.Actions = {};

AjaxPlugins.Geostat.Actions.UpdateMenu =
AjaxPlugins.Geostat.Actions.UpdateMap =
AjaxPlugins.Geostat.Actions.UpdateAll = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    },
    onBeforeAjaxCall: function(argObject) {
    },
    onAfterAjaxCall: function(argObject) {
    }
}