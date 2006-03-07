<p>
<input type="hidden" id="shortcut_doit" name="shortcut_doit" value="0" />
{t}Shortcuts{/t}
<select name="shortcut_id" id="shortcut_id" 
	onchange="javascript:{literal}
		document.carto_form.shortcut_doit.value=1;
		if (typeof(AjaxHandler) != 'undefined') {
			AjaxHandler.doAction('Location.Recenter');
			document.carto_form.shortcut_doit.value=0;
		}
		else FormItemSelected();
	{/literal}">
{html_options values=$shortcut_values output=$shortcut_labels}
</select></p>
