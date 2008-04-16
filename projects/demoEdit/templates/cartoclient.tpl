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
      <img src="{r type=gfx}spacer.gif{/r}" width="250" height="1"/>
      <input type="submit" name="refresh" value="refresh" class="form_button" />
      <input type="submit" name="reset_session" value="reset_session" class="form_button" 
            onclick="javascript:document.carto_form.posted.value=0;FormItemSelected();"/>
            
        </p>
        <div>
          <ul id="tabnav1">
            <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
            
            <li id="label4"><a href="javascript:ontop(4)">{t}Edit{/t}</a></li>

            <li id="label5"><a href="javascript:ontop(5)">{t}Help{/t}</a></li>
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

        <div id="folder5" class="folder">

          <p><i>{t}HowTo use the Plugin Edition{/t}</i></p>

          <div class="table_help"><img src="{r type=gfx/help}tab.gif{/r}" alt="{t}Themes tab{/t}" /><strong>&nbsp;{t}Edit tab{/t}</strong></div>
          <div>
<p>{t}First, you have to log in since only authorised users have access to the edition options.{/t}</p>

<p>{t}You can only edit one layer at a time. In the demo, there are three different editable layers, one for each type of geometry. Select the layer to edit in the dropdown menu.{/t}</p>
<p class="help_h1">{t}Polygon{/t}</p>

<p class="help_h2">{t}Selecting a Polygon{/t}</p>

<ul>
  <li>{t}Click on the {/t}<img src="{r type=gfx plugin=edit}edit_sel.gif{/r}" title="{t}edit_sel{/t}" alt="" />{t} icon.{/t}</li>
  <li>{t}Click on the polygon to select.{/t}</li>
</ul>

<p>{t}You can also click-and-drag to select all polygons within an area.{/t}</p>

<p>{t}Selected polygons attribute values will appear below the map.{/t}</p>

<p>{t}You will probably notice the radio-button and the zoom-like icon on the right of the table.{/t}</p>

<p>{t}If you have several polygons selected, the radio-button will allow you to activate a polygon so that you can edit its features on the map (move or delete the polygon, move, add or remove vertex). The selected polygon is hilighted in on the map.{/t}</p>

<p>{t}You can even navigate while editing. For example, you can zoom in to edit more precisely.{/t}</p>

<p class="help_h2">{t}Edit the information of an existing polygon{/t}</p>

<ul>
  <li>{t}Select a polygon.{/t}</li>
  <li>{t}Edit the values in the table.{/t}</li>
  <li>{t}Click on the Validate button to save the new values.{/t}</li>
</ul>

<p class="help_h2">{t}Creating a New Polygon{/t}</p> 

<ul>
  <li>{t}Click on the {/t}<img src="{r type=gfx plugin=edit}edit_polygon.gif{/r}" title="{t}edit_polygon{/t}" alt="" />{t} icon.{/t}</li>
  <li>{t}Click on the map to draw each vertex of your polygon.{/t}</li>
  <li>{t}To close your polygon, double-click with the mouse left button, or click on the first vertex of the polygon.{/t}</li>
</ul>

<p>{t}To have two polygons totally side by side, check the Vertex Snapping checkbox (magnet icon).
This is useful to prevent extra spacing between consecutive polygons.{/t}</p>
<ul>
  <li>{t}First, select the existing polygon beside which you will create a new one, using the select tool.{/t}</li>
  <li>{t}When you start creating a new polygon next to an existing polygon, click on the first polygon vertex along the matching side, the new polygon vertex will be positionned exactly over the existing polygon vertex.{/t}</li>
  <li>{t}Click on the Validate button to save the new polygone.{/t}</li>
</ul>

<p class="help_h2">{t}Deleting a Polygon{/t}</p>

