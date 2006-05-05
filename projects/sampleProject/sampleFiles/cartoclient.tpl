<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<!-- Modify here the title that is shown at the top of the browser window -->
  <title>{t}Cartoweb - My First Project{/t}</title>
  
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}folders.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />
  {if $collapsibleKeymap|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}keymap.css{/r}" />{/if}
  {if $outline_active|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=outline}outline.css{/r}" />{/if}
  {if $toolpicker_active|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}toolPicker.css{/r}" title="stylesheet" />{/if}

  <link rel="icon" href="{r type=gfx/layout}cw3.png{/r}" type="image/png" />
  
  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $collapsibleKeymap|default:''}<script type="text/javascript" src="{r type=js}keymap.js{/r}"></script>
    
  <script type="text/javascript">
    <!--
    var hideKeymapMsg = "{t}Collapse keymap{/t}";
    var showKeymapMsg = "{t}Show keymap{/t}";
    var hideKeymap = {$collapseKeymap};
    //-->
  </script>
  {/if}
  
  {include file="dhtmlcode.tpl"}
  {if $exportPdf|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>{/if}

</head>

<body>
<!-- Modify here the title that is shown in the top banner -->
<div id="banner"><h1>{t}My First Project{/t}</h1></div>

<form method="post" action="{$selfUrl}" name="carto_form" onsubmit="doSubmit();">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  <input type="hidden" name="features" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
{if $outline_active|default:''}
  {$outlinelabel}
{/if}


  <!-- ###################################################
       ############## main column  #######################
       ################################################### -->
  <div id="content">
    {include file="toolbar.tpl" group=1 header=1}
    {include file="toolbar.tpl" group=2}

    <table>
      <!-- pan direction arrows removed -->
      <tr><td><div id="floatScale" class="locationInfo">{t}Current scale:{/t} 1:{$currentScale}</div></td></tr>
      <tr>
        <td valign="top">
          {include file="mainmap.tpl"}
        </td>
      </tr>
      <tr>
        <td>
          <table width="100%"><tr>
            <td width="50%"><div id="floatGeo" class="locationInfo">{t}Coords (m):{/t} %s / %s</div></td>
            <td width="50%"><div id="floatDistance" class="locationInfo">{t}Dist approx.:{/t}%s{if $factor == 1000} km{else} m{/if}</div>
              <div id="floatSurface" class="locationInfo">{t}Approx. surface :{/t} %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
          </tr></table>
        </td>
      </tr>
      {if $scalebar_path|default:''}
      <tr><td align="center"><img src="{$scalebar_path}" 
      alt="{t}scalebar_alt{/t}" width="{$scalebar_width}" height="{$scalebar_height}" title="" /></td></tr>
      {/if}
    </table>

  {if $selection_result|default:''}
  {$selection_result}
  {/if}

  {if $query_result|default:''}
  {$query_result}
  {/if}

  {if $tables_result|default:''}
  {$tables_result}
  {/if}

    <!-- user and developper messages removed -->

    <p>&copy; <a href="http://camptocamp.com/" target="_blank">Camptocamp SA</a></p>
  
  </div>

  <!-- ###################################################
       ############## left column  #######################
       ################################################### -->

  <div id="leftbar">    
    <p>
      <input type="submit" id="refresh" name="refresh" value="{t}refresh{/t}" class="form_button" />
      <input type="submit" name="reset_session" value="{t}reset_session{/t}" class="form_button" 
        onclick="javascript:document.carto_form.posted.value=0;FormItemSelected();"/>
    </p>
    <div>
      <!-- tabnav2 (folders second line) not needed -->
      <ul class="tabnav" id="tabnav1">
        <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
        {if $exportPdf|default:''}<li id="label3"><a href="javascript:ontop(3)">{t}PDF{/t}</a></li>{/if}
        {if $outline_active|default:''}<li id="label6"><a href="javascript:ontop(6)">{t}Outline{/t}</a></li>{/if}
      </ul>
    </div>
    <div id="container">
  
      <!-- themes (layers) folder content moved in folder1 -->
      <div id="folder1" class="folder">
        {$switches}
        {$layers}
      </div>
      <!-- end of folder1 -->
    
      {if $exportPdf|default:''}
      <div id="folder3" class="folder">
        {$exportPdf}
      </div>
      <!-- end of folder3 -->
      {/if}

      {if $outline_active|default:''}
      <div id="folder6" class="folder">
        {$outline}
      </div>
      <!-- end of folder6 -->
      {/if}
    </div>
      
    <!-- location elements moved from folder1 to the main page -->
    {if $scales_active|default:''}
      {$scales}
    {/if}
    
    {if $shortcuts_active|default:''}
      {$shortcuts}
    {/if}
    
    {if $mapsizes_active|default:''}
      {$mapsizes}
    {/if}
  
  </div>

</form>
   {if $toolpicker_active|default:''}
     {include file="toolPicker.tpl"}
   {/if}

</body>
</html>
