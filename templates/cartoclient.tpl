<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="{$project_css_style}" title="stylesheet" />
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  <title>{$cartoclient_title}</title>

  {literal}
  <script language="Javascript" type="text/javascript">
  <!--
    function FormItemSelected() {
      document.carto_form.submit();
    }
  
    function CheckRadio(theIndex) {
      document.carto_form.tool[theIndex].checked = true;
    }
  //-->
  </script>
  
  <!-- BEGIN dhtmlHeader -->
  {/literal}
  <link rel="stylesheet" type="text/css" href="{$project_css_dhtml_tools}" title="stylesheet" />
  {literal}  
  <script type="text/javascript" src="js/x_core_nn4.js"></script>
  <script type="text/javascript" src="js/x_dom_nn4.js"></script>
  <script type="text/javascript" src="js/x_event_nn4.js"></script>
  <script type="text/javascript" src="js/navTools.js"></script>
  <script type="text/javascript" src="js/graphTools.js"></script>
  <script type="text/javascript">
    /*<![CDATA[*/ 
    var dhtmlDivs = new String();
    document.image = new Image;
    {/literal}document.image.src = '{$mainmap_path}';{literal}
    
    if (xIE) {
      dhtmlDivs = '<div id="mapImageDiv" class="dhtmldiv" style="background-image:url('; 
      dhtmlDivs += document.image.src;
      dhtmlDivs += ');visibility:hidden;background-repeat:no-repeat;"></div>';
    } else {
      dhtmlDivs = '<div id="mapImageDiv" class="dhtmldiv" style="visibility:hidden"><img ';
      {/literal}
      dhtmlDivs += 'src="' + document.image.src + '" alt="{$mainmap_alt}" title="" ';
      dhtmlDivs += 'width="{$mainmap_width}" height="{$mainmap_height}" /></div>';
      {literal}
    }
    dhtmlDivs += '<div id="myCanvasDiv" class="dhtmldiv"></div>';
    dhtmlDivs += '<div id="myCanvas2Div" class="dhtmldiv"></div>';
    dhtmlDivs += '<div id="mainDHTMLDiv" class="dhtmldiv"></div>';
    dhtmlDivs += '<div id="diplayContainerDiv" class="dhtmldiv">';
    dhtmlDivs += '<table border="0" width="100%" cellspacing="0" cellpadding="0"><tr>';
    dhtmlDivs += '<td width="50%"><div id="displayCoordsDiv" class="dhtmlDisplay"></div></td>';
    dhtmlDivs += '<td align="right" width="50%"><div id="displayMeasureDiv" class="dhtmlDisplay"></div></td>';
    dhtmlDivs += '</tr></table></div>';
    document.write(dhtmlDivs);
                  
    function dboxInit()
    {
      myform = document.forms['carto_form'];
      // DHTML drawing and navigating tools
      dhtmlBox = new dhtmlBox();
          
      //DHTML parameters
      dhtmlBox.dispPos = 'bottom';
      dhtmlBox.thickness = 2;
      dhtmlBox.cursorsize = 4;
      dhtmlBox.jitter = 10; // minimum size of a box dimension
      dhtmlBox.d2pts = 3;   // the distance between two points (measure tools);
      dhtmlBox.nbPts = 5;   // number of points for the last vertex
          
      // map units values
      {/literal}dhtmlBox.mapHeight = {$mainmap_height};{literal}
      dhtmlBox.boxx = -180;
      dhtmlBox.boxy = -90;
      dhtmlBox.pixel_size = 0.9;
      dhtmlBox.dist_msg = '{DIST_MSG}';
      dhtmlBox.dist_unit = ' {DIST_UNIT}';
      dhtmlBox.surf_msg = '{SURF_MSG}';
      dhtmlBox.surf_unit = ' {SURF_UNIT}';
      dhtmlBox.coord_msg = 'Coords: ';
          
      dhtmlBox.initialize();
    }
      
    window.onload = function() {
      dboxInit();
      xHide(xGetElementById('mapAnchorDiv')); 
    }
    /*]]>*/
  </script>
  <!-- END dhtmlHeader -->
  {/literal}
