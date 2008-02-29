<span class="stats_label">{t}Rapport{/t}</span>
<span class="stats_field">
 <select id="stats_report" name="stats_report" onChange="changeReport();">
  {html_options options=$stats_report_options selected=$stats_report}
 </select>
</span>
<div id="stats_periodtype_block" name="stats_periodtype_block" style="display: none"></div>