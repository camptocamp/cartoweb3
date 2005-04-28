{foreach from=$tables item=group}
{foreach from=$group->tables item=table}
{if $table->numRows > 0}
<p>
<table cellpadding="0" cellspacing="0" class="cw3table" width="50%">
  <caption>{$table->tableTitle}</caption>
  <tr>
    {if !$table->noRowId}<th width="15%">{t}Id{/t}</th>{/if}
    {foreach from=$table->columnTitles item=column}
      <th>{$column}</th>               
    {/foreach}
  </tr>
  {foreach from=$table->rows item=row}
    <tr bgcolor="{cycle values="#dedede,#eeeeee"}">
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
</p>
{/if}
{/foreach}
{/foreach}
