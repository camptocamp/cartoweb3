// $Id$
AjaxPlugins.Geoloc = {

    handleResponse: function(pluginOutput) {
        // alert ('Return of geoloc ');
        //if (pluginOutput.variables.geo_x && pluginOutput.variables.geo_x ) {
            var geo_x = eval(pluginOutput.variables.geo_x);
            var geo_y = eval(pluginOutput.variables.geo_y);
            $('geoloc_values').value = 'X: ' + geo_x + '   |   Y: ' + geo_y;
            $('geoLocBox').style.display = 'block';
            $('geoloc_values').select();
        //}
    }
};

/*
 * Geoloc plugin's Actions
 */
 
AjaxPlugins.Geoloc.Actions = {};

AjaxPlugins.Geoloc.Actions.DoIt = {
    buildPostRequest: function(argObject) {
        return AjaxHandler.buildPostRequest();
    }  
};

