<div>
<span class="stats_label">{$stats_label}{if $mandatory}<span class="mandatory_stats_option">*</span>{/if}</span>
<span class="stats_field">
<select id="stats_{$stats_id}" multiple name="stats_{$stats_id}[]" size="4"
    {if $stats_onchange|default:''}onChange="CartoWeb.trigger('StatsReports.RefreshOptions');"{/if}>
  {html_options options=$stats_options selected=$stats_selected}
</select>
</span>
</div>
