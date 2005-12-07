<p>
{t}Id recentering{/t}<br/>
{t}Layer to center on{/t}
<select name="id_recenter_layer" id="id_recenter_layer">
  {html_options values=$id_recenter_layers_id selected=$id_recenter_selected 
           output=$id_recenter_layers_label}
</select>

<br/>
{t}Comma separated id's{/t} 
<input type="text" id="id_recenter_ids" name="id_recenter_ids" />
<input type="submit" name="refresh" value="{t}refresh{/t}" class="form_button"
	onclick="{literal}if (typeof(AjaxHandler) != 'undefined')
		{AjaxHandler.doAction('Location.Recenter'); return false;}{/literal}" />
</p>
