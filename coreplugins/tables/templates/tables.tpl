{foreach from=$tables item=table}
{if $table->columnTitles}
<table class="queryres">
    <caption>{t}{$table->tableTitle}{/t}</caption>
    <tr>
      {foreach from=$table->columnTitles item=column}
      <th>{$column}</th>               
      {/foreach}
    </tr>
    {foreach from=$table->rows item=row}
    <tr>
       {foreach from=$table->columnTitles item=column key=columnId}
       <td>{foreach from=$row->cells item=value key=cellColumnId}{if $columnId==$cellColumnId}{$value}{/if}{/foreach}</td>
       {/foreach}
    </tr>
    {/foreach}
</table>
{/if}
{/foreach}
