<u>{t}Editing plugin:{/t}</u><br />
{if $edit_allowed|default:''}

{t}Choose layer{/t}<br />
<select name="edit_layer" onchange="javascript:FormItemSelected();">
<option value="0">{t}layer to edit{/t}</option>
{foreach from=$edit_layers item=edit_layer}
  <option value="{$edit_layer->id}"
  {if $edit_layer->disabled && $edit_layer->id != $edit_layer_selected}disabled="disabled"{/if}
  {if $edit_layer->id == $edit_layer_selected}selected="selected"{/if}
  >{t}{$edit_layer->label}{/t}</option>
{/foreach}
</select><br />
{t}Features: {/t}<span id="features_num"></span><br />
{t}Inserted: {/t}<span id="inserted_features_num"></span><br />
{t}Modified: {/t}<span id="modified_features_num"></span><br />
{t}Deleted: {/t}<span id="deleted_features_num"></span><br />
<br />
<div id="validateEdit">
  <input type="hidden" name="edit_validate_all" value="0" />
  <input type="button" id="validate_all" value="{t}Validate{/t}" onclick="myform['edit_validate_all'].value = '1';if (storeFeatures()) doSubmit();" class="form_button" />
</div>
<input name="edit_cancel" type="submit" value="{t}Cancel{/t}" class="form_button" onclick="doSubmit();" />
{else}
{t}Editing not allowed{/t},<a href="{$selfUrl}?login=y&amp;project={$project}">{t}please login{/t}</a>
{/if}
<img id="edit_recenter" src="{r type=gfx plugin=edit}edit_recenter.gif{/r}" style="display:none" />

<script language="JavaScript" type="text/javascript">
    <!--
    var editResultNbCol = '{$edit_resultNbCol}';
    var editDisplayAction = '{$edit_displayAction}';
    //-->
</script>
