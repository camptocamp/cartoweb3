<link rel="stylesheet" type="text/css" href="{r type=css}dhtml_tools.css{/r}" title="stylesheet" />
  <script type="text/javascript" src="{r type=js}x_core.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}x_core_n4.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}x_dom.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}x_dom_n4.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}x_event_n4.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}navTools.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}graphTools.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}folders.js{/r}"></script>
  {literal}  
  <script type="text/javascript">
    /*<![CDATA[*/ 
                  
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
      dhtmlBox.nbMaxSegments = {$dhtml_nb_max_segments|default:-1}; // max number of segments for polylines and polygons
      
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
          {math equation="(pixSizeX + pixSizeY) / 2"
                pixSizeX=$smarty.capture.pixSizeX
                pixSizeY=$smarty.capture.pixSizeY}
        {/capture}
        {capture name="pixelSizeFactor"}
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
      dhtmlBox.pixel_size = {$smarty.capture.pixelSizeFactor};
      dhtmlBox.pixel_size_m = {$smarty.capture.pixelSize};
      dhtmlBox.dist_msg = '{t}Approx. distance: {/t}';
      dhtmlBox.dist_unit = {if $factor == 1000}' km'{else}' m'{/if};
      dhtmlBox.surf_msg = '{t}Approx. surface: {/t}';
      dhtmlBox.surf_unit = {if $factor == 1000}' km&sup2;'{else}' m&sup2;'{/if};
      dhtmlBox.coord_msg = '{t}Coords (m): {/t}';
      dhtmlBox.overlap_msg = '{t}Overlapping polygons forbidden{/t}';
      dhtmlBox.maxSegments1_msg = '{t}Number of segments limited to {/t}';
      dhtmlBox.maxSegments2_msg = '{t} currently drawn{/t}';
  {literal}
          
      dhtmlBox.initialize();
      
      xHide(dhtmlBox.load);
      window.onresize = function() {
        dhtmlBox.initialize();
      }
      myform.onsubmit = function() {
        xShow(dhtmlBox.load);
      }
    }
    
    if (typeof onLoadString != "string") onLoadString = "";
    onLoadString += "dboxInit();";
    
    /*]]>*/
  </script>
  {/literal}
