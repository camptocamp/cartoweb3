<div id="id_recentering">
<fieldset><legend>{t}IdRecentering{/t}</legend> <br/>
<table width="100%">
<tr>
<td>
  {t}Layer to center on : {/t}
</td>
<td>
  <select name="id_recenter_layer" id="id_recenter_layer">
    {html_options values=$id_recenter_layers_id selected=$id_recenter_selected 
      output=$id_recenter_layers_label}
  </select>
</td>
</tr>
<tr>
<td>
{t}Comma separated id's : {/t}
</td>
<td> 
<input type="text" id="id_recenter_ids" name="id_recenter_ids" size="13"/> 
</td>
</tr>
</table>
</fieldset>
</div>