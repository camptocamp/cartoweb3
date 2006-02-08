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
         <label><input id="layerReorderRadio_{$key}" type="radio" 
           name="currentLayerReorder" 
           value="{$key}" onclick="javascript:layerReorderCurrent({$key});"
           />{$layer.label}</label>
         <input type="hidden" id="recenter_none_{$key}" 
                name="recenter_none_{$key}" value="{$layer.extent}" />
        </td>
    {if $enableTransparency|default:''}
        <td align="right">
         <select name="layersTransparency_{$key}" class="layersTransparency">
          {html_options options=$layerTransparencyOptions 
                        selected=$layer.transparency}
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
  <input type="button" onclick="javascript:retrieveOrder();FormItemSelected();"
         value="{t}Refresh{/t}" class="form_button" />
