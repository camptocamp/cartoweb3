<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  {if $order|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=projectLayers}order.css{/r}" />{/if}
  <title>{t}CartoWeb3 - Demonstration{/t}</title>


  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $exportPdf|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>{/if}

  <script language="JavaScript" type="text/javascript">
    <!--
    var hideKeymapMsg = "{t}Collapse keymap{/t}";
    var showKeymapMsg = "{t}Show keymap{/t}";
    var hideKeymap = {$collapseKeymap};

    {literal}
    function updateKeymapStatus() {
      if (hideKeymap)
        collapseKeymap();
    }
    {/literal}                       
    //-->
  </script>       
  
  {include file="dhtmlcode.tpl"}
</head>

<body>

<!-- header begins here -->
<div id="topbanner">
  <div id="logo">
    <a href="./"><img src="{r type=gfx/layout}logo.gif{/r}" alt="camptocamp" /></a>
  </div>
  <span id="title"><br /><br />{t}CartoWeb3 - Demonstration{/t}</span>
    
  <div id="langlinks">
    {foreach from=$locales item=lang}
      {if $lang == $currentLang}
        <img class="lang_on" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" />
      {else}
        <a href="javascript:document.carto_form.action='{$smarty.server.PHP_SELF}?lang={$lang}';FormItemSelected();" onclick="javascript:xShow(xGetElementById('mapAnchorDiv'));"><img class="lang_off" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" /></a>
      {/if}
    {/foreach}
  </div>
</div>
<!-- header ends here -->

<form method="post" action="{$smarty.server.PHP_SELF}" name="carto_form">
  <input type="image" name="dummy" alt="" width="0" height="0" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  <input type="hidden" name="project" value="{$project}" />
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />

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
        <td><input type="image" src="{r type=gfx/layout}north_west.gif{/r}" name="pan_nw" alt="NW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}north.gif{/r}" name="pan_n" alt="N" /></td>
        <td><input type="image" src="{r type=gfx/layout}north_east.gif{/r}" name="pan_ne" alt="NE" /></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}west.gif{/r}" name="pan_w" alt="W" /></td>
        <td id="mainmapCell">
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
       <td colspan="3" align="center">
         <table border="0" cellpadding="0" cellspacing="0" width="90%">
	   <tr>
	     <td colspan="2" valign="top" align="center">
              {if $scalebar_path|default:''}
               <img src="{$scalebar_path}" 
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

  {if $tables_result|default:''}
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
          {$tables_result}
         </center>
        </td>
     </tr>
     <tr>
      <td align="center">
        &nbsp;<br />
	<input type="submit" name="query_clear" value="{t}Query Clear{/t}" class="form_button"/>
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
    

  {if $keymap_path|default:''}
   <div id="keymapContainer" style="visibility:hidden">
    <div id="floatkeymap"><input type="image" name="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}"
    style="width:{$keymap_width}px;height:{$keymap_height}px; border:0;" /></div>
    <div id="keymapswitcher"><a href="#" onclick="javascript:collapseKeymap();"><img 
      src="{r type=gfx/layout}keymap_off.gif{/r}" title="{t}Collapse keymap{/t}"
      alt="" id="switcherimg" style="border:0;"/></a></div>
   </div>
  {/if}

  </div>

  <div id="leftbar">    

    <p id="row2" style="position:relative">
      <span id="label4" class="label"><a href="javascript:ontop(4)">{t}About{/t}</a></span><span 
	id="label5" class="label"><a href="javascript:ontop(5)">{t}Outline{/t}</a></span>
    </p>
    <p id="row1" style="position:relative">
      <span id="label1" class="label"><a href="javascript:ontop(1)">{t}Themes{/t}</a></span><span 
	id="label2" class="label"><a href="javascript:ontop(2)">{t}Print{/t}</a></span><span 
	id="label3" class="label"><a href="javascript:ontop(3)">{t}Search{/t}</a></span>
    </p>

    <div id="container"></div>
  </div> 
  
    <!-- folder 1 starts here -->
    <div id="folder1" class="folder">
      <br />
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
      <br />
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
      <br />
    
      {if $hello_active|default:''}
      <p>Hello plugin test: <br />
      {$hello_message} <br />
      <input type="text" name="hello_input" /></p>
      {/if}
    
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
</form>
  
    <!-- folder 2 starts here -->
    <div id="folder2" class="folder">
      <br />
        {if $exportPdf|default:''}
          {$exportPdf}
        {else}
          <p>{t}You are not allowed to print maps{/t}</p>
        {/if}
    </div>
    <!-- end of folder 2 -->


  {if $developer_messages|default:''}
   <span style="color: green; border: 10px; background-color: yellow;">
   {t} Developer messages {/t}</span>
   {foreach from=$developer_messages item=message}
     <p>{$message}</p>
   {/foreach}   
  {/if}

</body>
</html>
