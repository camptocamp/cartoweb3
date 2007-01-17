AjaxPlugins.Search = {
  
    handleResponse: function(pluginOutput) {
        
        if (pluginOutput.htmlCode.countries)
            $('search_country_div').innerHTML = pluginOutput.htmlCode.countries;

        if (pluginOutput.htmlCode.airports)
            $('search_results_div').innerHTML = pluginOutput.htmlCode.airports;
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

function search(config) {
    
    $('search_config').value = config;
    CartoWeb.trigger('Search.DoIt');
}

function initializeCountry() {

    search('countries');
}

Event.observe(window, 'load', initializeCountry, true);

function recenterAirport(airportId) {

    $('id_recenter_layer').value = 'airport'; 
    $('id_recenter_ids').value = airportId;
    
    CartoWeb.trigger('Location.Recenter');
}
