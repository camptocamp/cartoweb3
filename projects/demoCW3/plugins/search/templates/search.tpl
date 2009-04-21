<div id="search_airport_div">
<fieldset><legend>{t}Search{/t}</legend> <br/>
<table width="100%">
<tr>
<td>
  {t}Country{/t}:
</td>
<td>
<div id="search_country_div"></div>
</td>
</tr>
<tr>
<td>
{t}Airport{/t}:
</td>
<td> 
<input type="text" id="search_name" name="search_name" size="13"/> 
</td>
</tr>
</table>


<p>
  <input type="submit" value="{t}Search{/t}" class="form_button"
           onclick="javascript: $('search_page').value = 1;
                                search('airports');
                                return false;" />
  <input type="hidden" id="search_config" name="search_config" />
  <input type="hidden" id="search_sort_column" name="search_sort_column" />
  <input type="hidden" id="search_sort_direction" name="search_sort_direction" value="asc" />
  <input type="hidden" id="search_number" name="search_number" />
  <input type="hidden" id="search_page" name="search_page" />
  <input type="hidden" id="search_selection" name="search_selection" />
 
<div id="search_results_div"></div>
</p>
<hr />
<table width="100%">
<tr>
<td>
  {t}Country{/t}:
</td>
<td>
<div id="search_country_district_div"></div>
</td>
</tr>
<tr>
<td>
  {t}Area{/t}:
</td>
<td>
<select name="search_area" id="search_area">
<option value="0-999999999"></option>
<option value="0-999">0-999 km2</option>
<option value="1000-9999">1000-9999 km2</option>
<option value="10000-99999">10000-99999 km2</option>
</select>
<input type="hidden" id="search_area_min" name="search_area_min" />
<input type="hidden" id="search_area_max" name="search_area_max" />
</td>
</tr>
</table>
<p>
  <input type="submit" value="{t}Add to Selection{/t}" class="form_button"
           onclick="javascript: $('search_selection').value = 'plus';
                                search('districts');
                                return false;" />
  <input type="submit" value="{t}Remove From Selection{/t}" class="form_button"
           onclick="javascript: $('search_selection').value = 'minus';
                                search('districts');
                                return false;" />
  <input type="submit" value="{t}Clear Hilight{/t}" class="form_button"
           onclick="javascript: $('query_clear').value = 1;
           CartoWeb.trigger('Query.Clear');
                                return false;" />
<input type="hidden" id="query_clear" name="query_clear" />
</p>

</fieldset>
</div>

