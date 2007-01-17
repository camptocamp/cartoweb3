<select name="search_country" id="search_country">
{foreach from=$table->rows item=row}
<option value="{$row->rowId}">
 {foreach from=$row->cells item=value}
   {$value}
 {/foreach}
</option>
{/foreach}
</select>
