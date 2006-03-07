<div id="recenter">
<fieldset><legend>{t}Recenter{/t}</legend>
<label>{t}X:{/t}</label>&nbsp;
<input type="text" id="recenter_x" name="recenter_x" size="6" maxlength="6" />
&nbsp;&nbsp;&nbsp;
<label>{t}Y:{/t}</label>&nbsp;
<input type="text" id="recenter_y" name="recenter_y" size="7" maxlength="7" />
<br />
<input type="submit" name="refresh" value="{t}Recenter{/t}" class="form_button" onclick="AjaxHandler.doAction('Location.Recenter');return false;" />
</fieldset>
</div>
