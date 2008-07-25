{if $relatedDefaultSwitchId != ''}{assign var='defaultSwitchIdRef' value='default'}{else}{assign var='defaultSwitchIdRef' value=''}{/if}
<p>
{t}Switches{/t}
<select name="switch_id" id="switch_id"
    onchange="javascript: CartoWeb.trigger('Layers.LayerDropDownChange', 'FormItemSelected()');">
{section name=switchlist loop=$switch_values}
{if $switch_values[switchlist] != $defaultSwitchIdRef}
<option value="{$switch_values[switchlist]}" {if $switch_values[switchlist] == $switch_id  || 
                                               ($switch_values[switchlist] == $relatedDefaultSwitchId && 
                                                $switch_id == 'default')}selected="selected"{/if}>{t}{$switch_labels[switchlist]}{/t}</option>
{/if}
{/section}
</select></p>
