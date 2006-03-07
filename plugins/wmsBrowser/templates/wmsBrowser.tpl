{strip}
<input type="hidden" name="removeAllWmsLayers" value="0" />
<br />
<a href="#" 
   onclick="exploreWmsLayersWindow=window.open('{$selfUrl}?project={$project}&amp;exploreWmsLayers=1','exploreWmsLayers','scrollbars=yes,resizable=yes,toolbar=yes,location=no,width=610,height=540');exploreWmsLayersWindow.focus();">
  <img src="{r plugin=wmsBrowser type=gfx/layout}add_wms_{$currentLang}.gif{/r}" alt="{t}Add WMS layers{/t}" border="0" />
</a><br />
<a href="#" 
   onclick="javascript:document.carto_form.removeAllWmsLayers.value=1;javascript:FormItemSelected();">
  <img src="{r plugin=wmsBrowser type=gfx/layout}remove_wms_{$currentLang}.gif{/r}" alt="{t}Remove all WMS layers{/t}" border="0" />
</a>
<br /><br />
{/strip}
