<input type="checkbox"
       name="{$geostatStatusName}" id="{$geostatStatusName}"
       onclick="CartoWeb.trigger('Geostat.UpdateMap', 'doSubmit()')"
       {if $geostatStatusSelected}checked="checked"{/if} />
<label for="{$geostatStatusName}">{t}Display geostatistics{/t}</label>
