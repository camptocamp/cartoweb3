<div>
<span class="stats_label">{$stats_label}</span>
<span class="stats_field">
<select id="stats_{$stats_id}" multiple name="stats_{$stats_id}[]" size="4">
  {html_options options=$stats_options selected=$stats_selected}
</select>
</span>
</div>
