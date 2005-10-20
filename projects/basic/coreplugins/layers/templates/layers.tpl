{counter name="level" assign="level" start="-1"}
{defun name="drawChildren" element=$element level="$level"}

{capture name=inputElt}
{if !$element.layerFrozen}
<input 
{if $element.layerRendering == 'radio'}type="radio" name="layers_{$element.parentId}"
{else}type="checkbox" name="layers[]" {/if}
value="{$element.layerId}" {if $element.layerChecked}checked="checked"{/if} />
{/if}
{/capture}

{capture name=caption}
{if $element.layerLink}<a href="{$element.layerLink}" target="_blank" 
title="{t}more info on{/t} {$element.layerLabel}">{$element.layerLabel}</a>
{else}{$element.layerLabel}{/if}
{/capture}

{capture name=icon}
{if $element.layerIcon}
<img src="{$element.layerIcon}" alt=""
{if $element.layerOutRange > 0}title="{t}Zoom in to see layer{/t}"
{elseif $element.layerOutRange < 0}title="{t}Zoom out to see layer{/t}"{/if} />
{/if}
{/capture}                                                                              

{if $element.layerRendering == 'block'}
<fieldset>
<legend>{$element.layerLabel}</legend>
{/if}

{if $element.isDropDown}
  <select name="layers_dropdown_{$element.parentId}">
  {html_options options=$element.dropDownChildren selected=$element.dropDownSelected}
  </select>
{/if}

{if $element.elements}
  {if $element.layerId != 'root' && ($element.layerRendering == 'tree' || 
                                     $element.layerRendering == 'radio')}
    {section name="indent" loop="$level"}&nbsp;&nbsp;{/section}
    {$smarty.capture.inputElt}{$smarty.capture.icon}{$smarty.capture.caption}<br />
  {/if}

  {if $element.layerClassName != 'LayerClass'}
    {foreach from=$element.elements item=subelement}
      {counter name="level" direction="up"}
      {fun name="drawChildren" element=$subelement level=$level}
      {counter name="level" direction="down"}
    {/foreach}
  {/if}

{else}
  {section name="indent" loop="$level"}&nbsp;&nbsp;{/section}
  {if $element.layerClassName != 'LayerClass'}
  {$smarty.capture.inputElt}{/if}
  {$smarty.capture.icon}{$smarty.capture.caption}<br />
{/if}

{if $element.layerRendering == 'block'}
</fieldset>
{/if}

{/defun}
