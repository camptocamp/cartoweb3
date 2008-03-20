{if $header == 1}
<script type="text/javascript">
{strip}
{foreach from=$tools item=tool name=tool}
  {if $smarty.foreach.tool.first && $smarty.foreach.tool.last}cw3_tools = new Array("{$tool->id}");
  {elseif $smarty.foreach.tool.first}cw3_tools = new Array("{$tool->id}",
  {elseif $smarty.foreach.tool.last}"{$tool->id}");
  {else}"{$tool->id}",
  {/if}
{/foreach}
{/strip}
var cw3_initial_selected_tool = "mainmap.{$selected_tool}('map');";
{if $toolbar_rendering != 'radio'}
cw3_initial_selected_tool += "setActiveToolButton('{$selected_tool}');";
var toolbar_rendering = '{$toolbar_rendering}';
{/if}
</script>
{if $toolbar_rendering != 'radio'}
  <input type="hidden" name="tool" id="tool" value="{$selected_tool}"/>
{/if}
{/if}
  {foreach from=$tools item=tool}
  {if !$group || $group == $tool->group}
    {if $tool->stateless == 1}
      {if $tool->hasIcon}
        <input type="image" id="{$tool->id}_icon" alt="{$tool->id}" 
           name="{$tool->id}" 
           title="{t}{$tool->id}{/t}" 
               src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}"
               onclick="mainmap.{$tool->id}('map');" />
      {/if}
    {else}
      {if $toolbar_rendering == 'radio'}
        <label for="{$tool->id}" onclick="checkRadio(this.htmlFor);mainmap.{$tool->id}('map');" >
        <input type="radio" id="{$tool->id}" name="tool" value="{$tool->id}" 
        {if $selected_tool == $tool->id}checked="checked"{/if} />
      {/if}
      {if $tool->hasIcon}
        <img id="{$tool->id}_icon" alt="{$tool->id}" title="{t}{$tool->id}{/t}" 
          src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}"
          {if $toolbar_rendering != 'radio'}
          class="toolbar_off" 
          onclick="mainmap.{$tool->id}('map');{if !$tool->oneshot}setActiveToolButton('{$tool->id}');{/if}"
          {/if}
          />
      {else}
         <span>{t}{$tool->id}{/t}</span>
      {/if}
      {if $toolbar_rendering == 'radio'}
        </label>&nbsp;
      {/if}
    {/if}
  {/if}
  {/foreach}
