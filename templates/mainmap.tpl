<div id="mapBorder">
  <div id="loadbarDiv" class="dhtmldiv" style="position:absolute;z-index:3;">
    <table style="width:{$mainmap_width}px;height:{$mainmap_height}px;">
      <tr>
        <td align="center" valign="middle"><div id="loadbar">{t}Loading message{/t}<br />
        <img src="{r type=gfx/layout}loadingbar.gif{/r}" width="140" height="10" alt="" /></div></td>
      </tr>
    </table>
  </div>
{if $collapsibleKeymap|default:''}
  <div id="keymapContainer">
    <div id="floatkeymap">
      <input type="image" name="keymap" id="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}" 
      style="width:{$keymap_width}px;height:{$keymap_height}px;" /></div>
    <div id="keymapswitcher">
      <a href="#" onclick="javascript:collapseKeymap();"><img
      src="{r type=gfx/layout}keymap_off.gif{/r}" title="{t}Collapse keymap{/t}"
      alt="" id="switcherimg" /></a>
    </div>
  </div>
{/if}
  <div id="map" class="map" style="width:{$mainmap_width}px;height:{$mainmap_height}px;">
{* nothing here, DHTML API will run with the given class name *}
  </div>
{if $collapsibleKeymap|default:''}
  
{/if}
</div>