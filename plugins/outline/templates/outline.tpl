<p>Outliner plugin:<br />
<input type="radio" name="outline_mask" value="no"{if $outline_mask_selected eq "no"} checked="checked"{/if} />{t}Draw{/t}<br />
<input type="radio" name="outline_mask" value="yes"{if $outline_mask_selected eq "yes"} checked="checked"{/if} />{t}Mask{/t}<br />
{t}Total area{/t}: {$outline_area}<br />
<input type="submit" name="outline_clear" value="outline_clear" class="form_button" /></p>
