<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}folders.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />
  {if $collapsibleKeymap|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}keymap.css{/r}" />{/if}
  <title>{t}CartoWeb3 - Demonstration{/t}</title>

  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $exportPdf|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>{/if}
  {if $layerReorder|default:''}<script type="text/javascript" src="{r type=js plugin=layerReorder}layerReorder.js{/r}"></script>{/if}
  {if $outline_active|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=outline}outline.css{/r}" />{/if}
  {if $collapsibleKeymap|default:''}<script type="text/javascript" src="{r type=js}keymap.js{/r}"></script>
  <script language="JavaScript" type="text/javascript">
    <!--
    var hideKeymapMsg = "{t}Collapse keymap{/t}";
    var showKeymapMsg = "{t}Show keymap{/t}";
    var hideKeymap = {$collapseKeymap};
    //-->
  </script>
  {/if}
  
  {include file="dhtmlcode.tpl"}
</head>

<body>

<!-- header begins here -->
<table width="100%">
<tr><td colspan="2">
<table id="topbanner" border="0"  cellpadding="0" cellspacing="0">
  <tr>
    <td id="logo"><img src="{r type=gfx/layout}logoc2c.gif{/r}" alt="camptocamp" border="0"/></td>
    <td id="title" nowrap="nowrap">{t}CartoWeb3 - Demonstration{/t}</td>
    <td align='right' width="1%">
      <table>
      <tr>
        <td align='right'>
        {foreach from=$locales item=lang}
        {if $lang == $currentLang}
        <img class="lang_on" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" />
        {else}
        <a href="javascript:document.carto_form.action='{$selfUrl}?lang={$lang}';FormItemSelected();" onclick="javascript:doSubmit();"><img class="lang_off" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" /></a>
        {/if}
        {/foreach}</td>
      </tr>
      <tr>
        <td class="mini" align='right' nowrap="nowrap">
        {if $auth_active|default:''}
        {if $username|default:''}{t}welcome{/t} {$username} - {/if}
        {$auth}
        {/if}</td>
      </tr>
      </table>
    </td>
  </tr>
</table>
</td></tr>

<tr><td>
<!-- header ends here -->

