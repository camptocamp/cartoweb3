<h4>Choropleth dataset configuration</h4>

<div id="geostat_choropleth_univar">
<p>Number of data : {$geostatChoroplethNbVal} </p>
<p>Min. : {$geostatChoroplethMin} &emsp; Max. :{$geostatChoroplethMax}  </p>
<p>Mean : {$geostatChoroplethMean} </p>
<p>Std Div : {$geostatChoroplethStdDev} </p>
</div>

<p>
<label for="geostatChoroplethNbClasses">{t}Number of classes{/t}</label> 
<input name="geostatChoroplethNbClasses" type="text" size="3"
value="{$geostatChoroplethNbBins}" onblur="doSubmit()"/>
</p>

<p>
<label for="geostatChoroplethNbClasses">{t}Classification method{/t}</label> 
<select name="geostatChoroplethClassifMethod" onchange="doSubmit()">
{html_options options=$geostatChoroplethClassifMethod 
selected=$geostatChoroplethClassifMethodSelected}
</select>
</p>

<p>Class limits</p>
{foreach from=$geostatChoroplethBounds item=bound}
    <input name="geostatChoroplethBounds[]" type="text" size="5" 
    value="{$bound}"/><br/>
{/foreach}
<input type="submit" value="{t}Apply{/t}" onclick="javascript:return CartoWeb.trigger('Geostat.UpdateAll', 'doSubmit()');">