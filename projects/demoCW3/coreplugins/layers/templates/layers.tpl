<input type="hidden" id="openNodes" name="openNodes" />
<script type="text/javascript">
  <!--
  var openNodes = new Array('{$startOpenNodes}');
  writeOpenNodes(true);
  //-->
</script>
<div id="layerscmd">
<a href="javascript:void(0);" onclick="javascript:expandAll('layersroot');"><img src="{r type=gfx/layout}add.gif{/r}" id="expand_tree" alt="{t}expand tree{/t}" title="{t}Expand tree{/t}"></img></a>&nbsp;&nbsp;&nbsp;&nbsp;
<a href="javascript:void(0);" onclick="javascript:closeAll('layersroot');"><img src="{r type=gfx/layout}remove.gif{/r}" id="close_tree" alt="{t}close tree{/t}" title="{t}Close tree{/t}"></img></a>&nbsp;&nbsp;&nbsp;&nbsp;
<a href="javascript:void(0);" onclick="javascript:checkChildren('layersroot'); CartoWeb.trigger('Layers.LayerShowHide');"><img src="{r type=gfx/layout}check.gif{/r}" id="check_all" alt="{t}check all{/t}" title="{t}Check all{/t}"></img></a>&nbsp;&nbsp;&nbsp;&nbsp;
<a href="javascript:void(0);" onclick="javascript:checkChildren('layersroot', false); CartoWeb.trigger('Layers.LayerShowHide');"><img src="{r type=gfx/layout}uncheck.gif{/r}" id="uncheck_all" alt="{t}uncheck all{/t}" title="{t}Uncheck all{/t}"></img></a>&nbsp;&nbsp;&nbsp;&nbsp;<br /></div>
<div id="layersroot">

{defun name="drawChildren" element=$element}

{capture name=inputElt}
{if !$element.layerFrozen}
<input 
{if $element.layerRendering == 'radio'}type="radio" name="layers_{$element.parentId}"
{else}type="checkbox" name="layers[]" {/if}
value="{$element.layerId}" id="in{$element.nodeId}"
  onclick="javascript:updateChecked('{$element.nodeId}');
    CartoWeb.trigger('Layers.LayerShowHide');" {if $element.layerChecked}checked="checked"{/if} />
{/if}
{/capture}

{capture name=caption}
{if $element.layerLink}<a href="{$element.layerLink}" target="_blank" 
title="{t}more info on{/t} {$element.layerLabel}">{$element.layerLabel}</a>
{else}
  {if $element.layerOutRange}<span class="out">{$element.layerLabel}</span>
  {else}{$element.layerLabel}{/if}
{/if}
{/capture}

{capture name=icon}
{if $element.layerIcon}
{if $element.nextscale}<a href="javascript:void(0);" onclick="javascript:goToScale('{$element.nextscale}');">{/if}
<img src="{$element.layerIcon}" alt="" class="pic"
{if $element.nextscale}title="{t}Click to go to next visible scale:{/t} 1:{$element.nextscale}"
{elseif $element.layerOutRange > 0}title="{t}Zoom in to see layer{/t}"
{elseif $element.layerOutRange < 0}title="{t}Zoom out to see layer{/t}"{/if} />
{if $element.nextscale}</a>{/if}
{/if}
{/capture}                                                                              

{if $element.layerRendering == 'block'}
<fieldset>
<legend>{$element.layerLabel}</legend>
{/if}

{if $element.isDropDown}
  <select name="layers_dropdown_{$element.parentId}" 
  onchange="javascript:CartoWeb.trigger('Layers.LayerDropDownChange', 'FormItemSelected()');">
  {html_options options=$element.dropDownChildren selected=$element.dropDownSelected}
  </select>
{/if}

{if $element.elements}
  {if $element.isDropDown || $element.isRadioContainer}
    <div>
  {elseif $element.layerId != 'root' && ($element.layerRendering == 'tree' || 
                                         $element.layerRendering == 'radio')}
    <a href="javascript:shift('id{$element.nodeId}');" id="xid{$element.nodeId}" 
    class="lk"><img 
    src="{r type=gfx plugin=layers}{if $element.groupFolded}plus{else}minus{/if}.gif{/r}" 
    alt="{if $element.groupFolded}+{else}-{/if}" title="" /></a>
    {$smarty.capture.inputElt}{$smarty.capture.icon}{$smarty.capture.caption}<br />
    <div class="{if $element.groupFolded}nov{else}v{/if}" id="id{$element.nodeId}">
  {/if}

  {if $element.layerClassName != 'LayerClass'}
    {foreach from=$element.elements item=subelement}
      {fun name="drawChildren" element=$subelement}
    {/foreach}
  {/if}

  {if $element.isDropDown || $element.isRadioContainer ||
      ($element.layerId != 'root' && ($element.layerRendering == 'tree' ||
                                      $element.layerRendering == 'radio')
      )}
    </div>
  {/if}

{else}
  {if $element.layerClassName != 'LayerClass'}
  <span class="leaf"></span>{$smarty.capture.inputElt}{/if}
  {$smarty.capture.icon}{$smarty.capture.caption}<br />
{/if}

{if $element.layerRendering == 'block'}
</fieldset>
{/if}

{/defun}

</div>
