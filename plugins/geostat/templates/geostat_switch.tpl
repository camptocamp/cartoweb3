<input type="checkbox"
       name="{$geostatStatusName}"
       onclick="CartoWeb.trigger('Geostat.UpdateMap', 'doSubmit()')"
       {if $geostatStatusSelected}checked="checked"{/if} />
{t}Display geostatistics{/t}
</input>