{foreach from=$tables item=group}
<h2>{$group->groupTitle}</h2>
{foreach from=$group->tables item=table}
{if $table->numRows > 0}
<p>
<table class="queryres">
    <caption>{t}{$table->tableTitle}{/t}</caption>
    <tr>
      <th>{t}Id{/t}</th>
      {foreach from=$table->columnTitles item=column}
      <th>{$column}</th>               
      {/foreach}
    </tr>
    {foreach from=$table->rows item=row}
    <tr>
       <td>{$row->rowId}</td>
       {foreach from=$row->cells item=value}
       <td>{$value}</td>
       {/foreach}
    </tr>
    {/foreach}
</table>
{if $exportcsv_active|default:''}
<div class="exportlink"><a href="{$exportcsv_url}?exportcsv_tableid={$table->tableId}">{t}Download CSV{/t}</a></div>
{/if}
</p>
{/if}
{/foreach}
{/foreach}
