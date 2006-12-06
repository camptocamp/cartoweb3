<link rel="stylesheet" type="text/css" href="{r type=css}dhtml.css{/r}" />
<script type="text/javascript" src="{r type=js}x_cartoweb.js{/r}"></script>
<script type="text/javascript" src="{r type=js}wz_jsgraphics.js{/r}"></script>
<script type="text/javascript" src="{r type=js}Logger.js{/r}"></script>
<script type="text/javascript" src="{r type=js}dhtmlAPI.js{/r}"></script>
<script type="text/javascript" src="{r type=js}dhtmlFeatures.js{/r}"></script>
<script type="text/javascript" src="{r type=js}dhtmlInit.js{/r}"></script>
{if $mapUnits == 'dd'}<script type="text/javascript" src="{r type=js}LatLon.js{/r}"></script>{/if}

{if $edit_allowed|default:''}<script type="text/javascript" src="{r type=js plugin=edit}dhtmlEdit.js{/r}"></script>{/if}
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
    factor = {$factor};
    mainmap.mapUnits = '{$mapUnits}';
    mainmap.scalebarUnits = '{$scalebarUnits}';{literal}

    var rasterLayer = new Layer("raster");{/literal}
    var feature = new Raster('{$mainmap_path}', 'map_raster_img');{literal}
    rasterLayer.addFeature(feature);
    mainmap.addLayer(mainmap,rasterLayer);

    var drawLayer = new Layer("drawing");{/literal}
{if $edit_allowed|default:''}
{foreach from=$features item=feature}
    var feature = new Feature("{$feature->WKTString}");
    feature.id = "{$feature->id}";
    feature.attributes = new Array({$feature->attributesAsString});
    feature.operation = "{$feature->operation|default:'undefined'}";
    drawLayer.addFeature(feature);  
{/foreach}
{/if} {* end edit allowed *}
    mainmap.addLayer(mainmap,drawLayer);

    mainmap.currentLayer = drawLayer;    
    
{if $edit_allowed|default:''}   
{if $attribute_names|default:''}
    mainmap.editAttributeNames = new Array({$attribute_names});
    mainmap.editAttributeNamesI18n = new Array({$attribute_names_i18n});
{/if}
{if $attribute_types|default:''}
    mainmap.editAttributeTypes = new Array({$attribute_types});    
{/if}
{if $attribute_names|default:''}
    mainmap.drawEditAttributesTable();
{/if}
//    mainmap.handleEditTable();
    insert_feature_max_num = {$edit_max_insert};
{/if} {* end edit allowed *}
{literal}
}{/literal}


// Sets the profile (production, development or custom) for the AjaxHandler
var profile = '{$cartoclient_profile}'{literal}
if (typeof(AjaxHandler) != 'undefined') {
    AjaxHandler.setProfile(profile);
}{/literal}

{if $toolTips_active|default:''}
  /* Assigns JS state variables:
   * layerListIds and scale for toolTipsRequests
   */  
  {literal}
  function initToolTips() {
      if (typeof(AjaxPlugins.ToolTips) != 'undefined') {
        {/literal}
        AjaxPlugins.ToolTips.serviceUrl = '{$selfUrl}?toolTips=1';
        AjaxPlugins.ToolTips.charSet = '{$charset}';
        AjaxPlugins.ToolTips.lang = '{$toolTips_currentLanguage}';
        {literal}
      }

      // init the imagemap (area tags)
      if ($('map1')) {
          var imagemapTag = $('map1');
          args = new Array();
          args[0] = imagemapTag;
          checkMainmapExistence('callToolTips', args);
      }
  }

  /*
   * args is an array containing all the arguments passed originally
   */
  function callToolTips(args) {
    xAppendChild(mainmap.getDisplay('map').rootDisplayLayer, args[0]); 
    AjaxPlugins.ToolTips.useMap();
  }

  /*
   * generic loop function to wait till the mainmap object is ready
   * receive the name of the output function and an array containing the parameters to pass to this function
   */
  function checkMainmapExistence(functionCall, args) {
      try {
        mainmap
      } catch (e){
        setTimeout(function() { checkMainmapExistence(functionCall, args); }, 100);
        return;
      }
      // call to dynamically named function
      this[functionCall](args);
  }
  {/literal}

  EventManager.Add(window, 'load', initToolTips, false);
{/if}

/*]]>*/
</script>
