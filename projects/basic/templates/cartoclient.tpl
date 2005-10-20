<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>{t}Cartoclient Title{/t}</title>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <style type="text/css">
   {literal}
   body, html {font-family:verdana, arial, sans-serif; font-size:small;}
   a {text-decoration: none;color:#5C74BB;}
   a:hover {text-decoration: underline;color:#D0670A;}
   .box {border:1px solid #add8e6;}
   {/literal}
  </style>
  
</head>

<body bgcolor="#ffffff" text="#000000">
<form method="post" action="{$selfUrl}" name="carto_form">
  <input type="image" name="dummy" alt="" id="dummy" />
  <input type="hidden" name="posted" value="1" />
  <input type="hidden" name="selection_type" />
  <input type="hidden" name="selection_coords" />
  
  <table>
    <tr>
      <td width="200" valign="top">
      <!-- left bar -->
        <table class="box">
          <tr>
            <td align="center" bgcolor="#add8e6"><b>{t}Themes{/t}</b></td>
          </tr>
          <tr>
            <td>
              <input type="submit" id="refresh" name="refresh" value="refresh" />
              <input type="submit" name="reset_session" value="reset_session" />
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
          <tr><td colspan="3">{t}Current scale:{/t} 1:{$currentScale}</td></tr>
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
        <table class="box">
          <tr>
            <td align="center" bgcolor="#add8e6"><b>{t}Navigation{/t}</b></td>
          </tr>
          <tr>
            <td>
        {if $locales|default:''}
          {foreach from=$locales item=locale name=lang}
          {if !$smarty.foreach.lang.first || !$smarty.foreach.lang.last}
            {if $locale != $currentLang}<a href="{$selfUrl}?lang={$locale}">{$locale}</a>{else}<strong>{$locale}</strong>{/if}
            {if !$smarty.foreach.lang.last}|{/if}
          {/if}
          {/foreach}
          
        {if $auth_active|default:''}
          {$auth}
        {/if}
            </td>
          </tr>
          <tr>
            <td>
        {/if}
        {if $projects_chooser_active|default:''}
        {t}Choose project{/t}
          <select name="project" >
            {html_options values=$project_values output=$project_output 
                                        selected=$project}
          </select>
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
            <td align="center">
                <input type="image" name="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}" />
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
   <h4>{t} User messages {/t}</h4>
   {foreach from=$user_messages item=message}
        <p>{$message}</p>
   {/foreach}   
  {/if}

  {if $developer_messages|default:''}
   <h4>{t}Developer messages {/t}</h4>
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
