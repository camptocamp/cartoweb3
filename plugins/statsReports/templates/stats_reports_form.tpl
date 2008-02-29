<fieldset>
 <legend class="stats_blocktitle">{t}Rapport{/t}</legend>
 <div class="stats_block">
  <span class="stats_label">{t}Configuration{/t}</span>
  <span class="stats_field">
  <select id="stats_data" name="stats_data" onChange="changeData();">
    {html_options options=$stats_data_options selected=$stats_data}
  </select>
  </span>
<div id="stats_report_block" name="stats_report_block" style="display: none"></div>
 </div>
</fieldset>
<div id="stats_legend_block"></div>
