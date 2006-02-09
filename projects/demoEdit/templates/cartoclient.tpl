<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>{t}Cartoclient Title{/t}</title>
  
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}folders.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />
  {if $collapsibleKeymap|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}keymap.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css}toolbar.css{/r}" />
  {if $edit_allowed|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=edit}edit.css{/r}" />{/if}

  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $exportPdf|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>{/if}
  {if $layerReorder|default:''}<script type="text/javascript" src="{r type=js plugin=layerReorder}layerReorder.js{/r}"></script>{/if}
  {if $collapsibleKeymap|default:''}<script type="text/javascript" src="{r type=js}keymap.js{/r}"></script>
  <script language="JavaScript" type="text/javascript">
    <!--
    var hideKeymapMsg = "{t}Collapse keymap{/t}";
    var showKeymapMsg = "{t}Show keymap{/t}";
    var hideKeymap = {$collapseKeymap};
    //-->
    
  </script>
  {/if}
  <script type="text/javascript" src="{r type=js}toolbar.js{/r}" ></script>
  {if $views|default:'' || $viewsList|default:''}<script type="text/javascript" src="{r type=js plugin=views}views.js{/r}"></script>{/if}
  
  {include file="dhtmlcode.tpl"}
</head>

<body>
<form method="post" action="{$selfUrl}" name="carto_form">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="js_toolbar_idx" value="{$jsToolbarIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
{if $outline_active|default:''}
  {$outlinelabel}
{/if}
{if $auth_active|default:''}
  {$auth}
{/if}

<table>
  <tr>
    <td valign="top" width="250">
      <input type="submit" name="refresh" value="refresh" class="form_button" />
      <input type="submit" name="reset_session" value="reset_session" class="form_button" 
            onclick="javascript:document.carto_form.posted.value=0;FormItemSelected();"/>
            
        </p>
        <div>
          <ul id="tabnav1">
            <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
            
            <li id="label4"><a href="javascript:ontop(4)">{t}Edit{/t}</a></li>
          </ul>
        </div>
        <div id="container">
          <div id="folder1" class="folder">
    
        
          {if $keymap_path|default:'' && !$collapsibleKeymap|default:''}
          <div id="keymap">
          <input type="image" name="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}" 
          style="width:{$keymap_width}px;height:{$keymap_height}px;" />
          </div>
          {/if}
          
          {$switches}
          {$layers}
        
        </div>
        <!-- end of folder2 -->
        {if $exportPdf|default:''}
        <div id="folder3" class="folder">
          {$exportPdf}
        </div>
        {/if}
        
        <div id="folder4" class="folder">
        {if $edit_active|default:''}
          {include file="../plugins/edit/templates/edit.tpl"}
        {/if}
        </div>
      </div>
      {if $mapsizes_active|default:''}
      {$mapsizes}
      {/if}
    </td>
    <td>
      <div id="toolbar">
        {include file="toolbar.tpl" group=1 header=1}
        {include file="toolbar.tpl" group=2}
        <br />
        {if $edit_allowed|default:'' && $edit_layer_selected}
          {include file="toolbar.tpl" group=3}
          <input type="checkbox" id="snapping" name="edit_snapping" onclick='mainmap.snap("map")' {if $edit_snapping|default:''}checked=checked{/if}/>
          <img src="{r type=gfx plugin=edit}edit_snap.gif{/r}" title="{t}Allow vertex snapping{/t}" alt="{t}Allow vertex snapping{/t}"><br />
        {/if}
      </div>
    
        <table>
          <tr><td colspan="3"><div id="floatScale" class="locationInfo">{t}Current scale:{/t} 1:{$currentScale}</div></td></tr>
          <tr>
            <td><input type="image" src="{r type=gfx/layout}north_west.gif{/r}" name="pan_nw" alt="NW" /></td>
            <td align="center"><input type="image" src="{r type=gfx/layout}north.gif{/r}" name="pan_n" alt="N" /></td>
            <td><input type="image" src="{r type=gfx/layout}north_east.gif{/r}" name="pan_ne" alt="NE" /></td>
          </tr>
          <tr>
            <td><input type="image" src="{r type=gfx/layout}west.gif{/r}" name="pan_w" alt="W" /></td>
            <td valign="top">
              {include file="mainmap.tpl"}
            </td>
            <td><input type="image" src="{r type=gfx/layout}east.gif{/r}" name="pan_e" alt="E" /></td>
          </tr> 
          <tr>
            <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" alt="SW" /></td>
            <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" alt="S" /></td>
            <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" alt="SE" /></td>
          </tr>
          <tr>
            <td colspan="3">
              <table width="100%"><tr>
                <td width="50%"><div id="floatGeo" class="locationInfo">{t}Coords (m):{/t} %s / %s</div></td>
                <td width="50%"><div id="floatDistance" class="locationInfo" nowrap="nowrap">{t}Dist approx.:{/t}%s{if $factor == 1000} km{else} m{/if}</div>
                  <div id="floatSurface" class="locationInfo">{t}Approx. surface :{/t} %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
              </tr></table>
            </td>
          </tr>
        </table>
      {if $edit_allowed|default:''}<div id="edit_div" style="display:none"></div>{/if}
      {if $tables_result|default:''}
      {$tables_result}
      {/if}
    </td>
  </tr>
</table>

</form>


</body>
</html>
