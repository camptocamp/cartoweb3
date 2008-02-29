<span class="stats_label">{t}Visualisation{/t}</span>
<span class="stats_field">
<select id="stats_display" name="stats_display" onChange="changeDisplay();">
  {html_options options=$stats_display_options selected=$stats_display}
</select>
</span>
<div class="stats_subblock" id="stats_options_block" name="stats_options_block" style="display: none"></div>

<div class="stats_subblock"><input type="button" value="{t}Generer{/t}" onclick="CartoWeb.trigger('StatsReports.ComputeReport');"/></div>
