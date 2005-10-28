<div id="query">
{if $query_display_selection|default:''}
{foreach from=$query_selections item=selection key=index}
<fieldset>
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


<p>{t}Query all selected layers{/t}&nbsp;
<input type="checkbox" value="1" name="query_alllayers"
        {if $query_alllayers} checked="checked"{/if}/>
</p>
{/if}
<p>
<input type="submit" name="query_clear" value="{t}query_clear{/t}" class="form_button" />
</p>
</div>

