  <p>
    {counter start=-1 print=false name=tindex}
    {foreach from=$tools item=tool}
    <label for="{$tool->id}">
      <input type="radio" name="tool" value="{$tool->id}"{if $selected_tool == $tool->id}checked="checked"{/if} id="{$tool->id}" />
      {if $tool->hasIcon}
      <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" alt="{$tool->id}" title="{t}{$tool->id}{/t}"
      onclick="CheckRadio('{counter name=tindex}');" />
      {else}  
      <span onclick="CheckRadio('{counter name=tindex}');">{t}{$tool->id}{/t}</span>
      {/if}   
    </label>&nbsp;
    {/foreach}   
  </p>
