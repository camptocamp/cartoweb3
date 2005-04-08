{foreach from=$tables item=group}
<h2>{$group->groupTitle}</h2>
{foreach from=$group->tables item=table}
{if $table->numRows > 0}
<table class="cw3table">
    <caption>{$table->tableTitle}</caption>
    <tr>
      {if !$table->noRowId}<th>{t}Id{/t}</th>{/if}
      {foreach from=$table->columnTitles item=column}
      <th>{$column}</th>               
      {/foreach}
    </tr>
    {foreach from=$table->rows item=row}
    <tr>
       {if !$table->noRowId}<td>{$row->rowId}</td>{/if}
       {foreach from=$row->cells item=value}
       <td>{$value}</td>
       {/foreach}
    </tr>
    {/foreach}
</table>
{if $exportcsv_active|default:''}
<div class="exportlink"><a href="{$exportcsv_url}project={$project}&amp;exportcsv_groupid={$group->groupId}&amp;exportcsv_tableid={$table->tableId}">{t}Download CSV{/t}</a></div>
{/if}
{/if}
{/foreach}
{/foreach}
