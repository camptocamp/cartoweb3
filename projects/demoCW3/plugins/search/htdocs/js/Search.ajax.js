AjaxPlugins.Search = {
  
    handleResponse: function(pluginOutput) {
        
        if (pluginOutput.htmlCode.countries)
            $('search_country_div').innerHTML = pluginOutput.htmlCode.countries;

        if (pluginOutput.htmlCode.airports)
            $('search_results_div').innerHTML = pluginOutput.htmlCode.airports;
            
        if (pluginOutput.htmlCode.country_districts)
            $('search_country_district_div').innerHTML = pluginOutput.htmlCode.country_districts;
            
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
    
    $('query_clear').value = 0;
    $('search_config').value = config;
    if (config == 'airports') {
        $('search_number').value = 10;
    }
    if (config == 'districts') {
        area = $('search_area').value.split('-');
        $('search_area_min').value = area[0];
        $('search_area_max').value = area[1];
    }
    
    CartoWeb.trigger('Search.DoIt');
}

function initializeCountry() {

    search('countries');
    search('country_districts');
}

Event.observe(window, 'load', initializeCountry, true);

function recenterAirport(airportId) {

    $('id_recenter_layer').value = 'airport'; 
    $('id_recenter_ids').value = airportId;
    
    CartoWeb.trigger('Location.Recenter');
}
