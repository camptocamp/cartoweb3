
<!-- TODO: use a table instead -->

<br/>

{foreach from=$layer_results item=layer_result}

Layer result: {$layer_result->layerId}

	{foreach from=$layer_result->resultElements item=result_element}
		Result elements: </br>
		{foreach from=$result_element->fields key=field_key item=field_value}
			Field: {$field_key} = {$field_value} </br>
		{/foreach}
		<br/>
	{/foreach}

<br/>
{/foreach}
