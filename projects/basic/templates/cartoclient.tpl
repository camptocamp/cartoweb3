<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>{t}Cartoclient Title{/t}</title>
  
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}folders.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" />{/if}
  <link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}tables.css{/r}" />

  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  {if $exportPdf|default:''}<script type="text/javascript" src="{r type=js plugin=exportPdf}exportPdf.js{/r}"></script>{/if}

  {include file="dhtmlcode.tpl"}
</head>

<body>
<form method="post" action="{$selfUrl}" name="carto_form">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="js_folder_idx" value="{$jsFolderIdx}" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  <input type="hidden" name="features" />
  
  
  <table>
    <tr>
      <td width="200" valign="top">
      <!-- left bar -->
        <table style="border: 1px solid lightblue;">
          <tr>
            <td style="text-align:center;background-color:lightblue;"><b>{t}Themes{/t}</b>
            </td>
          </tr>
          <tr>
            <td>
              <input type="submit" id="refresh" name="refresh" value="refresh" class="form_button" />
              <input type="submit" name="reset_session" value="reset_session" class="form_button" 
                onclick="javascript:document.carto_form.posted.value=0;FormItemSelected();"/>
              {$switches}
              {$layers}
            </td>
          </tr>
        </table>
      </td>
      <td valign="top">
      <!-- main content -->
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
          {if $scalebar_path|default:''}
          <tr><td align="center" colspan="3"><img src="{$scalebar_path}" 
          alt="{t}scalebar_alt{/t}" width="{$scalebar_width}" height="{$scalebar_height}" title="" /></td></tr>
          {/if}
        </table>
        <p> LocationInfo: {$location_info} </p>
      </td>
      <td valign="top">
      <!-- right column -->
        <table style="border: 1px solid lightblue;">
          <tr>
            <td style="text-align:center;background-color:lightblue;"><b>{t}Navigation{/t}</b>
            </td>
          </tr>
          <tr>
            <td>
        {if $locales|default:''}
          {foreach from=$locales item=locale name=lang}
          {if !$smarty.foreach.lang.first || !$smarty.foreach.lang.last}
            {if $locale != $currentLang}<a href="javascript:document.carto_form.action='{$selfUrl}?lang={$locale}';FormItemSelected();">{$locale}</a>{else}<strong>{$locale}</strong>{/if}
            {if !$smarty.foreach.lang.last}|{/if}
          {/if}
          {/foreach}
          
        {if $auth_active|default:''}
          {$auth}
        {/if}
        <br />
        {/if}
        {if $projects_chooser_active|default:''}
        {t}Choose project{/t}
          <select name="project" onchange="javascript:document.carto_form.posted.value=0;FormItemSelected();">
            {html_options values=$project_values output=$project_output 
                                        selected=$project}
          </select><br />
        {else}
          <input type="hidden" name="project" value="{$project}" />
        {/if}
            </td>
          </tr>
          {if $exportPdf|default:''}
          <tr><td>
            {$exportPdf}
          </td></tr>
          {/if}
          <tr>
            <td>
              <div id="keymap">
                <input type="image" name="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}" 
                style="width:{$keymap_width}px;height:{$keymap_height}px;" />
              </div>
              {if $exporthtml_active|default:''}
                <p><a href="{$exporthtml_url}" target="print">{t}Print{/t}</a></p>
              {/if}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  
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

  {if $tables_result|default:''}
  {$tables_result}
  {/if}

  <p>&copy; <a href="http://camptocamp.com/" target="_blank">Camptocamp SA</a> -
  <a href="http://validator.w3.org/check/referer" target="_blank">XHTML Validator</a></p>
   

</form>


</body>
</html>
