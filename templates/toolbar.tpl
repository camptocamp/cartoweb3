  <p>
    {counter start=-1 print=false name=tindex}
    {foreach from=$tools item=tool}
    {if !$group || $group == $tool->group}
    <label for="{$tool->id}">
      <input type="radio" name="tool" 
              value="{$tool->id}" 
                {if $selected_tool == $tool->id}checked="checked"{/if}
                id="{$tool->id}" onclick="mainmap.{$tool->id}('map');" />
           {if $tool->hasIcon}
            <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" alt="{$tool->id}" title="{t}{$tool->id}{/t}"
        onclick="CheckRadio('{counter name=tindex}');mainmap.{$tool->id}('map');" />
           {else}
            <span onclick="CheckRadio('{counter name=tindex}');mainmap.{$tool->id}('map');">
               {t}{$tool->id}{/t}
            </span>
           {/if}
    </label>&nbsp;
    {/if}
    {/foreach}
  </p>
