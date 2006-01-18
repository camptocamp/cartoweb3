

<link rel="stylesheet" type="text/css" href="{r type=css}dhtml.css{/r}" />
<script type="text/javascript" src="{r type=js}x_cartoweb.js{/r}"></script>
<script type="text/javascript" src="{r type=js}wz_jsgraphics.js{/r}"></script>
<script type="text/javascript" src="{r type=js}Logger.js{/r}"></script>
<script type="text/javascript" src="{r type=js}dhtmlAPI.js{/r}"></script>
<script type="text/javascript" src="{r type=js}dhtmlFeatures.js{/r}"></script>
<script type="text/javascript" src="{r type=js}dhtmlInit.js{/r}"></script>
<script type="text/javascript" src="{r type=js}folders.js{/r}"></script>
<script type="text/javascript">
/*<![CDATA[*/
// TODO application object
// alert messages to be translated
_m_overlap = "{t}overlapping polygons are not allowed{/t}"
_m_delete_feature = "{t}Are you sure ?{/t}";
_m_bad_object = "{t}Not conform object{/t}";



{literal}
function initMap() {{/literal}
    mainmap.setExtent({$bboxMinX},{$bboxMinY},{$bboxMaxX},{$bboxMaxY});
    factor = {$factor};{literal}

    var rasterLayer = new Layer("raster");{/literal}
    var feature = new Raster('{$mainmap_path}');{literal}
    rasterLayer.addFeature(feature);
    mainmap.addLayer(mainmap,rasterLayer);

    var drawLayer = new Layer("drawing");{/literal}
{foreach from=$features item=feature}
    var feature = new Feature("{$feature->WKTString}");
    feature.id = "{$feature->id}";
    feature.operation = "{$feature->operation|default:'undefined'}";
    drawLayer.addFeature(feature);  
{/foreach}{literal}
    mainmap.addLayer(mainmap,drawLayer);

    mainmap.currentLayer = drawLayer;    
    
}{/literal}
/*]]>*/
</script>
