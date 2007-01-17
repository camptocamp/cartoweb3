{if $table->numRows > 0}
<table class="cw3table">
    <tr>
      <th>{t}Id{/t}</th>
      {foreach from=$table->columnIds item=column}
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
{/if}
