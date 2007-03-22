AjaxPlugins.ExportDxf = {

    handleResponse: function(pluginOutput) {
        /* Plugin general behaviour */

        AjaxHandler.updateDomElement(pluginOutput.variables.exportDxfContainerName, 'innerHTML',
                                     pluginOutput.variables.exportDxf);

        
    }  
};