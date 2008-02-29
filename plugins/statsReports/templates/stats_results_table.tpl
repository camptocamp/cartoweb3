<table class="cw3table">
    <tr>
      <th>&nbsp;</th>
      {foreach from=$stats_columnTitles item=column}
      <th>{t}{$column}{/t}</th>               
      {/foreach}
    </tr>
    {foreach from=$stats_lines item=line}
    <tr>
       <td>{$line->lineTitle}</td>
       {foreach from=$line->values item=value}
       <td>{$value}</td>
       {/foreach}
    </tr>
    {/foreach}
</table>
