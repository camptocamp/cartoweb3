AjaxPlugins.Layers = {

    /* HTML element's id definitions */
    layersFolderId: 'folder2',
    recenterScaleId: 'recenter_scale',  
  
    handleResponse: function(pluginOutput) {
        /* Plugin general behaviour */

        if (pluginOutput.variables.layersFolderId) {
            this.layersFolderId = pluginOutput.variables.layersFolderId;
        }

        if (pluginOutput.variables.switchTargetId) {
            this.switchTargetId = pluginOutput.variables.switchTargetId;
            /* Redraws layers HTML Code */        
            AjaxHandler.updateDomElement(this.layersFolderId, 'innerHTML',
                                         pluginOutput.htmlCode.layers);
            /* Redraws switch HTML Code */        
            AjaxHandler.updateDomElement(this.switchTargetId, 'innerHTML',
                                     pluginOutput.htmlCode.switches);

        } else {
            /* Redraws switch+layers HTML Code */        
            AjaxHandler.updateDomElement(this.layersFolderId, 'innerHTML',
                                     pluginOutput.htmlCode.switches
                                     + pluginOutput.htmlCode.layers);
        }
        
        /* Reopen open nodes */
        if ($(this.layersFolderId) != null) {
            startOpenNodes = pluginOutput.variables.startOpenNodes;
            // Uses layers.tpl + layers.js mecanism
            eval("var openNodes = new Array(" + startOpenNodes + ");");
            writeOpenNodes(true);
        }
    }  
};

/*
 * Images plugin's Actions
 */
 
AjaxPlugins.Layers.Actions = {};

AjaxPlugins.Layers.Actions.LayerShowHide = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};

AjaxPlugins.Layers.Actions.LayerDropDownChange = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};