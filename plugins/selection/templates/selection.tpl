<h3>Selection information: </h3>

select a hilight layer<br/>
<select name="selection_layerid" onChange="document.carto_form.submit();">
        {html_options values=$selection_selectionlayers selected=$selection_layerid 
           output=$selection_selectionlayers}
</select>

<br/>
selected elements:
<br/>

<input type="hidden" name="selection_unselect"/>
{foreach from=$selection_selectedids item=id}
<li>
        selected id: {$id} <a href="javascript:document.carto_form.selection_unselect.value = '{$id|escape:"url"}';document.carto_form.submit();"> 
        unselect id </a> <br/>
{/foreach}

<input type="hidden" name="selection_clear"/>
<a href="javascript:document.carto_form.selection_clear.value = '1';document.carto_form.submit();">
clear selection
</a> 
