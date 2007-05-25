<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>{$cartoclient_title}</title>
  
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />
  {if $collapsibleKeymap|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}keymap.css{/r}" />{/if}
  {if $layerReorder|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layerReorder}layerReorder.css{/r}" />{/if}
  {if $outline_active|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=outline}outline.css{/r}" />{/if}
  {if $toolpicker_active|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}toolPicker.css{/r}" title="stylesheet" />{/if}
  {if $toolTips_active|default:''}
    <link rel="stylesheet" type="text/css" href="{r type=css plugin=toolTips}layerResult_customLayers.css{/r}" title="stylesheet" />
    <script type="text/javascript" src="{r type=js plugin=toolTips}overlib_style.js{/r}"></script>
  {/if}
  
  <link rel="icon" href="{r type=gfx/layout}cw3.png{/r}" type="image/png" />
  
  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $layerReorder|default:''}<script type="text/javascript" src="{r type=js plugin=layerReorder}layerReorder.js{/r}"></script>{/if}
  {if $collapsibleKeymap|default:''}<script type="text/javascript" src="{r type=js}keymap.js{/r}"></script>
    
  <script type="text/javascript">
    <!--
    var hideKeymapMsg = "{t}Collapse keymap{/t}";
    var showKeymapMsg = "{t}Show keymap{/t}";
    var hideKeymap = {$collapseKeymap};
    //-->
  </script>
  {/if}
  {if $jsAccounting|default:''}<script type="text/javascript" src="{r type=js plugin=accounting}accounting.js{/r}"></script>{/if}
  {if $views|default:'' || $viewsList|default:''}<script type="text/javascript" src="{r type=js plugin=views}views.js{/r}"></script>{/if}

  {include file="cartoclient_ajaxHeader.tpl"}
  
  {include file="dhtmlcode.tpl"}

  {if $exportPdf|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>{/if}
  {if $exportPdfRotate|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}dhtmlPdf.js{/r}"></script>{/if}

</head>

<body>
<div id="banner"><h1>{$cartoclient_title}</h1></div>
<form method="post" action="{$selfUrl}" name="carto_form" id="carto_form" onsubmit="doSubmit();">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" id="posted" value="1" />
  <input type="hidden" name="js_folder_idx" id="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" id="selection_type" />
  <input type="hidden" name="selection_coords" id="selection_coords" />
  <input type="hidden" name="features" id="features" />
  {if $collapsibleKeymap|default:''}
    <input type="hidden" name="collapse_keymap" id="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
  {if $jsAccounting|default:''}
    <input type="hidden" name="js_accounting"/>
  {/if}
  {if $outline_active|default:''}
    {$outlinelabel}
  {/if}
  <div id="content">

    {include file="toolbar.tpl" group=1 header=1}
    {include file="toolbar.tpl" group=2}
    {if $toolTips_active|default:''}
        <label for="toolTipsSwitch">ToolTips active</label>
        <input type="checkbox" name="toolTipsSwitch" id="toolTipsSwitch" checked="checked" />
    {/if}

    <table>
      <tr><td colspan="3"><div id="floatScale" class="locationInfo">{t}Current scale:{/t} <span id="currentScale">1:{$currentScale}</span></div></td></tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}north_west.gif{/r}" name="pan_nw" id="pan_nw" alt="NW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}north.gif{/r}" name="pan_n" id="pan_n" alt="N" /></td>
        <td><input type="image" src="{r type=gfx/layout}north_east.gif{/r}" name="pan_ne" id="pan_ne" alt="NE" /></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}west.gif{/r}" name="pan_w" id="pan_w" alt="W" /></td>
        <td valign="top">
          {include file="mainmap.tpl"}
        </td>
        <td><input type="image" src="{r type=gfx/layout}east.gif{/r}" name="pan_e" id="pan_e" alt="E" /></td>
      </tr> 
      <tr>
        <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" id="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" id="pan_s" alt="S" /></td>
        <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" id="pan_se" alt="SE" /></td>
      </tr>
      <tr>
        <td colspan="3">
          <table width="100%"><tr>
            <td width="50%">
              <div id="floatGeo" class="locationInfo">{t}Coord (m):{/t} %s / %s</div>
            </td>
            <td width="50%">
              <div id="floatDistance" class="locationInfo"><span id="distanceValueLabel">{t}Approx. distance :{/t}</span> %s{if $factor == 1000} km{else} m{/if}</div>
              <div id="floatSurface" class="locationInfo"><span id="surfaceValueLabel">{t}Approx. area :{/t}</span> %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
          </tr></table>
        </td>
      </tr>
      {if $scalebar_path|default:''}
      <tr><td align="center" colspan="3"><img src="{$scalebar_path}" id="scalebar" 
      alt="{t}scalebar_alt{/t}" width="{$scalebar_width}" height="{$scalebar_height}" title="" /></td></tr>
      {/if}
    </table>

  <p>Powered by <a href="http://www.cartoweb.org/" target="_blank">CartoWeb {$cw_version.version}</a> -
  &copy; <a href="http://camptocamp.com/" target="_blank">Camptocamp SA</a> -
  <a href="http://validator.w3.org/check/referer" target="_blank">XHTML Validator</a></p>
  
  </div>

  <div id="leftbar">    
    {if $locales|default:''}
    <p>
      {foreach from=$locales item=locale name=lang}
      {if !$smarty.foreach.lang.first || !$smarty.foreach.lang.last}
        {if $locale != $currentLang}<a href="javascript:document.carto_form.action='{$selfUrl}?lang={$locale}';FormItemSelected();">{$locale}</a>{else}<strong>{$locale}</strong>{/if}
        {if !$smarty.foreach.lang.last}|{/if}
      {/if}
      {/foreach}
    </p>
    {/if}

    <p>
         
      <input type="submit" id="refresh" name="refresh" value="{t}refresh{/t}" class="form_button" />
      <input type="submit" name="reset_session" value="{t}reset_session{/t}" class="form_button" 
        onclick="javascript:document.carto_form.posted.value=0;FormItemSelected();"/>
    </p>

    <div id="container">
    
      {if $keymap_path|default:'' && !$collapsibleKeymap|default:''}
      <div id="keymapDiv">
      <input type="image" name="keymap" id="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}" 
      style="width:{$keymap_width}px;height:{$keymap_height}px;" />
      </div>
      {/if}
    
      {if $mapsizes_active|default:''}
      {$mapsizes}
      {/if}
      
      
      {$layers}
  </div>
</div>
   {if $auth_active|default:''}
   {$auth}
   {/if}

</form>

</body>
</html>
