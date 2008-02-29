<span class="stats_label">{t}Period{/t}</span>
<span class="stats_field">
  <select id="stats_periodtype" name="stats_periodtype" onChange="changePeriodType();">
   {html_options options=$stats_periodtype_options selected=$stats_periodtype}
  </select>
 </span>
<div id="stats_display_block" name="stats_display_block" style="display: none"></div>
