<h3>{t}Selection information:{/t}</h3>

<p>{t}Select a hilight layer{/t}&nbsp;
<select name="selection_layerid" id="selection_layerid"
onChange="javascript:FormItemSelected();">
{html_options values=$selection_selectionlayers selected=$selection_layerid 
output=$selection_selectionlayers}
</select></p>

{if $selection_selectedids}
<p>{t}Selected elements:{/t}</p>

<input type="hidden" name="selection_unselect" />
<ul>
{foreach from=$selection_selectedids item=id}
<li>{t}Selected id:{/t} {$id} <a href="javascript:document.carto_form.selection_unselect.value='{$id|escape:"url"}';FormItemSelected();">{t}unselect id{/t}</a></li>
{/foreach}
</ul>

<input type="hidden" name="selection_clear" />
<p><a href="javascript:document.carto_form.selection_clear.value='1';FormItemSelected();">
{t}clear selection{/t}</a></p>
{/if}
