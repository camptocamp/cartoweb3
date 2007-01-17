AjaxPlugins.Search = {
  
    handleResponse: function(pluginOutput) {
    
        if (pluginOutput.htmlCode.myconfig)
            $('search_results').innerHTML = pluginOutput.htmlCode.myconfig;        
    }  
};


/*
 * Search plugin's Actions
 */
 
AjaxPlugins.Search.Actions = {};

AjaxPlugins.Search.Actions.DoIt = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};