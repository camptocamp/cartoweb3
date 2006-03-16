<p>{t}Scale{/t} 
{if $recenter_noscales}
<input type="hidden" id="recenter_doit" name="recenter_doit" value="1" />
<input type="text" id="recenter_scale" name="recenter_scale" size="10" 
maxlength="10" />
{else}
<input type="hidden" id="recenter_doit" name="recenter_doit" value="0" />
<select name="recenter_scale" id="recenter_scale" 
onchange="javascript:document.carto_form.recenter_doit.value=1;
          CartoWeb.trigger('Location.Zoom', 'FormItemSelected()');">
{html_options values=$recenter_scaleValues selected=$recenter_scale 
output=$recenter_scaleLabels}
</select>
{/if}
</p>
