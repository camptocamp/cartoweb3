  <p>
    {foreach from=$tools item=tool}
    <label for="{$tool->id}">
      <input type="radio" name="tool" value="{$tool->id}"{if $selected_tool == $tool->id}checked="checked"{/if} id="{$tool->id}" />
      {if $tool->hasIcon}
      <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" alt="{$tool->id}" title="{t}{$tool->id}{/t}" />
      {else}  
      {t}{$tool->id}{/t}
      {/if}   
    </label>&nbsp;
    {/foreach}   
  </p>
