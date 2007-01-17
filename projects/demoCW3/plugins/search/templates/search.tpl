<div id="search_airport_div">
<fieldset><legend>{t}Airport Search{/t}</legend> <br/>
<table width="100%">
<tr>
<td>
  {t}Country: {/t}
</td>
<td>
<div id="search_country_div"></div>
</td>
</tr>
<tr>
<td>
{t}Airport: {/t}
</td>
<td> 
<input type="text" id="search_name" name="search_name" size="13"/> 
</td>
</tr>
</table>
<p>
  <input type="submit" value="{t}Search{/t}" class="form_button"
           onclick="javascript: search('airports'); return false;" />
  <input type="hidden" name="search_config" id="search_config" />
<div id="search_results_div"></div>
</p>
</fieldset>
</div>
