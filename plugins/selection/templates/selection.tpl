
Info: <br/>
layerid: {$selection_layerid} <br/>

{foreach from=$selection_selectedids item=id}
        selected id: {$id} <a href="javascript:document.carto_form.selection_unselect.value = '{$id|escape:"url"}';document.carto_form.submit();"> 
        unselect id </a> <br/>
{/foreach}

<input type="hidden" name="selection_clear"/>
<a href="javascript:document.carto_form.selection_clear.value = '1';document.carto_form.submit();">
clear selection
</a> 
