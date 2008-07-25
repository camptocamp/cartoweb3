{* the line below is used to display or not the "default" switch. See check on line 6.
there is also an extra check on line 9-10 to have a switch selected by default when we do not want to display the "default" switch (you need to set a relatedDefaultSwitchId in the layers.ini on client side). *}
{if $relatedDefaultSwitchId != ''}{assign var='defaultSwitchIdRef' value='default'}{else}{assign var='defaultSwitchIdRef' value=''}{/if}
<span class="switchheader">{t}Switches{/t}:</span>
<ul id="switchmenucontainer">
{section name=switchlist loop=$switch_values}
{if $switch_values[switchlist] != $defaultSwitchIdRef}
<li class="switchmenu switchmenu_{$switch_values[switchlist]}_{$currentLang} {if $switch_values[switchlist] == $switch_id || 
                         ($switch_values[switchlist] == $relatedDefaultSwitchId && 
                          $switch_id == 'default')}switchmenuactiv{/if}" id="switch_{$switch_values[switchlist]}"
    onclick="javascript: switchSwitch('{$switch_values[switchlist]}');
                         CartoWeb.trigger('Layers.LayerDropDownChange', 'FormItemSelected()');" 
    ><div class="switchmenu_l"></div><div class="switchmenu_r"></div>{t}{$switch_labels[switchlist]}{/t}</li>
{/if}
{/section}
</ul>
{* because we replaced the original dropdown input, we use a hidden input to send the switch value back to the server (the value is set via the  javascript function switchSwitch in layers.js) *}
<input type="hidden" name="switch_id" id="switch_id" />
