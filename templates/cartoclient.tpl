<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  {if $layers|default:''}<link rel="stylesheet" type="text/css" href="{r type=css plugin=layers}layers.css{/r}" title="stylesheet" />{/if}
  <meta name="author" content="Sylvain Pasche" />
  <meta name="email" content="sylvain dot pasche at camptocamp dot com" />
  <title>{$cartoclient_title}</title>

  <script type="text/javascript" src="{r type=js}carto.js{/r}"></script>
  {if $layers|default:''}<script type="text/javascript" src="{r type=js plugin=layers}layers.js{/r}"></script>{/if}
  
  {include file="dhtmlcode.tpl"}
</head>

<body>

<div id="banner"><h1>{$cartoclient_title}</h1></div>

<form method="post" action="{$smarty.server.PHP_SELF}" name="carto_form">
  <input type="hidden" name="posted" value="true" />

  <div id="content">

    {include file="toolbar.tpl"}

    <table>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}north_west.gif{/r}" name="pan_nw" alt="NW" /></td>
        <td align="center"><input type="image" src="{r type=gfx/layout}north.gif{/r}" name="pan_n" alt="N" /></td>
        <td><input type="image" src="{r type=gfx/layout}north_east.gif{/r}" name="pan_ne" alt="NE" /></td>
      </tr>
      <tr>
        <td><input type="image" src="{r type=gfx/layout}west.gif{/r}" name="pan_w" alt="W" /></td>
        <td>
          <input type="hidden" name="selection_type" />
          <input type="hidden" name="selection_coords" />
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

<pre>
Request:
{$debug_request}
<div class="separator"></div>
ClientContext:
{$debug_clientcontext}
</pre>

  <p>&copy; <a href="http://camptocamp.com/" target="_blank">Camptocamp SA</a> -
  <a href="http://validator.w3.org/check/referer" target="_blank">XHTML Validator</a></p>
  
  </div>

  <div id="leftbar">    

  {if $keymap_path|default:''}
  <div id="keymap">
  <input type="image" name="keymap" src="{$keymap_path}" 
  style="width:{$keymap_width}px;height:{$keymap_height}px;" />
  </div>
  {/if}

  {$layers}

  <p>
    <input type="submit" name="refresh" value="refresh" class="form_button" /><br />
    <input type="submit" name="reset_session" value="reset_session" class="form_button" />
  </p>

  {if $hello_active|default:''}
  <p>Hello plugin test: <br />
  {$hello_message} <br />
  <input type="text" name="hello_input" /></p>
  {/if}

  {if $recenter_active|default:''}
  {$recenter}
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

  </div>

</form>

</body>
</html>
