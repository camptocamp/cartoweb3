<script type="text/javascript">
{strip}
{foreach from=$tools item=tool name=tool}
  {if $smarty.foreach.tool.first}cw3_tools = new Array("{$tool->id}",
  {elseif $smarty.foreach.tool.last}"{$tool->id}");
  {else}"{$tool->id}",
  {/if}
{/foreach}
{/strip}
cw3_initial_selected_tool = "mainmap.{$selected_tool}('map');";
{if $toolbar_rendering != 'radio'}
cw3_initial_selected_tool += "setActiveToolButton('{$selected_tool}');";
{/if}
</script>
{if $toolbar_rendering != 'radio'}
  <input type="hidden" name="tool" id="tool" value="{$selected_tool}"/>
{/if}

  {if $group == 1}
    <input type="hidden" name="recenter_none" value="-1582561, -1327290, 1142895, 1540633" />
    <a href="javascript:document.carto_form.recenter_none.name='recenter_bbox';">
     <img src="{r type=gfx/layout}fullextent.gif{/r}"
     title="{t}full_extent{/t}" alt="{t}full_extent{/t}" />
    </a>&nbsp;&nbsp;
  {/if}

{counter start=-1 print=false name=tindex}
  {foreach from=$tools item=tool}
  {if !$group || $group == $tool->group}
    {if $toolbar_rendering == 'radio'}
      <label for="{$tool->id}" onclick="checkRadio(this.htmlFor);mainmap.{$tool->id}('map');" >
      <input type="radio" id="{$tool->id}" name="tool" value="{$tool->id}" 
      {if $selected_tool == $tool->id}checked="checked"{/if} />
    {/if}
    {if $tool->hasIcon}
      <img id="{$tool->id}_icon" alt="{$tool->id}" title="{t}{$tool->id}{/t}" 
        src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}"
        {if $toolbar_rendering != 'radio'}
        class="toolbar" 
        onclick="mainmap.{$tool->id}('map');setActiveToolButton('{$tool->id}');"
        {/if}
        />
    {else}
       <span>{t}{$tool->id}{/t}</span>
    {/if}
    {if $toolbar_rendering == 'radio'}
    </label>&nbsp;
    {/if}
  {/if}
  {/foreach}

  {if $group == 4}
    <a href="javascript:ontop(6);">
    <img  style="margin-left:5px;" src="{r type=gfx/layout}help.png{/r}"
    title="{t}Help{/t}" alt="{t}help{/t}" align="texttop"/></a>&nbsp;&nbsp;
    <a href="javascript:ontop(2);">
    <img src="{r type=gfx/layout/help}fileprint.png{/r}"
    title="{t}Print{/t}" alt="{t}print{/t}" /></a>&nbsp;&nbsp;
    <a href="javascript:resetSession();">
      <img src="{r type=gfx/layout}reset_session.png{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" style="padding-bottom:3px;"/>
    </a>
  {/if}
