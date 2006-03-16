AjaxPlugins.Query = {
  
    handleResponse: function(pluginOutput) {
        /* Plugin general behaviour */        
        
        // Nothing has to be done here: Tables manages to display the results
        // Note: although empty, this method is implemented to prevent
        //       a warning in the debugger
    }  
};

/*
 * Images plugin's Actions
 */
 
AjaxPlugins.Query.Actions = {};

AjaxPlugins.Query.Actions.Perform = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
};

AjaxPlugins.Query.Actions.Clear = {
buildPostRequest: function(argObject) {
        return 'query_clear=query_clear' + '&';
    },
    onAfterAjaxCall: function(argObject) {
        AjaxHandler.updateDomElement(AjaxPlugins.Tables.tablesResultId,
                                     'innerHTML', '');
    }
};