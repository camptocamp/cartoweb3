  <p>
    {counter start=-1 print=false name=tindex}
    {foreach from=$tools item=tool}
    <label for="{$tool->id}">
      <input type="radio" name="tool" 
              value="{$tool->js->shapeType},{$tool->js->action},{$tool->js->cursorStyle},{$tool->id}" 
                {if $selected_tool == $tool->id}checked="checked"{/if}
                id="{$tool->id}" onclick="dhtmlBox.changeTool()" />
           {if $tool->hasIcon}
            <img src="{r type=gfx plugin=$tool->plugin}{$tool->id}.gif{/r}" alt="{$tool->id}" title="{t}{$tool->id}{/t}"
        onclick="CheckRadio('{counter name=tindex}');dhtmlBox.changeTool()" />
           {else}
            <span onclick="CheckRadio('{counter name=tindex}');dhtmlBox.changeTool();">
               {t}{$tool->id}{/t}
            </span>
           {/if}
    </label>&nbsp;
    {/foreach}
  </p>
