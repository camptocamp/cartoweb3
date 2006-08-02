<h4>{t}Choropleth representation configuration{/t}</h4>

<p>
<label for="geostatChoroplethColorMethod">{t}Coloring Method{/t}</label>
<select name="geostatChoroplethColorMethod"
        onchange="javascript:CartoWeb.trigger('Geostat.UpdateAll', 'doSubmit()');">
{html_options options=$geostatChoroplethColorMethod 
selected=$geostatChoroplethColorMethodSelected}
</select>
</p>

<p>
<label>{t}Initialization Colors{/t}</label>
<br />
<span class="geostat_choropleth_color_box" 
style="background-color:{$geostatChoroplethColorAValue};" 
id="geostatChoroplethColorA_d" 
onclick="javascript:toolPicker('1','geostatChoroplethColorA')">
&nbsp;&nbsp;&nbsp;<input type="hidden" id="geostatChoroplethColorA" 
name="geostatChoroplethColorA"  value="{$geostatChoroplethColorAValue}" />
</span>
&nbsp;
<span class="geostat_choropleth_color_box" 
style="background-color:{$geostatChoroplethColorBValue};" 
id="geostatChoroplethColorB_d" 
onclick="javascript:toolPicker('1','geostatChoroplethColorB')">
&nbsp;&nbsp;&nbsp;<input type="hidden" 
id="geostatChoroplethColorB" name="geostatChoroplethColorB" 
value="{$geostatChoroplethColorBValue}" />
</span>
&nbsp;
</p>

<label>{t}Classes Color{/t}</label>
<table align="center">
{foreach from=$geostatChoroplethLabels item=label key=labelId}
    <tr>
      <td>
        <span class="geostat_choropleth_color_box" 
          style="background-color:{$geostatChoroplethClassesColor[$labelId]};" 
          id="geostatChoroplethClassColor{$labelId}_d" 
          onclick="javascript:toolPicker('1',
          'geostatChoroplethClassColor{$labelId}')">
          &nbsp;&nbsp;&nbsp;&nbsp;<input type="hidden" 
          id="geostatChoroplethClassColor{$labelId}" 
          name="geostatChoroplethClassColor{$labelId}"  
          value="{$geostatChoroplethClassesColor[$labelId]}" />
        </span>
      </td>
      <td>{$label}</td>
    </p>
{/foreach}
</table>
</p>

<input type="submit" value="{t}Apply{/t}" onclick="javascript:return CartoWeb.trigger('Geostat.UpdateAll', 'doSubmit()');">