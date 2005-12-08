<fieldset>
  <legend>{t}Drawing mode{/t}</legend>
  <label>
    <input type="radio" name="outline_mask" value="no"{if $outline_mask_selected eq "no"} checked="checked"{/if} />{t}Draw{/t}
  </label><br />
  <label>
    <input type="radio" name="outline_mask" value="yes"{if $outline_mask_selected eq "yes"} checked="checked"{/if} />{t}Mask{/t}
  </label>
</fieldset>

<fieldset>
  <legend>{t}Point{/t}</legend>
{t}symbol{/t}: <div id="outline_point_symbol_d" style="height:30px;width:30px;border:1px solid black;cursor:pointer;{if $outline_point_symbol_selected != ''}background-image: url({$pathToSymbols}{$outline_point_symbol_selected}.{$symbolType});{/if}" onclick="javascript:toolPicker('4','outline_point_symbol');"></div>
<input type="hidden" id="outline_point_symbol" name="outline_point_symbol" value="{$outline_point_symbol_selected}" {if $outline_point_symbol_selected != ''}title="{t}{$outline_point_symbol_selected}{/t}"{/if} />
          
{t}size{/t}: <input type="text" name="outline_point_size" size="3" value="{$outline_point_size_selected}" /><br/>
<div id="outline_point_color_d" onclick="javascript:toolPicker('1','outline_point_color');"
    style="border:1px solid black;cursor:pointer;background-color:{$outline_point_color_selected};">{t}color{/t}</div>
  <input type="hidden" id="outline_point_color" name="outline_point_color" size="7" value="{$outline_point_color_selected}" />
</fieldset>

<fieldset>
  <legend>{t}Line{/t}</legend>

{t}border size{/t}:
<input type="text" name="outline_line_size" size="3" value="{$outline_line_size_selected}" /><br />

<div id="outline_line_color_d" onclick="javascript:toolPicker('1','outline_line_color');"
    style="border:1px solid black;cursor:pointer;background-color:{$outline_line_color_selected};">{t}color{/t}</div>
<input type="hidden" id="outline_line_color" name="outline_line_color" size="7" value="{$outline_line_color_selected}" />
{t}transparency{/t}: <input type="text" name="outline_line_transparency" size="3" value="{$outline_line_transparency_selected}" />
</fieldset>

<fieldset>
<legend>{t}Rectangle/polygon{/t}</legend>
<div id="outline_polygon_outline_color_d" onclick="javascript:toolPicker('1','outline_polygon_outline_color');"
    style="border:1px solid black;cursor:pointer;background-color:{$outline_polygon_outline_color_selected};">{t}outline color{/t}</div>
<input type="hidden" id="outline_polygon_outline_color" name="outline_polygon_outline_color" size="7" value="{$outline_polygon_outline_color_selected}" />

<div id="outline_polygon_background_color_d" onclick="javascript:toolPicker('1','outline_polygon_background_color');"
    style="border:1px solid black;cursor:pointer;background-color:{$outline_polygon_background_color_selected};">{t}background color{/t}</div>
<input type="hidden" id="outline_polygon_background_color" name="outline_polygon_background_color" size="7" value="{$outline_polygon_background_color_selected}" />
{t}transparency{/t}: <input type="text" name="outline_polygon_transparency" size="3" value="{$outline_polygon_transparency_selected}" />
</fieldset>

{t}Total area{/t}: {$outline_area}<br />
<input type="submit" name="outline_clear" value="{t}outline_clear{/t}" class="form_button" />

<script type="text/javascript">
  /*<![CDATA[*/
    var imgPath = '{$pathToSymbols}';
    var symbolType = '{$symbolType}';

    var symbolNamesArray = new Array(
    {foreach name=symbolsList item=symbols from=$outline_point_available_symbols}
        "{$symbols}"{if !$smarty.foreach.symbolsList.last},{/if}
    {/foreach});

    var symbolLabelArray = new Array(
    {foreach name=symbolsLabelsList item=labels from=$outline_point_available_symbolsLabels}
        "{$labels}"{if !$smarty.foreach.symbolsLabelsList.last},{/if}
    {/foreach});
  /*]]>*/
</script>
