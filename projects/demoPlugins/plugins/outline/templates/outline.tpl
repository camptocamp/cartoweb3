<fieldset class="outl">
  <legend>{t}Point{/t}</legend>

<div class="outl_block_center">
  {t}symbol{/t}:
  <center>
  <div id="outline_point_symbol_d" class="outl_symbol_block" style="{if $outline_point_symbol_selected != ''}background-image: url({$pathToSymbols}{$outline_point_symbol_selected}.{$symbolType});{/if}" 
  onclick="javascript:toolPicker('4','outline_point_symbol');" {if $outline_point_symbol_selected != ''}title="{t}{$outline_point_symbol_selected}{/t}"{/if} ></div>
  </center>
</div>
<input type="hidden" id="outline_point_symbol" name="outline_point_symbol" value="{$outline_point_symbol_selected}" />

<div class="outl_block_center">          
  {t}size{/t}:<br /><input type="text" name="outline_point_size" size="3" value="{$outline_point_size_selected}" />    
</div>

<div class="outl_block_center">
  {t}color{/t}:
  <center>
  <div id="outline_point_color_d" class="outl_color_block" style="{if $outline_point_color_selected != ''}background-color:{$outline_point_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_point_color');" ></div>
  </center>
  <input type="text" id="outline_point_color" name="outline_point_color" size="7" value="{$outline_point_color_selected}" />
</div>
</fieldset>

<fieldset class="outl">
  <legend>{t}Line{/t}</legend>

<div class="outl_block_center">          
  {t}border size{/t}:<br />
  <input type="text" name="outline_line_size" size="3" value="{$outline_line_size_selected}" />  
</div>

<div class="outl_block_center">
  {t}color{/t}:
  <center>
  <div id="outline_line_color_d" class="outl_color_block" style="{if $outline_line_color_selected != ''}background-color:{$outline_line_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_line_color');" ></div>
  </center>
  <input type="text" id="outline_line_color" name="outline_line_color" size="7" value="{$outline_line_color_selected}" />
</div>

<div class="outl_block_center">          
  {t}transparency{/t}:<br />
  <input type="text" name="outline_line_transparency" size="3" value="{$outline_line_transparency_selected}" />    
</div>

</fieldset>

<fieldset class="outl">
<legend>{t}Rectangle/polygon{/t}</legend>

<div class="outl_block_center">
  {t}outline color{/t}:
  <center>
  <div id="outline_polygon_outline_color_d" class="outl_color_block" style="{if $outline_polygon_outline_color_selected != ''}background-color:{$outline_polygon_outline_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_polygon_outline_color');" ></div>
  </center>
  <input type="text" id="outline_polygon_outline_color" name="outline_polygon_outline_color" size="7" value="{$outline_polygon_outline_color_selected}" />
</div>

<div class="outl_block_center">
  {t}background color{/t}:
  <center>
  <div id="outline_polygon_background_color_d" class="outl_color_block" style="{if $outline_polygon_background_color_selected != ''}background-color:{$outline_polygon_background_color_selected};{/if}"  onclick="javascript:toolPicker('1','outline_polygon_background_color');" ></div>
  </center>
  <input type="text" id="outline_polygon_background_color" name="outline_polygon_background_color" size="7" value="{$outline_polygon_background_color_selected}" />
</div>

<div class="outl_block_center">          
  {t}transparency{/t}:<br />
  <input type="text" name="outline_polygon_transparency" size="3" value="{$outline_polygon_transparency_selected}" />    
</div>
</fieldset>

<center>
<input type="submit" name="outline_clear" value="{t}Clear outline{/t}" class="form_button" />
</center>
