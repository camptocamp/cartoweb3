<p>
{t}Switches{/t}
<select name="switch_id" id="switch_id"
    onchange="javascript: CartoWeb.trigger('Layers.LayerDropDownChange', 'FormItemSelected()');">
{html_options values=$switch_values output=$switch_labels selected=$switch_id}
</select></p>
