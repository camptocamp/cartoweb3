<!-- TODO: css and cosmetics -->

<table>

{foreach from=$layer_results item=layer_result}
{if $layer_result->fields}
  <tr bgcolor="#cccccc"><th>
 {t}{$layer_result->layerId}{/t}
  </th></tr>
  <tr><td>

  <table border="1" width="100%">
    <tr>
       <th>Id</th>
    {foreach from=$layer_result->fields item=field}
       <th>{$field}</th>               
    {/foreach}
    </tr>
    {foreach from=$layer_result->resultElements item=result_element}
    <tr>
       <td>{$result_element->id}</td>
        {foreach from=$result_element->values item=value}
       <td>
         {$value}
       </td>
        {/foreach}
    </tr>
    {/foreach}
  </table>

  </td></tr>
  <tr><td>&nbsp;</td></tr>
{/if}
{/foreach}

</table>
