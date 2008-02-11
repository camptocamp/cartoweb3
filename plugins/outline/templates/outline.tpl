<fieldset class="outl">
  <legend>{t}Drawing mode{/t}</legend>
  <label>
    <input type="radio" name="outline_mask" value="no"{if $outline_mask_selected eq "no"} checked="checked"{/if}
     onclick="javascript: CartoWeb.trigger('Outline.ChangeMode');" />{t}Draw{/t}
  </label><br />
  <label>
    <input type="radio" name="outline_mask" value="yes"{if $outline_mask_selected eq "yes"} checked="checked"{/if}
     onclick="javascript: CartoWeb.trigger('Outline.ChangeMode');" />{t}Mask{/t}
  </label>
</fieldset>

<fieldset class="outl">
  <legend>{t}Point{/t}</legend>

<div class="outl_block_center">
  <span class="outl_label" >{t}symbol{/t}:</span>
  <div id="outline_point_symbol_d" class="outl_symbol_block" style="{if $outline_point_symbol_selected != ''}background-image: url({$pathToSymbols}{$outline_point_symbol_selected}.{$symbolType});{/if}" 
  onclick="javascript:toolPicker('4','outline_point_symbol');" {if $outline_point_symbol_selected != ''}title="{t}{$outline_point_symbol_selected}{/t}"{/if} ></div>
</div>
<input type="hidden" id="outline_point_symbol" name="outline_point_symbol" value="{$outline_point_symbol_selected}" />

<div class="outl_block_center">          
  <span class="outl_label" >{t}size{/t}:</span><input type="text" id="outline_point_size" name="outline_point_size" size="3" value="{$outline_point_size_selected}" />    
</div>

<div class="outl_block_center">
  <span class="outl_label" >{t}color{/t}:</span>
  <div id="outline_point_color_d" class="outl_color_block" style="{if $outline_point_color_selected != ''}background-color:{$outline_point_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_point_color');" ></div>
  <input type="text" id="outline_point_color" name="outline_point_color" class="outl_color_hex" size="7" value="{$outline_point_color_selected}" />
</div>

<div class="outl_block_center">          
  <span class="outl_label" >{t}transparency{/t}:</span>
  <input type="text" id="outline_point_transparency" name="outline_point_transparency" size="3" value="{$outline_point_transparency_selected}" />    
</div>

</fieldset>

<fieldset class="outl">
  <legend>{t}Line{/t}</legend>

<div class="outl_block_center">          
  <span class="outl_label" >{t}border size{/t}:</span>
  <input type="text" id="outline_line_size" name="outline_line_size" size="3" value="{$outline_line_size_selected}" />  
</div>

<div class="outl_block_center">
  <span class="outl_label" >{t}color{/t}:</span>
  <div id="outline_line_color_d" class="outl_color_block" style="{if $outline_line_color_selected != ''}background-color:{$outline_line_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_line_color');" ></div>
  <input type="text" id="outline_line_color" name="outline_line_color" size="7" class="outl_color_hex" value="{$outline_line_color_selected}" />
</div>

<div class="outl_block_center">          
  <span class="outl_label" >{t}transparency{/t}:</span>
  <input type="text" id="outline_line_transparency" name="outline_line_transparency" size="3" value="{$outline_line_transparency_selected}" />    
</div>

</fieldset>

<fieldset class="outl">
<legend>{t}Rectangle/polygon/circle{/t}</legend>

<div class="outl_block_center">
  <span class="outl_label" >{t}outline color{/t}:</span>
  <div id="outline_polygon_outline_color_d" class="outl_color_block" style="{if $outline_polygon_outline_color_selected != ''}background-color:{$outline_polygon_outline_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_polygon_outline_color');" ></div>
  <input type="text" id="outline_polygon_outline_color" name="outline_polygon_outline_color" size="7" class="outl_color_hex" value="{$outline_polygon_outline_color_selected}" />
</div>

<div class="outl_block_center">
  <span class="outl_label" >{t}background color{/t}:</span>
  <div id="outline_polygon_background_color_d" class="outl_color_block" style="{if $outline_polygon_background_color_selected != ''}background-color:{$outline_polygon_background_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_polygon_background_color');" ></div>
  <input type="text" id="outline_polygon_background_color" name="outline_polygon_background_color" size="7" class="outl_color_hex" value="{$outline_polygon_background_color_selected}" />
</div>

<div class="outl_block_center">          
  <span class="outl_label" >{t}transparency{/t}:</span>
  <input type="text" id="outline_polygon_transparency" name="outline_polygon_transparency" size="3" value="{$outline_polygon_transparency_selected}" />    
</div>

</fieldset>

<fieldset class="outl">
<legend>{t}Circle{/t}</legend>

<div class="outl_block_center">
  {t}To draw a circle, either clic&drag on the map or set a value for the radius and clic on the map.{/t}<br />
  <span class="outl_label" >{t}circle radius{/t}:</span>
  <input type="text" id="outline_circle_radius" name="outline_circle_radius" size="5" value="{$outline_circle_radius}" />
</div>

</fieldset>

{t}Total area{/t}: {$outline_area}<br />
<input type="submit" name="outline_clear" value="{t}outline_clear{/t}" class="form_button"
       onclick="javascript: return CartoWeb.trigger('Outline.Clear');" />
