<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" type="text/css" href="css/style.css" title="stylesheet">
        <meta name="author" content="Sylvain Pasche">
        <meta name="email" content="sylvain dot pasche at camptocamp dot com">
        <title>{$cartoclient_title}</title>
    </head>

{literal}
<!-- BEGIN js_coords -->
<script language="Javascript" type="text/javascript">
var mapx = 400;
var mapy = 200;
var boxx = -180;
var boxy = -90;
var pixelx = 0.9;
var pixely = 0.9;
</script>
<!-- END js_coords -->


<script language="Javascript" type="text/javascript">

function FormItemSelected() {
    document.carto_form.submit();
}

function CheckRadio(theIndex) {
        document.carto_form.TOOLBAR_CMD[theIndex].checked=true;
}

</script>

<script type='text/javascript' src='js/x_core_nn4.js'></script>

<!-- BEGIN dhtmlHeader -->
<!-- pgiraud -->
<style type='text/css'><!--
.lineH { position: absolute; background-color: #DF0000; overflow :hidden; width : 2}
.lineW { position: absolute; background-color: #DF0000; overflow :hidden; height : 2}
.point { position: absolute; background-color: #DF0000; overflow: hidden; width: 2; height: 2}
.dhtmldiv {position:absolute; left:0; top:0;width:0;height:0; }
.dhtmlDisplay {position:absolute; left:0; top:0; width:0; height:0; background-color: #EFEFEF;layer-background-color: #EFEFEF padding: 2px 4px;}
--></style>
<script type='text/javascript' src='js/x_dom_nn4.js'></script>
<script type='text/javascript' src='js/x_event_nn4.js'></script>
<script type='text/javascript' src='js/navTools.js'></script>
<script type='text/javascript' src='js/graphTools.js'></script>
<script type='text/javascript'>

 function dboxInit()
  {
    // form used
    myform = document.forms['carto_form'];

    Dhtml_pixel_size = 0.9;
    Dhtml_dist_msg = '{DIST_MSG}';
    Dhtml_dist_unit = ' {DIST_UNIT}';
    Dhtml_surf_msg = '{SURF_MSG}';
    Dhtml_surf_unit = ' {SURF_UNIT}';
    Dhtml_coord_msg = 'Coords: ';

    // the DHTML mapping layers (image + box layers)
    // note that the mapLayer id must be the name of the dBox + 'Div'
    // ie : mainDHTML <-> mainDHTMLDIV
// mainDHTML = new dBox("mapAnchorDiv","mainDHTMLDiv","mapImageDiv","myCanvasDiv","myCanvas2Div","diplayContainerDiv", "bottom", "displayCoordsDiv","displayMeasureDiv", "{COLOR}",{THICKNESS}, {CURSOR}, {JITTER}, {POINTSIZE}, {D2POINTS}, {NBPOINTS});


 mainDHTML = new dBox("mapAnchorDiv","mainDHTMLDiv","mapImageDiv","myCanvasDiv","myCanvas2Div","diplayContainerDiv", "bottom", "displayCoordsDiv","displayMeasureDiv", "#DF0000",2, 4, 10, 2, 3, 5);


    mainDHTML.verbose = true;
    mainDHTML.initialize();

    // make the previous tool selected the current one
    for (var i =0; i < myform.TOOLBAR_CMD.length; i++) {
      if (myform.TOOLBAR_CMD[i].checked) {
        changeTool('mainDHTML',myform.TOOLBAR_CMD[i].value);
      }
    }
  }
   window.onload = function() {
       dboxInit();
       }


</script>
<!-- END dhtmlHeader -->

{/literal}

<body>

<div class="banner">
        <h1>
            {$cartoclient_title}
        </h1>
</div>

<form method="POST" action="{$smarty.server.PHP_SELF}" name="carto_form">
  <input type="hidden" name="posted" value="true">


<div class="leftbar">    

{if $keymap_path|default:''}
    <div align="center">
         <img src='{$keymap_path}'>
    </div>
{/if}

<p>
{$layers}
</p>

<p>
<input type="submit" name="refresh" value="refresh"/>
<input type="submit" name="reset_session" value="reset_session"/>
</p>

{if $hello_active|default:''}
<p>
Hello plugin test:
</p>
<p>
<input type="text" name="hello_input"/>
</p>
{/if}

{if $outliner_active|default:''}
<p>
Outliner plugin:
</p>
<p>
{html_checkboxes name="outliners" options=$outliners selected=$selected_outliners separator="<br />"}
</p>
{/if}

</div>


<div class="content">

<p>
{html_radios name="tool" options=$tools selected=$selected_tool }
</p>

<p>
<table>
<tr>
        <td>
                <input type="image" src="gfx/layout/north_west.gif" name="pan_nw"/>
        <td align="center">
                <input type="image" src="gfx/layout/north.gif" name="pan_n"/>
        <td>
                <input type="image" src="gfx/layout/north_east.gif" name="pan_ne"/>
<tr>
        <td>
                <input type="image" src="gfx/layout/west.gif" name="pan_w"/>
        <td>
        
            <input type="hidden" name="INPUT_TYPE" value="">
            <input type="hidden" name="INPUT_COORD" value="">
            <div id="mapAnchorDiv" style="position:relative;width:400;height:200;clip:rect(0,400,200,0)">
            </div>

        <td>
                <input type="image" src="gfx/layout/east.gif" name="pan_e"/>
<tr>
        <td>
                <input type="image" src="gfx/layout/south_west.gif" name="pan_sw"/>
        <td align="center">
                <input type="image" src="gfx/layout/south.gif" name="pan_s"/>
        <td>
                <input type="image" src="gfx/layout/south_east.gif" name="pan_se"/>
<tr>
        <td/>
      {if $scalebar_path|default:''}
        <td align="center">
	         <img src='{$scalebar_path}'>
        <td/>
      {/if}
</table>
</p>

<p> LocationInfo: {$location_info} </p>

</div>

<div style="visibility:hidden">
  <table border="0" cellspacing="2" cellpadding="0">
    <tr>

      <td valign="top" align="left">
      
 {literal}        
        <table border="1" cellspacing="0" cellpadding="0">

          <tr>
            <td>
              <input type="radio" name="TOOLBAR_CMD" value="ZOOM_IN" onclick="changeTool('mainDHTML','ZOOM_IN')"  CHECKED />
              <img onclick="javascript:CheckRadio(0);changeTool('mainDHTML','ZOOM_IN')" Xsrc="gfx/cartoclient/icon_zoomin.gif" alt="ZoomIn" title="{ZI_ALT}" />
            </td>
          </tr>

          <tr>
            <td>
              <input name="TOOLBAR_CMD" type="radio" onclick="changeTool('mainDHTML','ZOOM_OUT')" value="ZOOM_OUT"  {ZOOM_OUT_CHECKED} />
              <img onclick="javascript:CheckRadio(1);changeTool('mainDHTML','ZOOM_OUT')" Xsrc="gfx/cartoclient/icon_zoomout.gif" alt="ZoomOut" title="{ZO_ALT}" />
            </td>
          </tr>
          <tr>
            <td>
              <input name="TOOLBAR_CMD" type="radio" onclick="changeTool('mainDHTML','PAN')" value="PAN" {PAN_CHECKED} />

              <img onclick="javascript:CheckRadio(2);changeTool('mainDHTML','PAN')" Xsrc="gfx/cartoclient/icon_pan.gif" alt="Pan"  title="{RC_ALT}" />
            </td>
          </tr>
          <tr>
            <td>
              <input name="TOOLBAR_CMD" type="radio" onclick="changeTool('mainDHTML','QUERY')" value="QUERY" {QUERY_CHECKED} />
              <img onclick="javascript:CheckRadio(3);changeTool('mainDHTML','QUERY')" Xsrc="gfx/cartoclient/icon_infoarea.gif" alt="Info" title="{IN_ALT}" />
            </td>
          </tr>

          <tr>
            <td>
              <input name="TOOLBAR_CMD" type="radio" onclick="changeTool('mainDHTML','MEASURE')" value="MEASURE" {MEASURE_CHECKED}>
              <img onclick="javascript:CheckRadio(4);changeTool('mainDHTML','MEASURE')" Xsrc="gfx/cartoclient/icon_ruler.gif" alt="Distance" title="{DT_ALT}" />
            </td>
          </tr>
          <tr>
            <td>
              <input name="TOOLBAR_CMD" type="radio" onclick="changeTool('mainDHTML','SURFACE')" value="SURFACE" {SURFACE_CHECKED} />

              <img onclick="javascript:CheckRadio(5);changeTool('mainDHTML','SURFACE')" Xsrc="gfx/cartoclient/icon_area.gif" alt="Surface" title="{SF_ALT}" />
            </td>
          </tr>
        </table>
     {/literal}
        
      </td>
    </tr>
  </table>
</div>

</form>

<div id='mapImageDiv' class="dhtmldiv" style='visibility:hidden'>
  <img src='{$mainmap_path}'>
</div>

<div id='myCanvasDiv' class="dhtmldiv"></div>
<div id='myCanvas2Div' class="dhtmldiv"></div>

<div id='mainDHTMLDiv' class="dhtmldiv"></div>
<div id="diplayContainerDiv" class="dhtmldiv">
  <table border="0" width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td width="50%"><div id="displayCoordsDiv" style="font-size:10px;"></div></td>

      <td align="right" width="50%"><div id="displayMeasureDiv" style="font-size:10px;"></div></td>
    </tr>
  </table>
</div>


{if $query_result|default:''}
<h1>Results: </h1>
 {$query_result}
{/if}



<pre>
<hr/>
Request:
{$debug_request}
<hr/>
ClientContext:
{$debug_clientcontext}
<hr/>
</pre>


</body>
</html>
