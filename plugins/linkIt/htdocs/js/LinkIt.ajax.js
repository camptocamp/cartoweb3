AjaxPlugins.LinkIt = { 
    handleResponse: function(pluginOutput) {
        xGetElementById('linkItUrl').value = pluginOutput.variables.linkItUrl;
        if (pluginOutput.variables.isUrlTooLong) {
            xGetElementById('linkItUrlAlert').style.display = 'block';
        } else {
            xGetElementById('linkItUrlAlert').style.display = 'none';
        }
    }   
};
