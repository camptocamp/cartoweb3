<h3>{t}Selection information:{/t}</h3>

<p>{t}Select a hilight layer{/t}&nbsp;
<select name="selection_layerid" id="selection_layerid"
onchange="javascript:FormItemSelected();">
{html_options values=$selection_selectionlayers selected=$selection_layerid 
output=$selection_selectionlayers}
</select></p>

<p>{t}Mask mode{/t}&nbsp;
<input type="checkbox" name="selection_maskmode"{if $selection_maskmode} checked="checked"{/if}/>

{if $selection_hilightattr_active|default:''}
<br>{t}Show attributes{/t}&nbsp;
<input type="checkbox" name="selection_retrieve_attributes"
        {if $selection_retrieve_attributes|default:''} checked="checked"{/if}/>
{/if}
</p>

{if $selection_selectedids}
<p>{t}Selected elements:{/t}</p>

<input type="hidden" name="selection_unselect" />
<table class="queryres">
 <tr>
 <th>{t}Id{/t}</th>
 {if $selection_retrieve_attributes|default:''}
 {foreach from=$selection_layer_result->fields item=field}
 <th>{$field}</th>               
 {/foreach}
 {/if}
 <th>&nbsp;</th>
</tr>
{foreach from=$selection_layer_result->resultElements item=result_element}
<tr>
 <td>{$result_element->id}</td>
 {if $selection_retrieve_attributes|default:''}
 {foreach from=$result_element->values item=value}
 <td>{$value}</td>
 {/foreach}
 {/if}
 <td><a href="javascript:document.carto_form.selection_unselect.value='{$result_element->id|escape:"url"}';FormItemSelected();">{t}unselect id{/t}</a></td>
</tr>
{/foreach}
</table>

<input type="hidden" name="selection_clear" />
<div class="exportlink"><a href="javascript:document.carto_form.selection_clear.value='1';FormItemSelected();">
{t}clear selection{/t}</a></div>
{/if}
