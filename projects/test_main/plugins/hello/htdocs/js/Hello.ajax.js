AjaxPlugins.Hello = {

  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
    
    $('hello_message').innerHTML = pluginOutput.htmlCode.hello_message;
  }
};

/*
 * Hello plugin actions
 */

AjaxPlugins.Hello.Actions = {};

AjaxPlugins.Hello.Actions.Change = {
  buildPostRequest: function(argObject) {
      return AjaxHandler.buildPostRequest();
  },
  buildGetRequest: function(argObject) {
      return '';
  },
  onBeforeAjaxCall: function(argObject) {
  },
  onAfterAjaxCall: function(argObject) {
  }
}