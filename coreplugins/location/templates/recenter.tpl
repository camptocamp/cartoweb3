<p>{t}X{/t} <input type="text" id="recenter_x" name="recenter_x" size="6" 
maxlength="6" /> {t}Y{/t} <input type="text" id="recenter_y" name="recenter_y"
size="6" maxlength="6" /><br />
<input type="hidden" id="recenter_doit" name="recenter_doit" value="0" />
{t}Scale{/t} <select name="recenter_scale" id="recenter_scale" 
onchange="javascript:document.carto_form.recenter_doit.value=1;FormItemSelected();">
{html_options values=$recenter_scaleValues selected=$recenter_scale 
output=$recenter_scaleLabels}
</select></p>
