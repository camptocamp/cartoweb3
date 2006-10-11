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

  <link rel="icon" href="{r type=gfx/layout}cw3.png{/r}" type="image/png" />
  
  <title>{t}CartoWeb3 - Demonstration{/t}</title>

  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $layerReorder|default:''}<script type="text/javascript" src="{r type=js plugin=layerReorder}layerReorder.js{/r}"></script>{/if}
  {if $outline_active|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=outline}outline.css{/r}" />{/if}
  {if $collapsibleKeymap|default:''}<script type="text/javascript" src="{r type=js}keymap.js{/r}"></script>
  <script language="JavaScript" type="text/javascript">
    <!--
    var hideKeymapMsg = "{t}Collapse keymap{/t}";
    var showKeymapMsg = "{t}Show keymap{/t}";
    var hideKeymap = {$collapseKeymap};
    //-->
  </script>
  {/if}
  
  {include file="cartoclient_ajaxHeader.tpl"}
  {if $ajaxOn|default:''}
  <script type="text/javascript" src="{r type=js}custom.ajax.js{/r}"></script>
  {/if}

  {include file="dhtmlcode.tpl"}
  
  {if $exportPdf|default:''}
  <script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>
  <script type="text/javascript" src="{r type=js plugin=exportPdf}dhtmlPdf.js{/r}"></script>
  {/if}
</head>

<body>

<!-- header begins here -->
<table width="100%">
<tr><td colspan="2">
<table id="topbanner" border="0"  cellpadding="0" cellspacing="0">
  <tr>
    <td id="logo"><img src="{r type=gfx/layout}logoc2c.gif{/r}" alt="camptocamp" border="0"/></td>
    <td id="title" nowrap="nowrap">{t}CartoWeb3 - Demonstration{/t}</td>
    <td align='right' width="1%">
      <table>
      <tr>
        <td align='right'>
        {foreach from=$locales item=lang}
        {if $lang == $currentLang}
        <img class="lang_on" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" />
        {else}
        <a href="javascript:document.carto_form.action='{$selfUrl}?lang={$lang}';FormItemSelected();" onclick="javascript:doSubmit();"><img class="lang_off" name="{$lang}" src="{r type=gfx/layout}language_{$lang}.gif{/r}" alt="{$lang}" /></a>
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

<tr><td>
<!-- header ends here -->

<form method="post" action="{$selfUrl}" name="carto_form" id="carto_form" onsubmit="doSubmit();">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" id="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" id="selection_type" />
  <input type="hidden" name="selection_coords" id="selection_coords" />
  <input type="hidden" name="features" id="features" />
  <input type="hidden" name="project" id="projects" value="{$project}" />
  {if $collapsibleKeymap|default:''}
  <input type="hidden" name="collapse_keymap" id="collapse_keymap" value="{$collapseKeymap}" />
  {/if}
  <input type="hidden" id="fake_reset" name="fake_reset" />
  <input type="hidden" id="fake_query" name="fake_query" />
  {if $outline_active|default:''}
  {$outlinelabel}
  {/if}
  <div id="content">
    <table id="mapframe" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="3" id="toolbar_row" nowrap="nowrap">
          {include file="toolbar.tpl" group="1" header="1"}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          {include file="toolbar.tpl" group="3"}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          {include file="toolbar.tpl" group="4"}
          <span {if !$exportPdf}style="display:none; "{/if}>
          &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{include file="toolbar.tpl" group="2"}
          </span>
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
        <td></td>
        <td>
          <table width="100%"><tr>
            <td width="50%"><div id="floatGeo" class="locationInfo">{t}Coord (m):{/t} %s / %s</div></td>
            <td width="50%" align="right">
              <div id="floatDistance" class="locationInfo"><span id="distanceValueLabel">{t}Approx. distance :{/t}</span> %s{if $factor == 1000} km{else} m{/if}</div>
              <div id="floatSurface" class="locationInfo"><span id="surfaceValueLabel">{t}Approx. area :{/t}</span> %s{if $factor == 1000} km&sup2;{else} m&sup2;{/if}</div></td>
          </tr></table>
        </td>
        <td></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}south_west.gif{/r}" name="pan_sw" id="pan_sw" alt="SW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}south.gif{/r}" name="pan_s" id="pan_s" alt="S" /></td>
        <td><input type="image" src="{r type=gfx/layout}south_east.gif{/r}" name="pan_se" id="pan_se" alt="SE" /></td>
      </tr>
      <tr>
        <td  colspan="3"><br /></td>
      </tr>
      <tr>
       <td></td>
       <td align="center">
         <table border="0" cellpadding="0" cellspacing="0" width="100%">
           <tr>
             <td colspan="3" valign="top" align="center" width="80%">
               {if $scalebar_path|default:''}
               <img id="scalebar" src="{$scalebar_path}" 
               alt="{t}scalebar_alt{/t}" width="{$scalebar_width}"
               height="{$scalebar_height}" title="" />
               {/if}
             </td>
           </tr>
           <tr>
             <td width="10%" align="center">
               {if $scales_active|default:''}
               <div id="recenter_scale_div">
                 {$scales}
               </div>
               {/if}
             </td>
             <td width="80%" align="center">
                 {$projections}
             </td> 
             <td width="10%" align="center">
               {if $mapsizes_active|default:''}
                 {$mapsizes}
               {/if}
             </td>
           </tr>
         </table>
       </td>
       <td></td>
       </tr>
       <tr>
         <td  colspan="3"><br /></td>
       </tr>
         
       {if $tables_result OR $ajaxOn}
       <tr>
         <td colspan="3">
           <table id="tables_result_container"
                  style="{if $ajaxOn && !$tables_result}display:none;{/if}border:1px solid black;"
                  width="100%">
             <tr>
               <td>
                 <center>
                   <div id="tables_result">        
                     {$tables_result}
                   </div>
                 </center>
               </td>
             </tr>
           </table>
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
      Powered by <a href="http://www.cartoweb.org/" target="_blank">CartoWeb {$cw_version.version}</a>
          &copy; <a href="http://www.camptocamp.com/"  target="_blank">Camptocamp SA</a>
    </td>
  </tr>
