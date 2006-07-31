<h3>{t}Choropleth{/t}</h3>

<select name="geostatChoroplethLayer" 
    onchange="javascript:CartoWeb.trigger('Geostat.UpdateMenu', 'doSubmit()');">
<option value="def">{t}Select geographic entities...{/t}</option>
{html_options values=$geostatChoroplethLayersId 
output=$geostatChoroplethLayersDesc selected=$geostatChoroplethLayerSelected }
</select>

<select name="geostatChoroplethIndicator" id="geostatChoroplethIndicator"
    onchange="javascript:CartoWeb.trigger('Geostat.UpdateAll', 'doSubmit()');">
<option value="def">{t}Select indicator...{/t}</option>
{html_options values=$geostatChoroplethIndicatorsId 
output=$geostatChoroplethIndicatorsDesc 
selected=$geostatChoroplethIndicatorSelected}
</select>