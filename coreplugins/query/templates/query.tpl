
<!-- TODO: use a table instead -->

<br/>

{foreach from=$layer_results item=layer_result}

Layer result: {$layer_result->layerId} <br/>

    {foreach from=$layer_result->fields item=field}
		Fields: {$field} <br/>
		
    {/foreach}

    {foreach from=$layer_result->resultElements item=result_element}
        Result elements: </br>
        {foreach from=$result_element->values item=value}
            Value: {$value} </br>
        {/foreach}
        <br/>
    {/foreach}

<br/>
{/foreach}
