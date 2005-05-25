<p><b>{t}Find path{/t}</b></p>
<p>{t}from{/t} <input type="text" id="routing_from" name="routing_from"
                value="{$routing_from}" size="8" maxlength="10" />
<br />{t}to{/t} <input type="text" id="routing_to" name="routing_to"
                 value="{$routing_to}" size="8" maxlength="10" />
<br />{t}options{/t} <select name="routing_options" id="routing_options">
{html_options values=$routing_options_values output=$routing_options_labels selected=$routing_options}
</select></p>
<p><input type="button" name="refresh" value="{t}routing_compute{/t}"
    class="form_button" onclick="javascript:FormItemSelected();" />
</p>