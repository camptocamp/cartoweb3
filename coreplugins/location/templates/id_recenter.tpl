<p>
Id recentering <br/>

{t}Layer to center on{/t}
<select name="id_recenter_layer" id="id_recenter_layer">
  {html_options values=$id_recenter_layers_id selected=$id_recenter_selected 
           output=$id_recenter_layers_label}
</select>

<br/>
{t}Comma separated id's{/t} 
<input type="text" id="id_recenter_ids" name="id_recenter_ids" /> 
</p>
