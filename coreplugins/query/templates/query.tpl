<h3>{t}Results:{/t}</h3>

{foreach from=$layer_results item=layer_result}
{if $layer_result->fields}
<table class="queryres">
    <caption>{t}{$layer_result->layerId}{/t}</caption>
    <tr>
      <th>{t}Id{/t}</th>
      {foreach from=$layer_result->fields item=field}
      <th>{$field}</th>               
      {/foreach}
    </tr>
    {foreach from=$layer_result->resultElements item=result_element}
    <tr>
       <td>{$result_element->id}</td>
       {foreach from=$result_element->values item=value}
       <td>{$value}</td>
       {/foreach}
    </tr>
    {/foreach}
</table>
{if $exportcsv_active|default:''}
<div class="exportlink"><a href="{$exportcsv_url}?exportcsv_layerid={$layer_result->layerId}">{t}Download CSV{/t}</a></div>
{/if}
{/if}
{/foreach}
