AjaxPlugins.StatsReports = {

    handleResponse: function(pluginOutput) {
        
          if (pluginOutput.htmlCode.report) {
            $('stats_report_block').innerHTML = pluginOutput.htmlCode.report;        
          }
          if (pluginOutput.htmlCode.periodtype) {
            $('stats_periodtype_block').innerHTML = pluginOutput.htmlCode.periodtype;
            if (pluginOutput.variables.periodtypecount == 1) {
                changePeriodType();    
            }
          }
          if (pluginOutput.htmlCode.display) {
            $('stats_display_block').innerHTML = pluginOutput.htmlCode.display;
            if (pluginOutput.variables.displaycount == 1) {
                changeDisplay();
            }
          }
          if (pluginOutput.htmlCode.options) {
            $('stats_options_block').innerHTML = pluginOutput.htmlCode.options;
          }
          if (pluginOutput.htmlCode.result) {
              
            toggleGeneratedResult('show');              
            $('generated_result').innerHTML = pluginOutput.htmlCode.result;              
          } else if (pluginOutput.variables.resulttype == 'map') {
            toggleGeneratedResult('hide');              
          }
          if (pluginOutput.htmlCode.legend) {
            $('stats_legend_block').innerHTML = pluginOutput.htmlCode.legend;      
        }
    }  
};


/*
 * Actions
 */

AjaxPlugins.StatsReports.Actions = {}

AjaxPlugins.StatsReports.Actions.RefreshData = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
}

AjaxPlugins.StatsReports.Actions.RefreshReport = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
}

AjaxPlugins.StatsReports.Actions.RefreshPeriodType = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
}

AjaxPlugins.StatsReports.Actions.RefreshOptions = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
}

AjaxPlugins.StatsReports.Actions.ComputeReport = {

    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }
}

function resetReport() {
    if ($('stats_report')) {
        $('stats_report').value = '_empty';
    }
    resetPeriodType();
}

function changeData() {

    resetReport();
    if ($('stats_data').value == '_empty') {
        $('stats_report_block').hide();
    } else {
        $('stats_report_block').show();
        CartoWeb.trigger('StatsReports.RefreshData');    
    }
}

function resetPeriodType() {
    if ($('stats_periodtype')) {
        $('stats_periodtype').value = '_empty';
    }
    resetDisplay();
}

function changeReport() {

    resetPeriodType();
    if ($('stats_report').value == '_empty') {
        $('stats_periodtype_block').hide();
    } else {
        $('stats_periodtype_block').show();
        CartoWeb.trigger('StatsReports.RefreshReport');    
    }
}

function resetDisplay() {
    if ($('stats_display')) {
        $('stats_display').value = '_empty';
    }
    resetOptions();
}

function changePeriodType() {

    resetDisplay();
    if ($('stats_periodtype').value == '_empty') {
        $('stats_display_block').hide();
    } else {
        $('stats_display_block').show();
        CartoWeb.trigger('StatsReports.RefreshPeriodType');    
    }
}

function resetOptions() {

    $('stats_legend_block').innerHTML = '';    
}

function changeDisplay() {
    
    resetOptions();
    if ($('stats_display').value == '_empty') {
        $('stats_options_block').hide();
    } else {
        $('stats_options_block').show();
        CartoWeb.trigger('StatsReports.RefreshOptions');    
    }    
}
