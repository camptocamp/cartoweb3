AjaxPlugins.Tables = {

  tablesResultId: 'tables_result',
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */
	
	AjaxHandler.updateDomElement(this.tablesResultId, 'innerHTML', pluginOutput.htmlCode.tableResult);
  }
};

/*
 * Tables plugin provides no action
 */
