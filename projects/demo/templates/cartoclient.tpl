<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}folders.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />
  {if $collapsibleKeymap|default:''}<link rel="stylesheet" type="text/css" href="{r type=css}keymap.css{/r}" />{/if}
  <title>{t}CartoWeb3 - Demonstration{/t}</title>

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

  <!-- Ajax related js includes - External libraries -->
  <script type="text/javascript" src="{r type=js}prototype-1.3.1.js{/r}"></script>

  <!-- Ajax related js includes - Global logic -->
  <script type="text/javascript" src="{r type=js}AjaxHelper.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxHandler.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}AjaxPlugins.js{/r}"></script>

  <!-- Ajax related js includes - Per plugin logic -->
  <!-- Coreplugins -->
  {if true}<script type="text/javascript" src="{r type=js plugin=location}Location.ajax.js{/r}"></script>{/if}
  {if true}<script type="text/javascript" src="{r type=js plugin=layers}Layers.ajax.js{/r}"></script>{/if}
  {if true}<script type="text/javascript" src="{r type=js plugin=images}Images.ajax.js{/r}"></script>{/if}
  {if true}<script type="text/javascript" src="{r type=js plugin=query}Query.ajax.js{/r}"></script>{/if}
  {if true}<script type="text/javascript" src="{r type=js plugin=tables}Tables.ajax.js{/r}"></script>{/if}
  <!-- Plugins -->
  {if true}<script type="text/javascript" src="{r type=js plugin=hello}Hello.ajax.js{/r}"></script>{/if}
  
  {include file="dhtmlcode.tpl"}
  <script language="JavaScript" type="text/javascript">
    <!--
    {literal}
    window.onload = function() {
      if (typeof onLoadString == "string") {
        eval(onLoadString);
      }
    }
    {/literal}
    //-->
  </script>
</head>

<body>

<!-- header begins here -->
<div id="topbanner">
  <div id="logo">
    <a href="./"><img src="{r type=gfx/layout}logo.gif{/r}" alt="camptocamp" /></a>
  </div>
  <span id="title"><br /><br />{t}CartoWeb3 - Demonstration{/t}</span>
{if $locales|default:''}
  <div id="langlinks">
    {foreach from=$locales item=lang}
      {if $lang == $currentLang}
        <img class="lang_on" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" />
      {else}
        <a href="javascript:document.carto_form.action='{$selfUrl}?lang={$lang}';FormItemSelected();" onclick="javascript:xShow(xGetElementById('mapAnchorDiv'));"><img class="lang_off" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" /></a>
      {/if}
    {/foreach}
  </div>
{/if}
</div>
<!-- header ends here -->

<form method="post" action="{$selfUrl}" name="carto_form" id="carto_form">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" id="selection_type" />
  <input type="hidden" name="selection_coords" id="selection_coords" />
  <input type="hidden" name="features" id="features" />
  <input type="hidden" name="project" value="{$project}" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
{if $outline_active|default:''}
  {$outlinelabel}
{/if}

  {if $auth_active|default:''}
    <div id="loginform">
    {if $username|default:''}{t}welcome{/t} {$username} - {/if}
    {$auth}
    </div>
  {/if}
  
  <div id="content">

    <table id="mapframe" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="3" id="toolbar_row">
          {include file="toolbar.tpl"}
        </td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}north_west.gif{/r}" name="pan_nw" id="pan_nw" alt="NW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}north.gif{/r}" name="pan_n" id="pan_n" alt="N" /></td>
        <td><input type="image" src="{r type=gfx/layout}north_east.gif{/r}" name="pan_ne" id="pan_ne" alt="NE" /></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}west.gif{/r}" name="pan_w" id="pan_w" alt="W" /></td>
        <td valign="top">
          {include file="mainmap.tpl"}
        </td>
        <td><input type="image" src="{r type=gfx/layout}east.gif{/r}" name="pan_e" id="pan_e" alt="E" /></td>
      </tr> 
      <tr>
        <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" id="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" id="pan_s" alt="S" /></td>
        <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" id="pan_se" alt="SE" /></td>
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
      <tr>
       <td colspan="3" align="center">
         <table border="0" cellpadding="0" cellspacing="0" width="90%">
       <tr>
         <td colspan="2" valign="top" align="center">
              {if $scalebar_path|default:''}
               <img id="scalebar" src="{$scalebar_path}" 
                alt="{t}scalebar_alt{/t}" width="{$scalebar_width}"
                height="{$scalebar_height}" title="" />
              {/if}
         </td>
       </tr>
       <tr>
         <td width="50%" align="left">
              {if $scales_active|default:''}
                {$scales}
              {/if}
          </td>
          <td width="50%" align="right">
              {if $mapsizes_active|default:''}
                {$mapsizes}
              {/if}
         </td>
       </tr>
     </table>
       </td>
      </tr>

      {if $user_messages|default:''}
      <tr>
       <td colspan="3" align="center">
        <table id="user_message" width="90%" border="0" cellpadding="0" cellspacing="0">
      <tr>
            <th align="left" id="title">{t}User messages{/t}</th>
          </tr>
          <tr>
        <td>
            <ul>
                  {foreach from=$user_messages item=message}
                    <li>{$message}</li>
                  {/foreach}   
            </ul>
         </td>
       </tr> 
    </table>
       </td>
      </tr>
      {/if}

  {if $tables_result|default:true}
  <tr>
   <td colspan="3">
    <center>
     <table id="query_result" width="90%">
      <tr>
        <th align="left" id="query_result_title">{t}Query result{/t}</th>
      </tr>
      <tr>
        <td>
         <center>
         	<div id="tables_result">
          		{$tables_result}
          	</div>
         </center>
        </td>
     </tr>
     <tr>
      <td align="center">
        &nbsp;<br />
    <input type="submit" name="query_clear" value="{t}Query Clear{/t}" class="form_button" onClick="{literal}AjaxHandler.doAction('Query.clear', {clickedElement: this});return false;{/literal}" />
      </td>
     </tr>
     </table>
    </center>
   </td>
  </tr>
  {/if}
  <tr>
    <td colspan="3" align="center">
      <div class="footer" id="footer">
        <div class="footertxt">
      Powered by <a href="http://camptocamp.com/srubrique103.html" target="_blank">CartoWeb</a>
          &copy; <a href="http://www.camptocamp.com/"  target="_blank">Camptocamp SA</a>
    </div>
      </div>
    </td>
  </tr>
