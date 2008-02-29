<div class="stats_subblock">
<span class="stats_label">{$stats_label_column}</span>
<span class="stats_field">
<select id="stats_column" name="stats_column">
  {html_options options=$stats_column_options selected=$stats_column}
</select>
</span>
</div>
<div>
<span class="stats_label">{$stats_label_line}</span>
<span class="stats_field">
<select id="stats_line" name="stats_line">
  {html_options options=$stats_line_options selected=$stats_line}
</select>
</span>
</div>