</table>
<pre>
Request:
{$debug_request}
</pre>

</div>

  <div id="leftbar">
    <div>
      <ul id="tabnav2">
        <li id="label2"><a href="javascript:ontop(2)">{t}Print{/t}</a></li>
        <li id="label4"><a href="javascript:ontop(4)">{t}About{/t}</a></li>
        <!-- <li id="label6"><a href="javascript:ontop(6)">{t}Help Viewer{/t}</a></li> -->
        {if $wmsBrowser|default:''}<li id="label8"><a href="javascript:ontop(8)">{t}WMS layers{/t}</a></li>{/if}
      </ul>
      <ul id="tabnav1">
        <li id="label1"><a href="javascript:ontop(1)">{t}Themes{/t}</a></li>
        <li id="label3"><a href="javascript:ontop(3)">{t}Search{/t}</a></li>
        <li id="label5"><a href="javascript:ontop(5)">{t}Outline{/t}</a></li>
        <li id="label7"><a href="javascript:ontop(7)">{t}Query{/t}</a></li>
      </ul>

   </div>


    <div id="container">
    <!-- folder 1 starts here -->
    <div id="folder1" class="folder">
      <br />
      <div id="layerTree">
        {$layers}
      </div>
      <br />
      <p style="text-align:right; vertical-align:middle;">

        <a href="javascript:ontop(6);">
         <img src="{r type=gfx/layout}help.gif{/r}"
              title="{t}Help{/t}" alt="{t}help{/t}" 
              style="margin-bottom:3px;"/></a>
        &nbsp;&nbsp;
        <a href="javascript:resetSession();">
          <img src="{r type=gfx/layout}reset_session.gif{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" style="padding-bottom:3px;"/>
        </a>&nbsp;&nbsp;
        <input type="submit" id="refresh" name="refresh" value="{t}OK{/t}" 
        class="form_button" style="margin-bottom:7px;" />
      </p>
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
        <li>{t}Nima :{/t} <a target="_blank" href="http://geoengine.nima.mil/geospatial/SW_TOOLS/NIMAMUSE/webinter/rast_roam.html">Vmap0</a></li>
        <li>{t}Gtopo30 :{/t} <a  target="_blank" href="http://edc.usgs.gov/products/elevation/gtopo30/gtopo30.html">W020N90</a></li>
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
  
    <!-- folder 2 starts here -->
    <div id="folder2" class="folder">
      <br />
        {if $exportPdf|default:''}
          {$exportPdf}
        {else}
          <p>
            {t}You are not allowed to print maps{/t},
            {if $auth_active|default:''}
             {t}please{/t} {$auth}.
            {/if}
          </p>
        {/if}
    </div>
    <!-- end of folder 2 -->
    

    
    <!-- folder 7 starts here -->
    <div id="folder7" class="folder" style="height:550px;">
      <br />
        {if $selection_result|default:''}
        {$selection_result}
        {/if}

        {if $query_result|default:''}
        {$query_result}
        {/if}  
    </div>
    <!-- end of folder 7 -->
    <!-- folder 8 starts here -->
    {if $wmsBrowser|default:''}
    <div id="folder8" class="folder">
      <br />
      <center>
        {$wmsBrowser}
      </center> 
    </div>
    {/if}
    <!-- end of folder8 -->
    
  </div> <!--container-->
  </div> <!--leftbar-->
</form>
</td></tr>
<tr><td></td></tr>
</table>
</body>
</html>
