{foreach from=$imagemapFeatures item=feature key=index}
  $('{$feature->layer}{$index+1}')._fid = {literal}{{/literal}layer: '{$feature->layer}', id: {$feature->id}{literal}}{/literal};
  {strip}
  {if $feature->attributes|default:''}
  $('{$feature->layer}{$index+1}')._attributes = {literal}{{/literal}
  {foreach from=$feature->attributes name=attributes item=value key=attribute}
      "{$attribute}": "{$value}"{if !$smarty.foreach.attributes.last},{/if}
  {/foreach}
  {literal}}{/literal};
  {/if}
  {/strip}
{/foreach}