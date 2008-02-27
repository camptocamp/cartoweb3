AjaxPlugins.LinkIt = { 
    handleResponse: function(pluginOutput) {
        xGetElementById('linkItUrl').value = pluginOutput.variables.linkItUrl;
    }   
};
