<span id="bbox_history_form">
  <img src="{r type=gfx plugin=bboxHistory}prev.png{/r}"
{if $has_prev}
       onclick="return CartoWeb.trigger('BboxHistory.moveTo', null, {ldelim}steps: -1{rdelim})"
       style="cursor: pointer"
{else}
       style="opacity: 0.5; filter:alpha(opacity=50);"
{/if}
 alt="Previous" />

  <img src="{r type=gfx plugin=bboxHistory}next.png{/r}"
{if $has_next}
       onclick="return CartoWeb.trigger('BboxHistory.moveTo', null, {ldelim}steps: +1{rdelim})"
       style="cursor: pointer"
{else}
       style="opacity: 0.5; filter:alpha(opacity=50);"
{/if}
 alt="Next" />
</span>
