<p>{t}Show hilight attributes{/t}:&nbsp;
<input type="checkbox" name="hilight_retrieve_attributes"
        {if $hilight_retrieve_attributes} checked="checked"{/if}/>
</p>

{* FIXME: the css style should be imported by this plugin *}

{if $hilight_layer_result->fields}
<table class="queryres">
    <caption>{t}{$hilight_layer_result->layerId}{/t}</caption>
    <tr>
      <th>{t}Id{/t}</th>
      {foreach from=$hilight_layer_result->fields item=field}
      <th>{$field}</th>               
      {/foreach}
    </tr>
    {foreach from=$hilight_layer_result->resultElements item=result_element}
    <tr>
       <td>{$result_element->id}</td>
       {foreach from=$result_element->values item=value}
       <td>{$value}</td>
       {/foreach}
    </tr>
    {/foreach}
</table>
{/if}