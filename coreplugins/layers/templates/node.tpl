{if $childrenLayers}
  {if $layerId != 'root'}
  <a href="javascript:shift('id{$nodeId}');" id="xid{$nodeId}" class="lk">+</a> <input
  type="checkbox" name="layers[]" value="{$layerId}" id="in{$nodeId}"
  onclick="javascript:updateChecked({$nodeId});" {if $layerChecked}checked="checked"{/if} />{$layerLabel}<br />
  <div class="nov" id="id{$nodeId}">
  {/if}
  {foreach from=$childrenLayers item=layer}
  {$layer}
  {/foreach}
  {if $layerId != 'root'}
  </div>
  {/if}
{else}
<span class="lk">-</span> <input
type="checkbox" name="layers[]" value="{$layerId}" id="in{$nodeId}"
onclick="javascript:updateChecked({$nodeId});" {if $layerChecked}checked="checked"{/if} />{$layerLabel}<br />
{/if}
