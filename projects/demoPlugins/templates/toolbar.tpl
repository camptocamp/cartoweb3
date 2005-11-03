{if $header}
<script type="text/javascript">
{strip}
var cw3_tools = new Array(
{foreach from=$tools item=tool name=tool}
"{$tool->id}"{if !$smarty.foreach.tool.last},{/if}
{/foreach}
);
{/strip}
{if $toolbar_rendering != 'radio'}
cw3_initial_selected_tool = "setActiveToolButton('{$selected_tool}');";
{/if}
cw3_initial_selected_tool += "mainmap.{$selected_tool}('map');";
</script>
{if $toolbar_rendering != 'radio'}
  <input type="hidden" name="tool" id="tool" value="{$selected_tool}"/>
{/if}
{/if}

{if $group == 1}
<tr align="left" valign="middle">
  <td style="padding:5px">
  <input type="hidden" name="recenter_none" value="-1582561, -1327290, 1142895, 1540633" />
  <a href="javascript:document.carto_form.recenter_none.name='recenter_bbox';document.carto_form.submit();">
    <img src="{r type=gfx/layout}fullextent.gif{/r}"
    title="{t}full_extent{/t}" alt="{t}full_extent{/t}" />
  </a>
  </td>
</tr>
{/if}

{if $group == 3}
    <tr align="center">
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
    {if $group != 3}
    <tr align="center">
      <td>
      <img id="{$tool->id}_icon" alt="{$tool->id}" title="{t}{$tool->id}{/t}" 
        src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}"
        {if $toolbar_rendering != 'radio'}
        class="toolbar_off" 
        onclick="mainmap.{$tool->id}('map');setActiveToolButton('{$tool->id}');"
        {/if}
        />
      </td>
    </tr>
    {else}
    <td style="padding:5px">
      <img id="{$tool->id}_icon" alt="{$tool->id}" title="{t}{$tool->id}{/t}" 
        src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}"
        {if $toolbar_rendering != 'radio'}
        class="toolbar_off" 
        onclick="mainmap.{$tool->id}('map');setActiveToolButton('{$tool->id}');"
        {/if}
        />
    </td>
    {/if}
    {else}
       <span>{t}{$tool->id}{/t}</span>
    {/if}
    {if $toolbar_rendering == 'radio'}
    </label>&nbsp;
    {/if}
  {/if}
  {/foreach}

  {if $group == 4}
  <tr>
    <td style="padding:5px;padding-left:1px;">
    <a href="javascript:ontop(6);">
      <img  style="margin-left:5px;" src="{r type=gfx/layout}help.png{/r}"
      title="{t}Help{/t}" alt="{t}help{/t}" />
    </a>
    </td>
  <tr>
    <td style="padding:5px;padding-top:1px;">
    <a href="javascript:resetSession();">
      <img src="{r type=gfx/layout}reset_session.png{/r}" alt="{t}reset session{/t}" title="{t}Reset session{/t}" />
    </a>
    </td>
  </tr>
  {/if}
