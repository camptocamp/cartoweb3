AjaxPlugins.Hello = {

  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
    
    $('hello_message').innerHTML = pluginOutput.htmlCode.hello_message;
    //$('hello_input').value = '';
  }
};

/*
 * Locations plugin's Actions
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
  },
  init: function() {
    // Initialise only if hello_input element exists, else
    // wait a little while before trying again
    if ($('hello_input') == undefined) {
      setTimeout(AjaxPlugins.Hello.Actions.Change.init, 500);
    } else {
	  AjaxHandler.attachAction($('hello_input'), 'keyup', 'Hello.change');
	}
  }
}

/* Initialises js Location plugin */
AjaxPlugins.Hello.Actions.Change.init();