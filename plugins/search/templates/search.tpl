<input type="text" id="search_name" name="search_name" /><br /> 
<input type="hidden" id="search_config" name="search_config" value="myconfig" />
<input type="submit" value="{t}Search{/t}"
    onclick="javascript: CartoWeb.trigger('Search.DoIt'); return false;" /><br />
<div id="search_results">
</div>
