<div id="shortcut">
<fieldset>
<legend>{t}Shortcuts{/t}</legend>
<center>
<input type="hidden" id="shortcut_doit" name="shortcut_doit" value="0" />
<select name="shortcut_id" class="input_text" 
onchange="javascript:document.carto_form.shortcut_doit.value=1;FormItemSelected();">
{html_options values=$shortcut_values output=$shortcut_labels}
</select>
</center>
</fieldset>
</div>
