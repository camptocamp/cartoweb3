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
<table width="100%">
<tr><td>
<table id="topbanner" border="0"  cellpadding="0" cellspacing="0">
  <tr>
    <td id="logo"><img src="{r type=gfx/layout}logo.gif{/r}" alt="camptocamp" border="0"/></td>
    <td id="title" nowrap="nowrap">{t}CartoWeb3 - Demonstration{/t}</td>
    <td align='right' width="1%">
      <table>
      <tr>
        <td align='right'>
        {foreach from=$locales item=lang}
        {if $lang == $currentLang}
        <img class="lang_on" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" />
        {else}
        <a href="javascript:document.carto_form.action='{$smarty.server.PHP_SELF}?lang={$lang}';FormItemSelected();" onclick="javascript:xShow(xGetElementById('mapAnchorDiv'));"><img class="lang_off" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" /></a>
        {/if}
        {/foreach}</td>
      </tr>
      <tr>
        <td class="mini" align='right' nowrap="nowrap">
        {if $auth_active|default:''}
        {if $username|default:''}{t}welcome{/t} {$username} - {/if}
        {$auth}
        {/if}</td>
      </tr>
      </table>
    </td>
  </tr>
</table>
</td></tr>
<tr>
  <td colspan="3" align="right">
    <a href="javascript:document.carto_form.posted.value=0;FormItemSelected();document.carto_form.submit();">
      <img src="{r type=gfx/layout}2_remove.png{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" />
    </a>
  </td>
</tr>
<tr><td>
<!-- header ends here -->

<form method="post" action="{$smarty.server.PHP_SELF}" name="carto_form">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  <input type="hidden" name="features" />
  <input type="hidden" name="project" value="{$project}" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
{if $outline_active|default:''}
  {$outlinelabel}
{/if}

  
  
  <div id="content">
  
    <br />
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
        <td valign="top">
          {include file="mainmap.tpl"}
        </td>
        <td><input type="image" src="{r type=gfx/layout}east.gif{/r}" name="pan_e" alt="E" /></td>
      </tr> 
      <tr>
        <td></td>
        <td>
          <table width="100%"><tr>
            <td width="50%"><div id="floatGeo" class="locationInfo">{t}Coordonnees (m):{/t} %s / %s</div></td>
            <td width="50%" align="right"><div id="floatDistance" class="locationInfo">{t}Distance approx.: {/t}%s{if $factor == 1000} km{else} m{/if}</div>
              <div id="floatSurface" class="locationInfo">{t}Surface approx. : {/t} %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
          </tr></table>
        </td>
        <td></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" alt="S" /></td>
        <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" alt="SE" /></td>
      </tr>
      <tr>
        <td  colspan="3"><br /></td>
      </tr>
      <tr>
       <td colspan="3" align="center">
         <table border="0" cellpadding="0" cellspacing="0" width="100%">
         <tr>
           <td colspan="3" valign="top" align="center" width="80%">
              {if $scalebar_path|default:''}
               <img src="{$scalebar_path}" 
                alt="{t}scalebar_alt{/t}" width="{$scalebar_width}"
                height="{$scalebar_height}" title="" />
              {/if}
           </td>
	 </tr><tr>
	   <td width="10%" align="center">
              {if $scales_active|default:''}
                {$scales}
              {/if}
           </td>
           <td width="80%"></td> 
           <td width="10%" align="center">
              {if $mapsizes_active|default:''}
                {$mapsizes}
              {/if}
           </td>
         </tr>
         </table>
       </td>
       </tr>
       <tr>
         <td  colspan="3"><br /></td>
       </tr>
	   {if $tables_result|default:''}
  <tr>
   <td colspan="3">
    <center>
     <table id="query_result" width="100%">
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
       {if $developer_messages|default:''}
       <tr>
       <td colspan="3" align="center">
       <table id="user_message" width="100%" border="0" cellpadding="0" cellspacing="0">
       <tr>
            <th align="left" class="messages"><span style="color: green; border: 10px; background-color: yellow;">{t} Developer messages {/t}
            </span></th>
       </tr>
       <tr>
         <td>
            <ul>
              {foreach from=$developer_messages item=message}
              <li>{$message}</li>
              {/foreach}   
            </ul>
         </td>
       </tr> 
       </table>
       </td>
       </tr>
       {/if}
       
       {if $user_messages|default:''}
       <tr>
       <td colspan="3" align="center">
        <table id="user_message" width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <th align="left" class="messages">{t}User messages{/t}</th>
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

  
  
  <tr>
    <td  colspan="3"><br /></td>
  </tr>
  <tr>
    <td colspan="3" align="center" class="mini">
      Powered by <a href="http://www.cartoweb.org/" target="_blank">CartoWeb ***3.1***</a>
          &copy; <a href="http://www.camptocamp.com/"  target="_blank">Camptocamp SA</a>
    </td>
  </tr>
</table>

  </div>

  <div id="leftbar">
    <div>
      <ul id="tabnav1">
        <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
        <li id="label3"><a href="javascript:ontop(3)">{t}Search{/t}</a></li>
        <li id="label5"><a href="javascript:ontop(5)">{t}Outline{/t}</a></li>
      </ul>

   </div>


    <div id="container">
    <!-- folder 1 starts here -->
    <div id="folder1" class="folder">
      <br />
      {$layers}
      <center>
      <input type="submit" name="refresh" value="refresh" class="form_button" />
      </center>
    </div>
    <!-- end of folder 1 -->
    
    <!-- folder 3 starts here -->
    <div id="folder3" class="folder">
      <br />
      
      <fieldset>
      <legend><b>{t}A route{/t}</b></legend>
      {if $routing_active|default:''}
        {$routing}
      {/if}
      </fieldset>
      <br />
      <fieldset>
      <legend><b>{t}A geographic object{/t}</b></legend>
      <br />
      <iframe name="search" id="iframe_search" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" width="190" height="125px"
      {if $iframeSrc|default:''}src="{$iframeSrc}" {/if} >
      {t}Your browser does not support search services{/t}
      </iframe>
      </fieldset>
      
      {if $id_recenter_active|default:''}
      {$id_recenter}
      {/if}
    
      {if $exporthtml_active|default:''}
      <a href="{$exporthtml_url}" target="print">{t}Print{/t}</a>
      {/if}

    </div>
    <!-- end of folder 3 -->
    
    <!-- folder 5 starts here -->
    <div id="folder5" class="folder">
      <br />
      {if $outline_active|default:''}
      {$outline}
      {/if}
    </div>
    <!-- end of folder 5 -->
    
  </div> <!--container-->
  </div> <!--leftbar-->
</form>
</table>
</td></tr>

</body>
</html>
