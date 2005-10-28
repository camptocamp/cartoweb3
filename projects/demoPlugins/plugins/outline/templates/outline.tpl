<div id="outline_mode">
<fieldset>
  <legend>{t}Mode{/t}</legend>
  <p style="text-align:center">
    <label><input type="radio" name="outline_mask" value="no"{if $outline_mask_selected eq "no"} checked="checked"{/if} />{t}Draw{/t}</label>&nbsp;&nbsp;&nbsp;
    <label><input type="radio" name="outline_mask" value="yes"{if $outline_mask_selected eq "yes"} checked="checked"{/if} />{t}Mask{/t}</label>
  </p>
  <center>
    <input type="submit" value="{t}Change Mode{/t}" class="form_button"/>
  </center>
</fieldset>
</div>
<div id="outline_area">
<fieldset>
  <legend>{t}Total area{/t}</legend>
  <p style="text-align:center">&nbsp;&nbsp;&nbsp;{$outline_area} {t}Km{/t}&sup2;</p>
</fieldset>
<table width="240px"><tr style="text-align:center;"><td><input type="submit" name="outline_clear" value="{t}Clear outline{/t}" class="form_button" /></td></tr></table>
</div>
