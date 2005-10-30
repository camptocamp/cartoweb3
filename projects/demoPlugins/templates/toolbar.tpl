<table cellpadding="0" cellspacing="1" border="0">
    {counter start=-1 print=false name=tindex}
    {foreach from=$tools item=tool}
    {if !$group || $group == $tool->group}
    <tr align="left">
    <td>
    <label for="{$tool->id}">
      {if $tool->hasIcon}
      <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" 
      alt="{$tool->id}" title="{t}{$tool->id}{/t}"
      onclick="CheckRadio('{counter name=tindex}');mainmap.{$tool->id}('map');"  align="middle"/>
      {else}
      <span onclick="CheckRadio('{counter name=tindex}');
      mainmap.{$tool->id}('map');">
               {t}{$tool->id}{/t}
            </span>
           {/if}
      <input type="radio" name="tool" value="{$tool->id}" 
                {if $selected_tool == $tool->id}checked="checked"{/if}
                id="{$tool->id}" onclick="mainmap.{$tool->id}('map');" />
      
    </label>
    </td>
    </tr>
    {/if}
    {/foreach}
    <tr align="left" valign="middle">
    <td>
    <input type="hidden" name="recenter_none" value="-1582561, -1327290, 1142895, 1540633" />
    <input type="image" 
    onclick="javascript:document.carto_form.recenter_none.name='recenter_bbox';"
    src="{r type=gfx/layout}fullextent.gif{/r}"
    title="{t}full_extent{/t}" alt="{t}full_extent{/t}" />
    </td>
    </tr>
    <tr><td>
    <a href="javascript:ontop(6);">
    <img  align ="left" style="margin-left:5px;" src="{r type=gfx/layout}help.png{/r}"
    title="{t}Help{/t}" alt="{t}help{/t}" /></a>
    </td></tr>
    <tr><td style="padding-left:7px;padding-top:3px;">
    <a href="javascript:resetSession();">
      <img align ="left" src="{r type=gfx/layout}reset_session.png{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" />
    </a>
    </td></tr>
</table>