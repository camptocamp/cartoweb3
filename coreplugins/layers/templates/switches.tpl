<p>
{t}Switches{/t}
<select name="switch_id" id="switch_id"
	onchange="if (typeof(AjaxHandler) == 'undefined') FormItemSelected(); else AjaxHandler.doAction('Layers.LayerDropDownChange');">
{html_options values=$switch_values output=$switch_labels selected=$switch_id}
</select></p>