</table>

  </div>

  <div id="leftbar">
    <div>
      <ul id="tabnav2">
        <li id="label4"><a href="javascript:ontop(4)">{t}About{/t}</a></li>
        <li id="label5"><a href="javascript:ontop(5)">{t}Outline{/t}</a></li>
      </ul>
      <ul id="tabnav1">
        <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
        <li id="label2"><a href="javascript:ontop(2)">{t}Print{/t}</a></li>
        <li id="label3"><a href="javascript:ontop(3)">{t}Search{/t}</a></li>
      </ul>

   </div>


    <div id="container">
    <!-- folder 1 starts here -->
    <div id="folder1" class="folder">
      {$layers}
    </div>
    <!-- end of folder 1 -->

    <!-- folder 5 starts here -->
    <div id="folder5" class="folder">
      <br />
      {if $outline_active|default:''}
      {$outline}
      {/if}
    </div>
    <!-- end of folder 5 -->

    <!-- folder 4 starts here -->
    <div id="folder4" class="folder">
      <fieldset>
       <legend>{t}Data sources{/t}</legend>
       <ul>
        <li>{t}Nima:{/t} <a href="http://geoengine.nima.mil/muse-cgi-bin/rast_roam.cgi">Vmap0</a></li>
        <li>{t}Gtopo30:{/t} <a href="http://edcdaac.usgs.gov/gtopo30/w020n90.asp">W020N90</a></li>
       </ul>
      </fieldset>

      <fieldset>
       <legend>{t}Projection and datums{/t}</legend>
       <ul>
        <li>{t}UTM 32 North{/t}</li>
        <li>{t}WGS 84{/t}</li>
        <li>{t}EPSG 32632{/t}</li>
       </ul>
      </fieldset>
    </div>
    <!-- end of folder 4 -->
  
    <!-- folder 3 starts here -->
    <div id="folder3" class="folder">
      <div id="helloPlugin">
	      {if $hello_active|default:''}
	      <p>Hello plugin test: <br />
	      <span id="hello_message">{$hello_message}</span> <br />
	      <input type="text" name="hello_input" id="hello_input" /></p>
	      <input type="submit" name="hello_submit" id="hello_submit" />
	      {/if}
      </div>
    
      {if $shortcuts_active|default:''}
      {$shortcuts}
      {/if}
    
      {if $recenter_active|default:''}
      {$recenter}
      {/if}
    
      {if $id_recenter_active|default:''}
      {$id_recenter}
      {/if}
    
      {if $exporthtml_active|default:''}
      <a href="{$exporthtml_url}" target="print">{t}Print{/t}</a>
      {/if}

    </div>
    <!-- end of folder 3 -->
  
    <!-- folder 2 starts here -->
    <div id="folder2" class="folder">
        {if $exportPdf|default:''}
          {$exportPdf}
        {else}
          <p>{t}You are not allowed to print maps{/t}</p>
        {/if}
    </div>
    <!-- end of folder 2 -->
    </div>
  </div>
</form>


  {if $developer_messages|default:''}
   <span style="color: green; border: 10px; background-color: yellow;">
   {t} Developer messages {/t}</span>
   {foreach from=$developer_messages item=message}
     <p>{$message}</p>
   {/foreach}   
  {/if}

</body>
</html>
