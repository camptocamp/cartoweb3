AjaxPlugins.Tables = {
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
	
	$('tables_result').innerHTML = pluginOutput.htmlCode.tableResult;
  }
};

/*
 * Tables plugin provides no action
 */
