{if $childrenLayers}
  {if $layerId != 'root'}
<table cellpadding="0" cellspacing="0"><tr>
  <td class="lk"><a href="javascript:shift('id{$nodeId}');" id="xid{$nodeId}">{if $groupFolded}+{else}-{/if}</a></td>
  <td class="node">
    <table cellpadding="0" cellspacing="0">
      <tr>
        <td class="inp"><input
        type="checkbox" name="layers[]" value="{$layerId}" id="in{$nodeId}"
        onclick="javascript:updateChecked({$nodeId});" {if $layerChecked}checked="checked"{/if} /></td>
        <td>{$layerLabel}</td>
      </tr>
      <tr>
        <td colspan="2" class="node">
          <div class="{if $groupFolded}nov{else}v{/if}" id="id{$nodeId}">
  {/if}
    {foreach from=$childrenLayers item=layer}
    {$layer}
    {/foreach}
  {if $layerId != 'root'}
          </div>
        </td>
      </tr>
    </table>
  </td>
</tr></table>
  {/if}
{else}
<table cellpadding="0" cellspacing="0"><tr>
  <td class="lk">-</td>
  <td class="inp"><input type="checkbox" name="layers[]" value="{$layerId}" 
  id="in{$nodeId}" onclick="javascript:updateChecked({$nodeId});" 
  {if $layerChecked}checked="checked"{/if} /></td>
  <td>{$layerLabel}</td>
</tr></table>
{/if}
