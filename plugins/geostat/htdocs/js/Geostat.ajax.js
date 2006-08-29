AjaxPlugins.Geostat = {

    folderId: 'folder8',

    handleResponse: function(pluginOutput) {
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