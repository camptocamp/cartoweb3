AjaxPlugins.Tables = {

  tablesResultId: 'tables_result',
  tablesResultContainer: 'tables_result_container',
  
  handleResponse: function(pluginOutput) {
    /* Plugin general behaviour */

    var resultHtml = pluginOutput.htmlCode.tableResult;
    var resultContainer = $(this.tablesResultContainer);
    
    AjaxHandler.updateDomElement(this.tablesResultId, 'innerHTML', resultHtml);
    
    if (resultContainer != null && resultHtml == '') {
        Element.hide(resultContainer);
    } else {
        Element.show(resultContainer);        
    }
  }
};

/*
 * Tables plugin provides no action
 */
