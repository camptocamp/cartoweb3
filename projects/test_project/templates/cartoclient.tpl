<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>{t}Cartoclient Title{/t} test_project</title>
  
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}folders.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />
  {if $collapsibleKeymap|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}keymap.css{/r}" />{/if}
  {if $layerReorder|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layerReorder}layerReorder.css{/r}" />{/if}

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
  
  {include file="dhtmlcode.tpl"}
</head>

<body>

<div id="banner"><h1>{t}Cartoclient Title{/t} test_project</h1></div>

<form method="post" action="{$selfUrl}" name="carto_form" onsubmit="doSubmit();">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
{if $outline_active|default:''}
  {$outlinelabel}
{/if}
  <div id="content">

    {include file="toolbar.tpl" group=1}

    <table>
      <tr><td colspan="3"><div id="floatScale" class="locationInfo">{t}Current scale:{/t} 1:{$currentScale}</div></td></tr>
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
            <td width="50%"><div id="floatDistance" class="locationInfo">{t}Dist approx.:{/t}%s{if $factor == 1000} km{else} m{/if}</div>
              <div id="floatSurface" class="locationInfo">{t}Approx. surface :{/t} %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
          </tr></table>
        </td>
      </tr>
      {if $scalebar_path|default:''}
      <tr><td align="center" colspan="3"><img src="{$scalebar_path}" 
      alt="{t}scalebar_alt{/t}" width="{$scalebar_width}" height="{$scalebar_height}" title="" /></td></tr>
      {/if}
    </table>

  Current user: {$username} roles: {$roles}
  <p> LocationInfo: {$location_info} </p>

  {if $user_messages|default:''}
   <span style="color: blue;">
   {t} User messages {/t}</span>
   {foreach from=$user_messages item=message}
        <p>{$message}</p>
   {/foreach}   
  {/if}

  {if $developer_messages|default:''}
   <span style="color: green; border: 10px; background-color: yellow;">
   {t} Developer messages {/t}</span>
   {foreach from=$developer_messages item=message}
     <p>{$message}</p>
   {/foreach}   
  {/if}

  {if $selection_result|default:''}
  {$selection_result}
  {/if}

  {if $query_result|default:''}
  {$query_result}
  {/if}

  {if $tables_result|default:''}
  {$tables_result}
  {/if}

<pre>
Request:
{$debug_request}
</pre>

  <p>&copy; <a href="http://camptocamp.com/" target="_blank">Camptocamp SA</a> -
  <a href="http://validator.w3.org/check/referer" target="_blank">XHTML Validator</a></p>
  
  </div>

  <div id="leftbar">    
    {if $locales|default:''}
    <p>
      {foreach from=$locales item=locale name=lang}
      {if $locale != $currentLang}<a href="javascript:document.carto_form.action='{$smarty.server.PHP_SELF}?lang={$locale}';FormItemSelected();">{$locale}</a>{else}<strong>{$locale}</strong>{/if}
      {if !$smarty.foreach.lang.last}|{/if}
      {/foreach}
    </p>
    {/if}

    <p>

      {if $projects_chooser_active|default:''}
      {t}Choose project{/t}
        <select name="project" onchange="javascript:document.carto_form.posted.value=0;FormItemSelected();">
            {html_options values=$project_values output=$project_output 
                                        selected=$project}
        </select><br />
      {else}
        <input type="hidden" name="project" value="{$project}" />
      {/if}
         
      <input type="submit" id="refresh" name="refresh" value="refresh" class="form_button" />
      <input type="submit" name="reset_session" value="reset_session" class="form_button" 
        onclick="javascript:document.carto_form.posted.value=0;FormItemSelected();"/>
    </p>
    <div>
      {if $layerReorder|default:''}
      <ul class="tabnav" id="tabnav2">
        <li id="label4"><a href="javascript:ontop(4)">{t}Layer Reorder{/t}</a></li>
      </ul>
      {/if}
      <ul class="tabnav" id="tabnav1">
        <li id="label1"><a href="javascript:ontop(1)">{t}Navigation{/t}</a></li>
        <li id="label2"><a href="javascript:ontop(2)">{t}Themes{/t}</a></li>
        {if $exportPdf|default:''}<li id="label3"><a href="javascript:ontop(3)">{t}PDF{/t}</a></li>{/if}
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
    
      {if $hello_active|default:''}
      <p>Hello plugin test: <br />
      {$hello_message} <br />
      <input type="text" name="hello_input" /></p>
      {/if}
    
      {if $recenter_active|default:''}
      {$recenter}
      {/if}
    
      {if $scales_active|default:''}
      {$scales}
      {/if}
    
      {if $shortcuts_active|default:''}
      {$shortcuts}
      {/if}
    
      {if $id_recenter_active|default:''}
      {$id_recenter}
      {/if}
    
      {if $mapsizes_active|default:''}
      {$mapsizes}
      {/if}
    
      {if $outline_active|default:''}
      {$outline}
      {/if}
    
      {if $routing_active|default:''}
      {$routing}
      {/if}

      {if $exporthtml_active|default:''}
      <a href="{$exporthtml_url}" target="print">{t}Print{/t}</a>
      {/if}

      {if $projectplugin_active|default:''}
      <p>Project plugin test: <br />
      {$projectplugin_message} <br />
      <input type="text" name="projectplugin_input" /></p>
      {/if}

      {t}Message from plugin ProjectLocation:{/t} {$projectmessage}

    </div>
    <!-- end of folder1 -->
  
    <div id="folder2" class="folder">
      
      {$layers}
    
    </div>
    <!-- end of folder2 -->
    {if $exportPdf|default:''}
    <div id="folder3" class="folder">
      {$exportPdf}
    </div>
    <!-- end of folder3 -->
    {/if}

    {if $layerReorder|default:''}
    <div id="folder4" class="folder">
       {$layerReorder}
    </div>
    <!-- end of folder4 -->
    {/if}
    
  </div>
</div>
   {if $auth_active|default:''}
   {$auth}
   {/if}

</form>


</body>
</html>
