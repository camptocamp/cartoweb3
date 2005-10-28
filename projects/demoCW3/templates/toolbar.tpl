{strip}

  {if $group == 1}
  <input type="hidden" name="recenter_none" value="142776,4757216,1083857,5409261" />
  <a href="javascript:document.carto_form.recenter_none.name='recenter_bbox';document.carto_form.submit();">
    <img align ="middle" src="{r type=gfx/layout}fullextent.gif{/r}"
    title="{t}full_extent{/t}" alt="{t}full_extent{/t}" />
  </a>&nbsp;&nbsp;
  {/if}
  {counter start=-1 print=false name=tindex}
  {foreach from=$tools item=tool}
  {if !$group || $group == $tool->group}
    <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" class="toolbar_off" alt="{$tool->id}" title="{t}{$tool->id}{/t}" onclick="CheckRadio('{counter name=tindex}');mainmap.{$tool->id}('map');setActiveButton('{$tool->id}', true);" id="{$tool->id}_icon" align ="middle"
    {if $selected_tool == $tool->id} onload="setActiveButton('{$tool->id}', false);"{/if}
    />&nbsp;
  {/if}
  {/foreach}
  {if $group == 4}
  &nbsp;&nbsp;
  <a href="javascript:ontop(6);">
    <img  align ="middle" src="{r type=gfx/layout/help}help.png{/r}"
    title="{t}Help{/t}" alt="{t}help{/t}" /></a>&nbsp;&nbsp;
  <a href="javascript:ontop(2);">
    <img align ="middle"
    src="{r type=gfx/layout/help}fileprint.png{/r}"
    title="{t}Print{/t}" alt="{t}print{/t}" /></a>
  &nbsp;&nbsp;&nbsp;
  <a href="javascript:resetSession();">
    <img align ="middle" src="{r type=gfx/layout}2_recur.png{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" />
  </a>
  {/if}

<div style="display:none;">
  {counter start=-1 print=false name=tindex}
  {foreach from=$tools item=tool}
  {if !$group || $group == $tool->group}
  <input type="radio" name="tool" 
  value="{$tool->id}" 
  onclick="mainmap.{$tool->id}('map');"
  {if $selected_tool == $tool->id}checked="checked"{/if} id="{$tool->id}" />
  {/if}
  {/foreach}
</div>
{/strip}


          
