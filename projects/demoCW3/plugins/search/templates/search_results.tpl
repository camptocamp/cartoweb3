{if $table->numRows > 0}

{t}Total:{/t} {$table->totalRows}<br />
{if $table->totalRows > $table->numRows}
{section name=offset start=1 loop=$table->totalPages+1}
<a href="javascript: $('search_page').value = '{$smarty.section.offset.index}';
                     search('airports');">
{if $smarty.section.offset.index == $table->page}
  [{$smarty.section.offset.index}]
{else}
  {$smarty.section.offset.index}
{/if}</a>&nbsp;
{/section}
{/if}
<table class="cw3table">
    <tr>
      {foreach from=$table->columnIds item=column}
      <th>
        <a href="javascript: $('search_sort_column').value = '{$column}';
                             $('search_page').value = 1;
                             search('airports');">{$column}</a>
      </th>               
      {/foreach}
    </tr>
    {foreach from=$table->rows item=row}
    <tr>
       {foreach from=$row->cells item=value}
       <td><a href="javascript: recenterAirport({$row->rowId});">{$value}</a></td>
       {/foreach}
    </tr>
    {/foreach}
</table>

{else}
{t}No results{/t}
{/if}
