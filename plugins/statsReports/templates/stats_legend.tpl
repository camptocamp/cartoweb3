  <fieldset>
    <legend class="stats_blocktitle">{t}Legende{/t}</legend>
    <div class="stats_block">
<table width="100%">
  <tbody>
   <tr>
{section name=legend start=0 loop=$stats_legend}  
  {if $smarty.section.legend.iteration % 2 == 1 && !$smarty.section.legend.first}</tr><tr>{/if}
   <td><img src="generated/stats/legends/{$stats_user}_{$smarty.section.legend.index}.png" 
        width="20" height="15" title="" alt="" />&nbsp;&nbsp;{$stats_legend[legend]}</td>
{/section}
   </tr>
  </tbody>
</table>
    </div>
  </fieldset>