</head>

<body>

<div id="banner"><h1>{$cartoclient_title}</h1></div>

<form method="post" action="{$smarty.server.PHP_SELF}" name="carto_form">
  <input type="hidden" name="posted" value="true" />

  <div id="leftbar">    

  {if $keymap_path|default:''}
  <div id="keymap"><img src="{$keymap_path}" 
  alt="{$keymap_alt}" width="{$keymap_width}" height="{$keymap_height}" title="" /></div>
  {/if}

  {$layers}

  <p>
    <input type="submit" name="refresh" value="refresh" class="form_button" /><br />
    <input type="submit" name="reset_session" value="reset_session" class="form_button" />
  </p>

  {if $hello_active|default:''}
  <p>Hello plugin test:</p>
  <p><input type="text" name="hello_input" /></p>
  {/if}

  {if $outliner_active|default:''}
  <p>Outliner plugin:</p>
  <p>{html_checkboxes name="outliners" options=$outliners selected=$selected_outliners separator="<br />"}</p>
  {/if}

  </div>

  <div id="content">

  <p>
    {foreach from=$tools key=toolcode item=toolname}
    <label for="{$toolcode}"><input type="radio" name="tool" value="{$toolcode}" {if $selected_tool == $toolcode}checked="checked"{/if} 
    id="{$toolcode}" onclick="dhtmlBox.changeTool('{$toolcode}')" /><img src="gfx/layout/{$toolcode}.gif" alt="{$toolname}" 
    onclick="CheckRadio('{$toolcode}');dhtmlBox.changeTool('{$toolcode}')" /></label>
    {/foreach}   
  </p>

    <table>
      <tr>
        <td><input type="image" src="{$project_gif_north_west}" name="pan_nw" alt="NW" /></td>
        <td align="center"><input type="image" src="{$project_gif_north}" name="pan_n" alt="N" /></td>
        <td><input type="image" src="{$project_gif_north_east}" name="pan_ne" alt="NE" /></td>
      </tr>
      <tr>
        <td><input type="image" src="{$project_gif_west}" name="pan_w" alt="W" /></td>
        <td>
          <input type="hidden" name="selection_type" />
          <input type="hidden" name="selection_coords" />
          <div id="mapAnchorDiv" style="position:relative;width:{$mainmap_width};height:{$mainmap_height};"> 
            <table width="{$mainmap_width}" height="{$mainmap_height}">
              <tr>
                <td align="center" valign="middle">LOADING MESSAGE<br /><img 
                src="gfx/layout/loadingbar.gif" width="140" height="10" alt="" /></td>
              </tr>
            </table>
          </div>
        </td>
        <td><input type="image" src="{$project_gif_east}" name="pan_e" alt="E" /></td>
      </tr> 
      <tr>
        <td><input type="image" src="{$project_gif_south_west}" name="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{$project_gif_south}" name="pan_s" alt="S" /></td>
        <td><input type="image" src="{$project_gif_south_east}" name="pan_se" alt="SE" /></td>
      </tr>
      {if $scalebar_path|default:''}
      <tr><td align="center" colspan="3"><img src="{$scalebar_path}" 
      alt="{$scalebar_alt}" width="{$scalebar_width}" height="{$scalebar_height}" title="" /></td></tr>
      {/if}
    </table>

  <p> LocationInfo: {$location_info} </p>

  {if $query_result|default:''}
  <h1>Results: </h1>
  {$query_result}
  {/if}

<pre>
Request:
{$debug_request}
<div class="separator"></div>
ClientContext:
{$debug_clientcontext}
</pre>

  <p>&copy; <a href="http://camptocamp.com/" target="_blank">Camptocamp SA</a> -
  <a href="http://validator.w3.org/check/referer" target="_blank">XHTML Validator</a></p>
  
  </div>

</form>

</body>
</html>
