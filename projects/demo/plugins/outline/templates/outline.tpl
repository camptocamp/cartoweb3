<div id="outline_plugin">
  <div id="outline_mode">
  <fieldset>
  <legend>{t}Mode{/t}</legend>
  <p><label><input type="radio" name="outline_mask" value="no"{if $outline_mask_selected eq "no"} checked="checked"{/if} onclick="AjaxHandler.doAction('Outline.ChangeMode');" />{t}Draw{/t}</label>&nbsp;&nbsp;&nbsp;
  <label><input type="radio" name="outline_mask" value="yes"{if $outline_mask_selected eq "yes"} checked="checked"{/if} onclick="AjaxHandler.doAction('Outline.ChangeMode');" />{t}Mask{/t}</label>
  </p>
  <center>
  <input type="submit" value="{t}Change Mode{/t}" class="form_button"/>
  </center>
  </fieldset>
  </div>
  <div id="outline_area">
  <fieldset>
  <legend>{t}Total area{/t}</legend>
  <p>&nbsp;&nbsp;&nbsp;{$outline_area} {t}Km{/t}&sup2;</p>
  </fieldset>
  <p class="mini"><input type="submit" name="outline_clear" value="{t}Clear outline{/t}" class="form_button" onclick="AjaxHandler.doAction('Outline.Clear', {literal}{target: this}{/literal});return false;" /></p>
  </div>
</div>