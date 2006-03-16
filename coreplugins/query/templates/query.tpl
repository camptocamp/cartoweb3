{if $query_display_selection|default:''}
<h3>{t}Query information:{/t}</h3>

<table>
<tr><th>{t}Layer{/t}</th><th>{t}In Query{/t}</th><th>{t}Policy{/t}<br/>u&nbsp;&nbsp;x&nbsp;&nbsp;n</th><th>{t}Mask Mode{/t}</th><th>{t}Hilight{/t}</th>
{if $query_hilightattr_active|default:''}<th>{t}Attributes{/t}</th><th>{t}Table{/t}</th>{/if}</tr>
{foreach from=$query_selections item=selection key=index}
<tr>
<td>{$selection->layerLabel}
<input type="hidden" value="{$selection->layerId}" name="query_layerid[]"/></td>
<td>
<input type="checkbox" value="{$selection->layerId}" name="query_inquery[]"
        {if $selection->useInQuery} checked="checked"{/if}/>
</td>
<td>
<input type="radio" value="POLICY_UNION" name="query_policy_{$index}"
        {if $selection->policy == 'POLICY_UNION'} checked="checked"{/if}/>
<input type="radio" value="POLICY_XOR" name="query_policy_{$index}"
        {if $selection->policy == 'POLICY_XOR'} checked="checked"{/if}/>
<input type="radio" value="POLICY_INTERSECTION" name="query_policy_{$index}"
        {if $selection->policy == 'POLICY_INTERSECTION'} checked="checked"{/if}/>
</td>
<td>
<input type="checkbox" value="{$selection->layerId}" name="query_maskmode[]"
        {if $selection->maskMode} checked="checked"{/if}/>
</td>
<td>
<input type="checkbox" value="{$selection->layerId}" name="query_hilight[]"
        {if $selection->hilight} checked="checked"{/if}/>
</td>
{if $query_hilightattr_active|default:''}<td>
<input type="checkbox" value="{$selection->layerId}" name="query_attributes[]"
        {if $selection->returnAttributes == '1'} checked="checked"{/if}/>
</td>
<td>
<input type="checkbox" value="{$selection->layerId}" name="query_table[]"
        {if $selection->returnTable == '1'} checked="checked"{/if}/>
</td>{/if}
</tr>
{/foreach}
</table>
<p>{t}Query all selected layers{/t}&nbsp;
<input type="checkbox" value="1" name="query_alllayers"
        {if $query_alllayers} checked="checked"{/if}/>
</p>
{/if}
<p>
<input type="submit" name="query_clear" value="{t}query_clear{/t}" class="form_button"
    onclick="return CartoWeb.trigger('Query.Clear');" />
</p>
