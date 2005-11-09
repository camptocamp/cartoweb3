<p>{t}Outliner plugin:{/t}<br />
<label><input type="radio" name="outline_mask" value="no"{if $outline_mask_selected eq "no"} checked="checked"{/if} />{t}Draw{/t}</label><br />
<label><input type="radio" name="outline_mask" value="yes"{if $outline_mask_selected eq "yes"} checked="checked"{/if} />{t}Mask{/t}</label><br />
{t}Total area{/t}: {$outline_area}<br />
<input type="submit" name="outline_clear" value="{t}outline_clear{/t}" class="form_button" /></p>