<form method="post" action="{$selfUrl}" name="carto_form" onsubmit="doSubmit();">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  <input type="hidden" name="features" />
  <input type="hidden" name="project" value="{$project}" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
  <input type="hidden" id="fake_reset" name="fake_reset" />
  <input type="hidden" id="fake_query" name="fake_query" />
  {if $outline_active|default:''}
  {$outlinelabel}
  {/if}
  <div id="content">
    <table id="mapframe" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="3" id="toolbar_row" nowrap="nowrap">
          {include file="toolbar.tpl" group="1" header="1"}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          {include file="toolbar.tpl" group="2"}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          {include file="toolbar.tpl" group="3"}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          {include file="toolbar.tpl" group="4"}
          <a href="javascript:ontop(2);">
            <img src="{r type=gfx/layout/help}fileprint.gif{/r}"
                 title="{t}Print{/t}" alt="{t}print{/t}" /></a>
        </td>
      </tr>
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
        <td></td>
        <td>
          <table width="100%"><tr>
            <td width="50%"><div id="floatGeo" class="locationInfo">{t}Coordonnees (m):{/t} %s / %s</div></td>
            <td width="50%" align="right"><div id="floatDistance" class="locationInfo">{t}Distance approx.: {/t}%s{if $factor == 1000} km{else} m{/if}</div>
              <div id="floatSurface" class="locationInfo">{t}Surface approx. : {/t} %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
          </tr></table>
        </td>
        <td></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" alt="S" /></td>
        <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" alt="SE" /></td>
      </tr>
      <tr>
        <td  colspan="3"><br /></td>
      </tr>
      <tr>
       <td></td>
       <td align="center">
         <table border="0" cellpadding="0" cellspacing="0" width="100%">
           <tr>
             <td colspan="3" valign="top" align="center" width="80%">
               {if $scalebar_path|default:''}
               <img src="{$scalebar_path}" 
               alt="{t}scalebar_alt{/t}" width="{$scalebar_width}"
               height="{$scalebar_height}" title="" />
               {/if}
             </td>
           </tr>
           <tr>
             <td width="10%" align="center">
               {if $scales_active|default:''}
                 {$scales}
               {/if}
             </td>
             <td width="80%"></td> 
             <td width="10%" align="center">
               {if $mapsizes_active|default:''}
                 {$mapsizes}
               {/if}
             </td>
           </tr>
         </table>
       </td>
       <td></td>
       </tr>
       <tr>
         <td  colspan="3"><br /></td>
       </tr>
         
       {if $tables_result|default:''}
       <tr>
         <td colspan ="3">
         <table style="border:1px solid black;" width="100%">
           <tr>
             <td>
               <center>
                 {$tables_result}
               </center>
             </td>
           </tr>
         </table>
         </td>
       </tr>  
       {/if}
       {if $developer_messages|default:''}
       <tr>
       <td colspan="3" align="center">
       <table id="user_message" width="100%" border="0" cellpadding="0" cellspacing="0">
       <tr>
            <th align="left" class="messages"><span style="color: green; border: 10px; background-color: yellow;">{t} Developer messages {/t}
            </span></th>
       </tr>
       <tr>
         <td>
            <ul>
              {foreach from=$developer_messages item=message}
              <li>{$message}</li>
              {/foreach}   
            </ul>
         </td>
       </tr> 
       </table>
       </td>
       </tr>
       {/if}
       
       {if $user_messages|default:''}
       <tr>
       <td colspan="3" align="center">
        <table id="user_message" width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <th align="left" class="messages">{t}User messages{/t}</th>
          </tr>
          <tr>
        <td>
            <ul>
                  {foreach from=$user_messages item=message}
                    <li>{$message}</li>
                  {/foreach}   
            </ul>
         </td>
       </tr> 
    </table>
       </td>
      </tr>
      {/if}
  <tr>
    <td  colspan="3"><br /></td>
  </tr>
  <tr>
    <td colspan="3" align="center" class="mini">
      Powered by <a href="http://www.cartoweb.org/" target="_blank">CartoWeb 3.2</a>
          &copy; <a href="http://www.camptocamp.com/"  target="_blank">Camptocamp SA</a>
    </td>
  </tr>
