<div id="layerReorderDiv">
   <div id="layerReorderPicto">
     <img src="{r type=gfx plugin=layerReorder}reorderUp.gif{/r}" 
          alt="{t}Upside{/t}" title="{t}Upside{/t}" 
          onclick="javascript:reorderUpside();"
          align="middle"
          id="layerReorderUp" />
     <img src="{r type=gfx plugin=layerReorder}reorderDown.gif{/r}" 
          alt="{t}Downside{/t}" title="{t}Downside{/t}"
          onclick="javascript:reorderDownside();" 
          align="middle"
          id="layerReorderDown" />
   </div>
   <br />
   <div id="layerReorderContainer">
    {foreach from=$layerReorder key=key item=layer name="layerReorder"}
     <div id="layerReorder_{$key}" class="layerReorder">
      <table cellpadding="0" cellspacing="0" width="100%">
       <tr>
        <td>
         <input id="layerReorderRadio_{$key}" type="radio" 
           name="currentLayerReorder" 
           value="{$key}" onclick="javascript:layerReorderCurrent({$key});"/>
         <label for="layerReorderRadio_{$key}">{$layer.label}</label>
         <input type="hidden" id="recenter_none_{$key}" 
                name="recenter_none_{$key}" value="{$layer.extent}" />
        </td>
    {if $enableOpacity|default:''}
        <td align="right">
         <select name="layersOpacity_{$key}" class="layersOpacity">
          {html_options options=$layerOpacityOptions 
                        selected=$layer.opacity}
         </select>
        </td>
    {/if}
       </tr>
      </table>  
     </div>
    {/foreach}
    <div id="layerReorder_last" class="layerReorder"></div>
   </div>
  <br />
  <input type="hidden" name="layersReorder" value="" />
  <input type="button" value="{t}Refresh{/t}" class="form_button"
           onclick="javascript: retrieveOrder();
             CartoWeb.trigger('LayerReorder.Apply', 'FormItemSelected()');
  "/>
</div>