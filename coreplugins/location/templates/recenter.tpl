<p>X <input type="text" id="recenter_x" name="recenter_x" size="6" />
Y <input type="text" id="recenter_y" name="recenter_y" size="6" /><br />
<input type="hidden" id="recenter_doit" name="recenter_doit" value="0" />
Scale <select name="recenter_scale" onChange="document.carto_form.recenter_doit.value = 1; document.carto_form.submit();">
        {html_options values=$recenter_scaleValues selected=$recenter_scale 
           output=$recenter_scaleLabels}
</select>
</p>
