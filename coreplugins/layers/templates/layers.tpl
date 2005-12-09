<input type="hidden" id="openNodes" name="openNodes" value="{$startOpenNodes}" />
<script type="text/javascript">
  <!--
  var openNodes = new Array('{$startOpenNodes}');
  writeOpenNodes(true);
  //-->
</script>
<div id="layerscmd"><a href="javascript:expandAll('layersroot');">{t}expand tree{/t}</a> -
<a href="javascript:closeAll('layersroot');">{t}close tree{/t}</a><br />
<a href="javascript:checkChildren('layersroot'); if (typeof(AjaxHandler) != 'undefined') AjaxHandler.doAction('Layers.LayerShowHide');">{t}check all{/t}</a> -
<a href="javascript:checkChildren('layersroot',false); if (typeof(AjaxHandler) != 'undefined') AjaxHandler.doAction('Layers.LayerShowHide');">{t}uncheck all{/t}</a></div>
<div id="layersroot">

{defun name="drawChildren" element=$element}

{capture name=inputElt}
{if !$element.layerFrozen}
<input 
{if $element.layerRendering == 'radio'}type="radio" name="layers_{$element.parentId}"
{else}type="checkbox" name="layers[]" {/if}
value="{$element.layerId}" id="in{$element.nodeId}"
  onclick="javascript:updateChecked('{$element.nodeId}');
	if (typeof(AjaxHandler) == 'undefined') FormItemSelected(); else AjaxHandler.doAction('Layers.LayerShowHide');"
  {if $element.layerChecked}checked="checked"{/if} />
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
{if $element.nextscale}<a href="javascript:goToScale('{$element.nextscale}')">{/if}
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
  onchange="javascript:if (typeof(AjaxHandler) == 'undefined') FormItemSelected(); else AjaxHandler.doAction('Layers.LayerDropDownChange');">
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
