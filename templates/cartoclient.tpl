<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" title="stylesheet" />{/if}
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  <title>{$cartoclient_title}</title>

  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  
  <!-- BEGIN dhtmlHeader -->
  <link rel="stylesheet" type="text/css" href="{r type=css}dhtml_tools.css{/r}" title="stylesheet" />
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
      dhtmlDivs += 'src="' + document.image.src + '" alt="{t}Main map{/t}" title="" ';
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
      {/literal}

      {strip}
        {capture name="pixSizeX"}
          {math equation="(maxX - minX) / width"
                maxX=$bboxMaxX minX=$bboxMinX width=$mainmap_width}
        {/capture}
        {capture name="pixSizeY"}
          {math equation="(maxY - minY) / height"
                maxY=$bboxMaxY minY=$bboxMinY height=$mainmap_height}
        {/capture}
        {capture name="pixelSize"}
          {math equation="(pixSizeX + pixSizeY) / (2 * factor)"
                pixSizeX=$smarty.capture.pixSizeX
                pixSizeY=$smarty.capture.pixSizeY
                factor=$factor}
        {/capture}
      {/strip}
     {$pixSizeX} 
      // map units values
      dhtmlBox.mapHeight = {$mainmap_height};
      dhtmlBox.boxx = {$bboxMinX};
      dhtmlBox.boxy = {$bboxMinY};
      dhtmlBox.pixel_size = {$smarty.capture.pixelSize};
      dhtmlBox.dist_msg = '{t}Approx. distance: {/t}';
      dhtmlBox.dist_unit = {if $factor == 1000}' km'{else}' m'{/if};
      dhtmlBox.surf_msg = '{t}Approx. surface: {/t}';
      dhtmlBox.surf_unit = {if $factor == 1000}' km²'{else}' m²'{/if};
      dhtmlBox.coord_msg = '{t}Coords (m): {/t}';
  {literal}
          
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

  <div id="content">

  <p>
    {counter start=-1 print=false name=tindex}
    {foreach from=$tools item=tool}
    <label for="{$tool->id}">
      <input type="radio" name="tool" value="{$tool->id}" 
                {if $selected_tool == $tool->id}checked="checked"{/if} 
                id="{$tool->id}" onclick="dhtmlBox.changeTool('{$tool->jsId}')" />
           {if $tool->icon}
            <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" alt="{$tool->label}" title="{t}{$tool->label}{/t}"
        onclick="CheckRadio('{counter name=tindex}');dhtmlBox.changeTool('{$tool->jsId}')" />
           {else}
            <span onclick="CheckRadio('{counter name=tindex}');dhtmlBox.changeTool('{$tool->jsId}');">
             {$tool->label}
            </span>
           {/if}
    </label>&nbsp;
    {/foreach}   
  </p>

    <table>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}north_west.gif{/r}" name="pan_nw" alt="NW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}north.gif{/r}" name="pan_n" alt="N" /></td>
        <td><input type="image" src="{r type=gfx/layout}north_east.gif{/r}" name="pan_ne" alt="NE" /></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}west.gif{/r}" name="pan_w" alt="W" /></td>
        <td>
          <input type="hidden" name="selection_type" />
          <input type="hidden" name="selection_coords" />
          <div id="mapAnchorDiv" style="position:relative;width:{$mainmap_width};height:{$mainmap_height};"> 
            <table width="{$mainmap_width}" height="{$mainmap_height}">
              <tr>
                <td align="center" valign="middle"><div id="loadbar">{t}Loading message{/t}<br />
                <img src="{r type=gfx/layout}loadingbar.gif{/r}" width="140" height="10" alt="" /></div></td>
              </tr>
            </table>
          </div>
        </td>
        <td><input type="image" src="{r type=gfx/layout}east.gif{/r}" name="pan_e" alt="E" /></td>
      </tr> 
      <tr>
        <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" alt="S" /></td>
        <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" alt="SE" /></td>
      </tr>
      {if $scalebar_path|default:''}
      <tr><td align="center" colspan="3"><img src="{$scalebar_path}" 
      alt="{t}scalebar_alt{/t}" width="{$scalebar_width}" height="{$scalebar_height}" title="" /></td></tr>
      {/if}
    </table>

  <p> LocationInfo: {$location_info} </p>

  {if $selection_result|default:''}
  {$selection_result}
  {/if}

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

  <div id="leftbar">    

  {if $keymap_path|default:''}
  <div id="keymap">
  <input id="keymap" type="image" name="keymap" src="{$keymap_path}" 
  style="width:{$keymap_width}px;height:{$keymap_height}px;" />
  </div>
  {/if}

  {$layers}

  <p>
    <input type="submit" name="refresh" value="refresh" class="form_button" /><br />
    <input type="submit" name="reset_session" value="reset_session" class="form_button" />
  </p>

  {if $hello_active|default:''}
  <p>Hello plugin test: <br />
  {$hello_message} <br/ >
  <input type="text" name="hello_input" /></p>
  {/if}

  {if $recenter_active|default:''}
  {$recenter}
  {/if}

  {if $mapsizes_active|default:''}
  {$mapsizes}
  {/if}

  {if $outliner_active|default:''}
  <p>Outliner plugin:</p>
  <p>{html_checkboxes name="outliners" options=$outliners selected=$selected_outliners separator="<br />"}</p>
  {/if}

  </div>

</form>

</body>
</html>
