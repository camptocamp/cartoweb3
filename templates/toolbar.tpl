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
{counter start=-1 print=false name=tindex}
  {foreach from=$tools item=tool}
  {strip}
  {if !$group || $group == $tool->group}
    {if $toolbar_rendering == 'radio'}
      <label for="{$tool->id}" onclick="checkRadio(this.htmlFor);mainmap.{$tool->id}('map');" >
      <input type="radio" id="{$tool->id}" name="tool" value="{$tool->id}" 
      {if $selected_tool == $tool->id}checked="checked"{/if} />
    {/if}
    {if $tool->hasIcon}
      <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" 
        id="{$tool->id}_icon" 
        alt="{$tool->id}" title="{t}{$tool->id}{/t}" 
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
  {/strip}
  {/foreach}
