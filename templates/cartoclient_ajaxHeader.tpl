{if $ajaxOn|default:''}

  {* Ajax related js includes - Debug Tool: jsTrace *}
  {* Remove these two links to get rid of the logger (i.e. in production mode *}
  {if $cartoclient_profile == 'development'}
    <script type="text/javascript" src="{r type=js}jsTrace/dom-drag.js{/r}"></script>
    <script type="text/javascript" src="{r type=js}jsTrace/jsTrace.js{/r}"></script>
  {/if}


  {* Ajax related js includes - External libraries *}
  <script type="text/javascript" src="{r type=js}prototype.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}Logger.js{/r}"></script>


  {* Ajax related js includes - Global logic *}
  <script type="text/javascript" src="{r type=js}AjaxHelper.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxHandler.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxPlugins.js{/r}"></script>

  {* Ajax related js includes - Per plugin logic *}
  {* Coreplugins *}
  <script type="text/javascript" src="{r type=js plugin=location}Location.ajax.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}Layers.ajax.js{/r}"></script>{/if}
  <script type="text/javascript" src="{r type=js plugin=images}Images.ajax.js{/r}"></script>
  <script type="text/javascript" src="{r type=js plugin=query}Query.ajax.js{/r}"></script>
  <script type="text/javascript" src="{r type=js plugin=tables}Tables.ajax.js{/r}"></script>


  {* Plugins *}
  {if $layerReorder|default:''}<script type="text/javascript" src="{r type=js plugin=layerReorder}LayerReorder.ajax.js{/r}"></script>{/if}
  {if $outline_active|default:''}<script type="text/javascript" src="{r type=js plugin=outline}Outline.ajax.js{/r}"></script>{/if}
  {if $search_active|default:''}<script type="text/javascript" src="{r type=js plugin=search}Search.ajax.js{/r}"></script>{/if}
  {if $hello_active|default:''}<script type="text/javascript" src="{r type=js plugin=hello}Hello.ajax.js{/r}"></script>{/if}
  {if $exportDxf|default:''}<script type="text/javascript" src="{r type=js plugin=exportDxf}ExportDxf.ajax.js{/r}"></script>{/if}
  {if $bboxHistoryForm|default:''}<script type="text/javascript" src="{r type=js plugin=bboxHistory}BboxHistory.ajax.js{/r}"></script>{/if}

  {if $toolTips_active|default:''}
    <script type="text/javascript" src="{r type=js plugin=toolTips}overlib_mini.js{/r}"></script>
    <script type="text/javascript" src="{r type=js plugin=toolTips}ToolTips.ajax.js{/r}"></script>
    <script type="text/javascript">
    /*<![CDATA[*/
      _toolTipsTimeoutBeforeHide = '{$toolTipsTimeoutBeforeHide}';
    /*]]>*/
    </script>
  {/if}

  {* Service plugin for AJAX *}
  <script type="text/javascript" src="{r type=js plugin=cartoMessages}CartoMessages.ajax.js{/r}"></script>

{/if}

{* Load the required libraries for Tooltips plugin if ajaxOn = false *}
{if $toolTips_active|default:'' && !$ajaxOn|default:''}
  {if $cartoclient_profile == 'development'}
    <script type="text/javascript" src="{r type=js}jsTrace/dom-drag.js{/r}"></script>
    <script type="text/javascript" src="{r type=js}jsTrace/jsTrace.js{/r}"></script>
  {/if}
  <script type="text/javascript" src="{r type=js}prototype.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}Logger.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxHelper.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxHandler.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxPlugins.js{/r}"></script>
  <script type="text/javascript" src="{r type=js plugin=cartoMessages}CartoMessages.ajax.js{/r}"></script>
  <script type="text/javascript" src="{r type=js plugin=toolTips}overlib_mini.js{/r}"></script>
  <script type="text/javascript" src="{r type=js plugin=toolTips}ToolTips.ajax.js{/r}"></script>
  <script type="text/javascript">
  /*<![CDATA[*/
    _toolTipsTimeoutBeforeHide = '{$toolTipsTimeoutBeforeHide}';
  /*]]>*/
  </script>
{/if}
