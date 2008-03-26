<fieldset>
<legend>{t}Filter criteria{/t}</legend>
{foreach from=$formObjects key=cname item=crit}
  <div class="layer_filter_crit">
    <h4>{if $i18n}{t}{$crit->title}{/t}{else}{$crit->title}{/if}</h4>
    {if $crit->type == 'radio'}
      {foreach from=$crit->options key=oname item=opt}
        <input type="radio" name="{$cname}" id="{$cname}_{$oname}" value="{$oname}" {if $opt->selected|default:''}checked="checked"{/if} />
        <label for="{$cname}_{$oname}">{if $i18n}{t}{$opt->label}{/t}{else}{$opt->label}{/if}</label><br />
      {/foreach}
    {elseif $crit->type == 'dropdown'} 
      <select name="{$cname}" id="{$cname}">
      {foreach from=$crit->options key=oname item=opt}
        <option value="{$oname}" {if $opt->selected|default:''}selected="selected"{/if}>{if $i18n}{t}{$opt->label}{/t}{else}{$opt->label}{/if}</option>
      {/foreach}
      </select>
    {else}
      {foreach from=$crit->options key=oname item=opt}
        <input type="checkbox" name="{$cname}_{$oname}" id="{$cname}_{$oname}" value="1" {if $opt->selected|default:''}checked="checked"{/if} />
        <label for="{$cname}_{$oname}">{if $i18n}{t}{$opt->label}{/t}{else}{$opt->label}{/if}</label><br />
      {/foreach}
    {/if}
  </div>
{/foreach}
</fieldset>
<input type="submit" id="layerFilterSubmit" name="layerFilterSubmit" value="{t}Search{/t}" />
<input type="submit" id="layerFilterReset" name="layerFilterReset" value="{t}Reset{/t}" />
