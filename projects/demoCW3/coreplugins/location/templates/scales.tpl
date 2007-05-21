<label>{t}Scale :{/t}</label>
{if $recenter_noscales}
<input type="hidden" id="recenter_doit" name="recenter_doit" value="1" />
<input type="text" id="input_text" name="recenter_scale" size="10" 
maxlength="10" />
{else}
<input type="hidden" id="recenter_doit" name="recenter_doit" value="0" />
<select name="recenter_scale" id="recenter_scale"
onchange="javascript:document.carto_form.recenter_doit.value=1;
          CartoWeb.trigger('Location.Zoom', 'FormItemSelected()');">
{html_options values=$recenter_scaleValues selected=$recenter_scale 
output=$recenter_scaleLabels}
</select>
{if $freescale}
{t}Custom_Scale{/t}&nbsp;&nbsp;1: <input id="custom_scale" type="text" onkeypress="javascript: setCustomScale(event)" size="10" />  
{/if}
{/if}