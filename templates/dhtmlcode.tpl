  <link rel="stylesheet" type="text/css" href="{r type=css}dhtml_tools.css{/r}" title="stylesheet" />
  {literal}  
  <script type="text/javascript" src="js/x_core_nn4.js"></script>
  <script type="text/javascript" src="js/x_dom_nn4.js"></script>
  <script type="text/javascript" src="js/x_event_nn4.js"></script>
  <script type="text/javascript" src="js/navTools.js"></script>
  <script type="text/javascript" src="js/graphTools.js"></script>
  <script type="text/javascript" src="js/folders.js"></script>
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

      // map units values
      dhtmlBox.mapHeight = {$mainmap_height};
      dhtmlBox.boxx = {$bboxMinX};
      dhtmlBox.boxy = {$bboxMinY};
      dhtmlBox.pixel_size = {$smarty.capture.pixelSize};
      dhtmlBox.dist_msg = '{t}Approx. distance: {/t}';
      dhtmlBox.dist_unit = {if $factor == 1000}' km'{else}' m'{/if};
      dhtmlBox.surf_msg = '{t}Approx. surface: {/t}';
      dhtmlBox.surf_unit = {if $factor == 1000}' km&sup2;'{else}' m&sup2;'{/if};
      dhtmlBox.coord_msg = '{t}Coords (m): {/t}';
  {literal}
          
      dhtmlBox.initialize();
    }
    
    // dhtml folders settings
    var myfolders = new Array(1, 2);
    
    window.onload = function() {
      dboxInit();
      setupFolders();
      xHide(xGetElementById('mapAnchorDiv')); 
    }
    /*]]>*/
  </script>
  {/literal}