<p>{t}To delete a polygon, you need to have at least one selected polygon. See section "Selecting a polygon".{/t}</p>
<ul>
  <li>{t}Once you have a selected polygone, click on the {/t}<img src="{r type=gfx plugin=edit}edit_del_feature.gif{/r}" title="{t}edit_del_feature{/t}" alt="" />{t} icon.{/t}</li>
  <li>{t}Now click on the polygon you want to delete, to activate it. Click a second time to trigger the deletion.{/t}</li>
  <li>{t}A warning will be displayed to confirm the deletion. If you select "ok", you will notice that the polygon table entries have been struck.{/t}</li>
  <li>{t}Click on the Validate button to delete the polygone and all related information from the database.{/t}</li>
</ul>

<p>{t}If you click on the Cancel button, all the modifications not validated are canceled.{/t}</p>

<p class="help_h2">{t}Moving a Polygon{/t}</p>

<p>{t}To move a polygon, you need to have at least one selected polygon. See section "Selecting a polygon".{/t}</p>
<ul>
  <li>{t}Click on the {/t}<img src="{r type=gfx plugin=edit}edit_move.gif{/r}" title="{t}edit_move{/t}" alt="" />{t} icon.{/t}</li>
  <li>{t}Click on the polygon you want to move to activate it.{/t}</li>
  <li>{t}Click and drag to the new position.{/t}</li>
  <li>{t}Click on the Validate button to definitively set the polygon position.{/t}</li>
</ul>

<p class="help_h2">{t}Editing a Polygon Vertex{/t}</p>

<p>{t}To edit a polygon vertex, you need to have at least one selected polygone. See section "Selecting a polygon".{/t}</p>

<ul>
  <li>{t}Moving a Vertex{/t}</li>
  <ul>
    <li>{t}Select the {/t}<img src="{r type=gfx plugin=edit}edit_move.gif{/r}" title="{t}edit_move{/t}" alt="" />{t} icon.{/t}</li>
    <li>{t}Click on the polygone to activate it.{/t}</li>
    <li>{t}Click and drag the vertex you want to move.{/t}</li>
  </ul>
  <li>{t}Adding a Vertex{/t}</li>
  <ul>
    <li>{t}Select the {/t}<img src="{r type=gfx plugin=edit}edit_add_vertex.gif{/r}" title="{t}edit_add_vertex{/t}" alt="" />{t} icon.{/t}</li>
    <li>{t}Click on the polygon to activate it.{/t}</li>
    <li>{t}Click precisely over the spline (border) where you want to have the new vertex.{/t}</li>
  </ul>
  <li>{t}Deleting a Vertex{/t}</li>
  <ul>
    <li>{t}Select the {/t}<img src="{r type=gfx plugin=edit}edit_del_vertex.gif{/r}" title="{t}edit_del_vertex{/t}" alt="" />{t} icon.{/t}</li>
    <li>{t}Click on the polygon to activate it.{/t}</li>
    <li>{t}Click on the vertex to delete.{/t}</li>
  </ul>
  <li><strong>{t}Validate!{/t}</strong></li>
</ul>

<p>{t}You can combine the move, add and delete vertex actions before validating.
Click on the Validate button to validate the polygon vertex modifications.{/t}</p>

<p class="help_h2">{t}Cancel{/t}</p>

<p>{t}If you need to cancel drawings or to get back to a classic navigation, click on the cancel button.{/t}</p>

<p class="help_h2">{t}Lines and Points{/t}</p>

<p>{t}Lines and Points editions are similar to polygon edition.{/t}</p>

           
          </div>
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
          <tr><td colspan="3"><div id="floatScale" class="locationInfo">{t}Current scale:{/t} 1:{$currentScale_value}</div></td></tr>
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
                <td width="50%"><div id="floatDistance" class="locationInfo" nowrap="nowrap">{t}Approx. distance :{/t}%s{if $factor == 1000} km{else} m{/if}</div>
                  <div id="floatSurface" class="locationInfo">{t}Approx. area :{/t} %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
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
