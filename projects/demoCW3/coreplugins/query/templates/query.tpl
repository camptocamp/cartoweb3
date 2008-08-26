<div id="query">

<p style="vertical-align:middle; text-align:right; float:right;">
<a href="javascript:void(0);" onclick="javascript:CartoWeb.trigger('Query.Clear', 'clearQuery()');">
    <img  style="margin-left:5px;" src="{r type=gfx/layout}reinitialize.gif{/r}"
    title="{t}query_clear{/t}" alt="{t}query_clear{/t}" /></a>&nbsp;&nbsp;
<a href="javascript:void(0);" onclick="javascript:CartoWeb.trigger('Query.Perform', 'FormItemSelected();');">
  <img src="{r type=gfx/layout}reload.gif{/r}" name="refresh" alt="refresh" 
  title="{t}Refresh{/t}" />
</a>
</p>

<!--
<p style="text-align:left;">
<input type="checkbox" value="1" name="query_alllayers"
{if $query_alllayers} checked="checked"{/if}/>&nbsp;{t}Query all selected layers{/t}        
</p>
-->

{if $query_display_selection|default:''}
{foreach from=$query_selections item=selection key=index}
<fieldset style="clear: both;">
 <legend>{$selection->layerLabel}</legend>
<input type="hidden" value="{$selection->layerId}" name="query_layerid[]" />

<input type="checkbox" value="{$selection->layerId}" name="query_hilight[]"
        {if $selection->hilight} checked="checked"{/if}/><label>{t}Hilight{/t}</label><br />
<input type="checkbox" value="{$selection->layerId}" name="query_attributes[]"
        {if $selection->returnAttributes == '1'} checked="checked"{/if}/><label>{t}Attributes{/t}</label><br />
<input type="checkbox" value="{$selection->layerId}" name="query_table[]"
        {if $selection->returnTable == '1'} checked="checked"{/if}/>Table<br />


<input type="radio" value="POLICY_UNION" name="query_policy_{$index}"
        {if $selection->policy == 'POLICY_UNION'} checked="checked"{/if}/>
<label>{t}Union{/t}</label>
<input type="radio" value="POLICY_XOR" name="query_policy_{$index}"
        {if $selection->policy == 'POLICY_XOR'} checked="checked"{/if}/>
<label>{t}Xor{/t}</label>
<input type="radio" value="POLICY_INTERSECTION" name="query_policy_{$index}"
        {if $selection->policy == 'POLICY_INTERSECTION'} checked="checked"{/if}/>
<label>{t}Inter{/t}</label><br />


<input type="checkbox" value="{$selection->layerId}" name="query_inquery[]"
        {if $selection->useInQuery} checked="checked"{/if}/><label>{t}In Query{/t}</label>
<br />
<input type="checkbox" value="{$selection->layerId}" name="query_maskmode[]"
        {if $selection->maskMode} checked="checked"{/if}/><label>{t}Mask{/t}</label>
</fieldset>
{/foreach}


{/if}
</div>

