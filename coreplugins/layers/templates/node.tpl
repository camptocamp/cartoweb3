{capture name=inputElt}
{if !$layerFrozen}
<input 
{if $layerRendering == 'radio'}type="radio" name="layers_{$parentId}"
{else}type="checkbox" name="layers[]"{/if}
value="{$layerId}" id="in{$nodeId}"
  onclick="javascript:updateChecked({$nodeId});" {if $layerChecked}checked="checked"{/if} />
{/if}
{/capture}

{capture name=caption}
{if $layerLink}<a href="{$layerLink}" target="_blank" 
title="{t}more info on{/t} {$layerLabel}">{$layerLabel}</a>
{else}
  {if $layerOutRange}<span class="out">{$layerLabel}</span>
  {else}{$layerLabel}{/if}
{/if}
{/capture}

{capture name=icon}
{if $layerIcon}
  <img src="{r type=gfx/icons}{$mapId}/{$layerIcon}{/r}" alt="" class="pic" 
  {if $nextscale}title="{t}Next visible scale:{/t} 1:{$nextscale}"
  {elseif $layerOutRange > 0}title="{t}Zoom in to see layer{/t}"
  {elseif $layerOutRange < 0}title="{t}Zoom out to see layer{/t}"{/if} />
{/if}
{/capture}

{if $layerRendering == 'block'}
<fieldset>
{/if}

{if $isDropDown}
  <select name="layers_dropdown_{$parentId}" 
  onchange="javascript:FormItemSelected();">
  {html_options options=$dropDownChildren selected=$dropDownSelected}
  </select>
{/if}

{if $childrenLayers}
  {if $isDropDown}
    <div>
  {elseif $layerId != 'root' && $layerRendering == 'tree'}
    <a href="javascript:shift('id{$nodeId}');" id="xid{$nodeId}" 
    class="lk"><img 
    src="{r type=gfx plugin=layers}{if $groupFolded}plus{else}minus{/if}.gif{/r}" 
    alt="{if $groupFolded}+{else}-{/if}" title="" /></a> 
    {$smarty.capture.inputElt}
    {$smarty.capture.icon}{$smarty.capture.caption}<br />
    <div class="{if $groupFolded}nov{else}v{/if}" id="id{$nodeId}">
  {/if}
  {foreach from=$childrenLayers item=layer}{$layer}{/foreach}
  {if $layerId != 'root' && $layerRendering == 'tree'}
    </div>
  {/if}
{else}
  {if $layerClassName != 'LayerClass'}
  <span class="leaf"></span>{$smarty.capture.inputElt}{/if}
  {$smarty.capture.icon}{$smarty.capture.caption}<br />
{/if}

{if $layerRendering == 'block'}
</fieldset>
{/if}
