<div id="shortcut">
<fieldset>
<legend>{t}Shortcuts{/t}</legend>
<input type="hidden" id="shortcut_doit" name="shortcut_doit" value="0" />
<select name="shortcut_id" id="shortcut_id" 
onchange="javascript:document.carto_form.shortcut_doit.value=1;
	AjaxHandler.doAction('Location.Recenter');
	//FormItemSelected();
" />
{html_options values=$shortcut_values output=$shortcut_labels}
</select>
</fieldset>
</div>
