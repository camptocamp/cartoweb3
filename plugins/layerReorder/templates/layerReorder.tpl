<div id="reorderContainer">
  {foreach from=$layerReorder key=key item=layer}
    <div id="layerReorder_{$key}" class="layerReorder">
      <div id="layerReorderInter_{$key}"
           class="layerReorderInter"
           onmouseup="javascript:reorderUnselect(this);"
           onmouseover="javascript:reorderInterOver(this);">&nbsp;</div>

      <div id="layerReorderLayer_{$key}"
           class="layerReorderLayer"
           onmouseup="javascript:reorderUnselect(this);"
           onmousedown="javascript:reorderSelect(this);">{$layer}</div> 
    </div>
  {/foreach}
  <div id="layerReorder_last" class="layerReorder">
    <div id="layerReorderInter_last"
         class="layerReorderInter"
         onmouseup="javascript:reorderUnselect(this);"
         onmouseover="javascript:reorderInterOver(this);">&nbsp;</div>
  </div>
</div>

<input type="hidden" name="layersReorder" value="" />
<input type="submit" onclick="javascript:retrieveOrder();" 
       value="{t}Submit{/t}" class="form_button" />
