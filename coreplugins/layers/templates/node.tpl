{if $layerType == 'LayerGroup'}
  {if $layerId != 'root'}
  <a href="#" onclick="shift('{$nodeId}')" id="x{$nodeId}" class="lk">-</a> <input
  type="checkbox" name="layers[]" value="{$layerId}" 
  onclick="updateChecked('{$nodeId}', true)" {$layerChecked} />{$layerLabel}
  <div class="v" id="{$nodeId}">
  {/if}
  {foreach from=$childrenLayers item=layer}
  {$layer}
  {/foreach}
  {if $layerId != 'root'}
  </div>
  {/if}
{else}
<span class="lk">-</span> <input
type="checkbox" name="layers[]" value="{$layerId}"
onclick="updateChecked('{$nodeId}', false)" {$layerChecked} />{$layerLabel}<br />
{/if}