</table>
</div>

  <div id="leftbar">
    <div>
      <ul id="tabnav2">
        <li id="label2"><a href="javascript:ontop(2)">{t}Print{/t}</a></li>
        <li id="label4"><a href="javascript:ontop(4)">{t}About{/t}</a></li>
        <li id="label6"><a href="javascript:ontop(6)">{t}Help Viewer{/t}</a></li>
      </ul>
      <ul id="tabnav1">
        <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
        <li id="label3"><a href="javascript:ontop(3)">{t}Search{/t}</a></li>
        <li id="label5"><a href="javascript:ontop(5)">{t}Outline{/t}</a></li>
        <li id="label7"><a href="javascript:ontop(7)">{t}Query{/t}</a></li>
      </ul>

   </div>


    <div id="container">
    <!-- folder 1 starts here -->
    <div id="folder1" class="folder">
      <br />
      {$layers}
      <br />
      <p style="text-align:right; vertical-align:middle;">

        <a href="javascript:ontop(6);">
         <img src="{r type=gfx/layout}help.gif{/r}"
              title="{t}Help{/t}" alt="{t}help{/t}" 
              style="margin-bottom:3px;"/></a>
        &nbsp;&nbsp;
        <a href="javascript:resetSession();">
          <img src="{r type=gfx/layout}reset_session.gif{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" style="padding-bottom:3px;"/>
        </a>&nbsp;&nbsp;
        <input type="submit" id="refresh" name="refresh" value="{t}OK{/t}" 
        class="form_button" style="margin-bottom:7px;" />
      </p>
    </div>
    
    <!-- end of folder 1 -->

    <!-- folder 5 starts here -->
    <div id="folder5" class="folder">
      <br />
      {if $outline_active|default:''}
      {$outline}
      {/if}
    </div>
    <!-- end of folder 5 -->

    <!-- folder 4 starts here -->
    <div id="folder4" class="folder">
      <br />
      <fieldset>
       <legend>{t}Data sources{/t}</legend>
       <ul>
        <li>{t}Nima :{/t} <a target="_blank" href="http://geoengine.nima.mil/geospatial/SW_TOOLS/NIMAMUSE/webinter/rast_roam.html">Vmap0</a></li>
        <li>{t}Gtopo30 :{/t} <a  target="_blank" href="http://edc.usgs.gov/products/elevation/gtopo30/gtopo30.html">W020N90</a></li>
       </ul>
      </fieldset>

      <fieldset>
       <legend>{t}Projection and datums{/t}</legend>
       <ul>
        <li>{t}UTM 32 North{/t}</li>
        <li>{t}WGS 84{/t}</li>
        <li>{t}EPSG 32632{/t}</li>
       </ul>
      </fieldset>
    </div>
    <!-- end of folder 4 -->
  
    <!-- folder 3 starts here -->
    <div id="folder3" class="folder">
      <br />
    
      {if $hello_active|default:''}
      <p>Hello plugin test: <br />
      {$hello_message} <br />
      <input type="text" name="hello_input" /></p>
      {/if}
    
      {if $shortcuts_active|default:''}
      {$shortcuts}
      {/if}
    
      {if $recenter_active|default:''}
      {$recenter}
      {/if}
      
      {if $id_recenter_active|default:''}
      {$id_recenter}
      {/if}
    
      {if $exporthtml_active|default:''}
      <a href="{$exporthtml_url}" target="print">{t}Print{/t}</a>
      {/if}

    </div>
    <!-- end of folder 3 -->
  
    <!-- folder 2 starts here -->
    <div id="folder2" class="folder">
      <br />
        {if $exportPdf|default:''}
          {$exportPdf}
        {else}
          <p>
            {t}You are not allowed to print maps{/t},
            {if $auth_active|default:''}
             {t}please{/t} {$auth}.
            {/if}
          </p>
        {/if}
    </div>
    <!-- end of folder 2 -->
    
    <!-- folder 6 starts here -->
    <div id="folder6" class="folder" style="height:550px;">
    <p><i>{t}This demo is an overview of the standard functionalities that are somehow visible for an end-user in Cartoweb. To get the most out of it, read through this Help guide before starting to explore.{/t}</i></p>
    
    <br />
    
    <table class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}tab.gif{/r}" alt="{t}Themes tab{/t}" /><strong>&nbsp;{t}Themes tab{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer">
        {t}Cartoweb supports an arbitrarily complex hierarchy of layers, with indefinite path.
        The layers which are checked and so selected, are displayed.{/t}<br /><br />
        <img src="{r type=gfx/layout/help}refresh.gif{/r}" alt="chargement du logo refresh" />
        {t}Once you have chosen your data, click on the OK button, located beneath the tab, to update your changes{/t}
    </p><br />
    
    
    <table  class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}tab.gif{/r}"   alt="{t}Search tab{/t}" /><strong>&nbsp;{t}Search tab{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer">
    {t}This tab allows you to move the center of the viewing area on a desired location. You can choose this location by three differents ways :{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}to select a shortcut to a specific location,{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}to entering the coordinates in the box X and Y (to start the search type on the touch {/t}<img src="{r type=gfx/layout/help}key_enter.gif{/r}"  alt="chargement du logo key_enter" />),<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}to do a search on the name. For it, please select the data on which you are interested in, type the word or the begining of the word you think correspond to a feature of the data and follow the instructions.{/t}</p><br />
    
        
    <table class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}tab.gif{/r}" alt="{t}Outline tab{/t}" /><strong>&nbsp;{t}Outline tab{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer">
    {t}In Cartoweb, it is possible to freely draw points, lines, rectangles and polygons on the map, and to attach labels to them. These features are persistent: they survive panning or zooming.
    This tab allows you to obtain informations on the drawn features and to switch on the mask mode, in which everything but the outlined polygon is masked, is provided too.{/t}
    <br /><br /><img src="{r type=gfx/layout/help}outline_point.gif{/r}"  alt="{t}outline_point{/t}" />
    <img src="{r type=gfx/layout/help}outline_line.gif{/r}"  alt="{t}outline_line{/t}" />
    <img src="{r type=gfx/layout/help}outline_rectangle.gif{/r}" alt="{t}outline_rectangle{/t}" />
    <img src="{r type=gfx/layout/help}outline_poly.gif{/r}" alt="{t}outline_polygon{/t}" />
    {t}To draw the desired features, you might use outline tools.{/t}</p><br />
    
    <table class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}tab.gif{/r}" alt="{t}Query tab{/t}" /><strong>&nbsp;{t}Query tab{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer">
    <img src="{r type=gfx/layout/help}query.gif{/r}" alt="{t}Query{/t}" />
    {t}Using the query tool, you can geographically search for objects. Found objects are hilighted and their attributes are displayed.{/t}<br />
    {t}The queries are persistent (i.e. you can add new objects to already selected objects).
    Note : {/t}<i>{t}only the layers which are specified questionnable by the administrator of the website are concerned by the query.{/t}</i><br /><br />
    {t}This tab allows you to parametrize your search. For each layers, which can be interrogated, the following options can be set :{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Hilight{/t}</span>{t} : allows to activate/desactivate features hilight;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Attributes{/t}</span>{t} : if it's checked, the layers attributes can be requested. Instead, only object IDs will be returned;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Table{/t}</span>{t} : allows to display the query result;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Union selection{/t}</span>{t} : when selecting a group of objects, already selected ones are kept selected and no yet selected ones are selected;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Xor{/t}</span>{t} : when selecting a group of objects, already selected ones are unselected and no yet selected ones are selected (defaut type);{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Intersection{/t}</span>{t} : when selecting a group of objects, only already selected are kept selected;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}InQuery{/t}</span>{t} : if it's checked, you force query to use this layer;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" /><span class="s">{t}Mask{/t}</span>{t} : if it's checked, you apply a mask instead of a simple selection. This don't work when using highlight mecanism;{/t}<br />
    <br /><i>{t}If "Query all selected layers" option is checked, the query will handle all the questionnable layers{/t}</i>
    </p><br />
    
    <table  class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}tab.gif{/r}" alt="{t}Print tab{/t}" /><strong>&nbsp;{t}Print tab{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer">
    {t}Cartoweb is able to output a PDF document. You can choose in this dialog tab, the elements you want to display (scalebar, overview, query results, legend) and specify additional options (format and resolutiion, orientation, title, note).{/t}
    </p><br />
    
    <table  class="table_help" border="0" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}tab.gif{/r}" alt="{t}About tab{/t}" /><strong>&nbsp;{t}About tab{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer">
    {t}This tab give you some informations on the data and the projections. 
    All the data are royalty-free. Nima refers to the datasource for the vector background and Gtopo30 refers to the datasource for the raster background.{/t}
    </p><br />
    
    <table  class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <img src="{r type=gfx/layout/help}globe.gif{/r}"  alt="{t}Globe{/t}" /><strong>&nbsp;{t}Mapping tools{/t}</strong><br />
      </td></tr>
    </table>
    
    <p class="help_viewer"><strong>{t}Navigation interface{/t}</strong><br />
    {t}There are many possibilities to navigate on the main map, that is to change the scale and the position{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}the arrows surrounding the map;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}the dynamic (i.e. clickable) keymap;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}the navigation tools (zoom and pan), which are detailled below;{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}the drop down menu "Scale";{/t}<br />
    <img src="{r type=gfx/layout/help}endturn.gif{/r}" alt="{t}pet{/t}" />{t}the various options in the "Search" tab.{/t}<br />
    {t}The menu "Mapsize" is self- explanatory.{/t}<br/><br />
    <img src="{r type=gfx/layout/help}zoomin.gif{/r}" alt="{t}zoomin{/t}" />
    {t}The Zoom In tool allows you to focus on a specific, smaller region of the Europe area. Click the Zoom In button and then either click the map near the center of the region you are interested in or click-and-drag a rectangle surrounding the area. The Zoom In tool can be used multiple times to move closer and closer to desired regions.{/t}<br />
    <img src="{r type=gfx/layout/help}zoomout.gif{/r}" alt="{t}zoomout{/t}" />
    {t}The Zoom Out tool allows you to focus on a larger area of the map than is currently displayed. Click the Zoom Out button and then click the map near the center of the larger region you are interested in. The Zoom Out tool can be used multiple times to view larger and larger regions.{/t}<br />
    <img src="{r type=gfx/layout/help}pan.gif{/r}" alt="{t}pan{/t}" />{t}The Pan Map tool allows you to move the center of the viewing area without changing the scale of the map. Click the Pan Map button and then click and drag the map image to recenter it.{/t}<br />
    <img src="{r type=gfx/layout}fullextent.gif{/r}" alt="{t}fullextent{/t}" />{t}The Zoom to Full Extents tool allows you to quickly reset the current map view to the entire Europe area. There is no need to click on the map to activate this tool - it is activated as soon as you click the Zoom to Full Extents button.{/t}</p><br />
    
    <p class="help_viewer"><strong>{t}Measuring tools{/t}</strong><br />
    <img src="{r type=gfx/layout/help}distance.gif{/r}" alt="{t}distance{/t}" /><img src="{r type=gfx/layout/help}surface.gif{/r}" alt="{t}area{/t}" />
    {t}Distances and surfaces can be measured on the main map with the above tools.{/t}</p><br />
    
    <table  class="table_help" cellpadding="0" cellspacing="0">
      <tr><td>
        <strong>{t}Others tools{/t}</strong><br />
      </td></tr>
    </table>
    <p class="help_viewer"><img src="{r type=gfx/layout/help}internationalization.gif{/r}" alt="{t}internationalization{/t}" />
    {t}The internationalization tools allow you to switch langage (Cartoweb uses gettext for the translation).{/t}
    <br /><br /><img src="{r type=gfx/layout/help}login.jpg{/r}" alt="{t}login{/t}" />&nbsp;{t}Access to different elements of Cartoweb can be allowed or denied according to who is currently using the application. Both functionnalities and data may have access restrictions. For this demo, you can access to more rights by using login 'demo' and password 'demo'. You can then access to the train data and to the print fonctionnalities.{/t} 
    </p>
    <br />
    <hr />
    <p>{t}For more information, you can have a look to the {/t}<a href="http://www.cartoweb.org/documentation.html" target="_blank">{t}Cartoweb documentation{/t}</a></p>
    </div>
    <!-- end of floder 6 -->
    
    <!-- folder 7 starts here -->
    <div id="folder7" class="folder" style="height:550px;">
      <br />
        {if $selection_result|default:''}
        {$selection_result}
        {/if}

        {if $query_result|default:''}
        {$query_result}
        {/if}  
    </div>
    <!-- end of folder 7 -->
    
  </div> <!--container-->
  </div> <!--leftbar-->
</form>
</td></tr>
<tr><td></td></tr>
</table>
</body>
</html>
