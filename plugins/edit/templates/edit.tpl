
<u>Edition plugin:</u><br />
{if $edit_allowed|default:''}

{t}Choose layer{/t}<br />
<select name="edit_layer" onchange="javascript:FormItemSelected();">
<option value="0">layer to edit</option>

{html_options values=$edit_layers_id output=$edit_layers_label
    selected=$edit_layer_selected}
</select><br />
{t}Features: {/t}<span id="features_num"></span><br />
{t}Inserted: {/t}<span id="inserted_features_num"></span><br />
{t}Modified: {/t}<span id="modified_features_num"></span><br />
{t}Deleted: {/t}<span id="deleted_features_num"></span><br />
<br />
<div id="validateEdit">
  <input type="hidden" name="edit_validate_all" value="0"/>
  <input type="button" id="validate_all" value="{t}Validate{/t}" onclick="myform['edit_validate_all'].value = '1';if (storeFeatures()) doSubmit();" class="form_button" />
</div>
<input name="edit_cancel" type="submit" value="{t}Cancel{/t}" class="form_button" onclick="doSubmit();"/>
{else}
{t}Edition not allowed{/t},<a href="?login=y&project={$project}">{t}please login{/t}</a>
{/if}
<img id="edit_recenter" src="{r type=gfx plugin=edit}edit_recenter.gif{/r}" style="display:none" />