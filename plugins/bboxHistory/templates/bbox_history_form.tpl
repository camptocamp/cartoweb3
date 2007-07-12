<span id="bbox_history_form">
  <img src="{r type=gfx plugin=bboxHistory}prev.png{/r}"
       title="{t}Back{/t}"
{if $has_prev}
       onclick="return CartoWeb.trigger('BboxHistory.moveTo', null, {ldelim}steps: -1{rdelim})"
       style="cursor: pointer"
{else}
       style="opacity: 0.5; filter:alpha(opacity=50);"
{/if}
 alt="Previous" />

  <img src="{r type=gfx plugin=bboxHistory}next.png{/r}"
       title="{t}Next{/t}"
{if $has_next}
       onclick="return CartoWeb.trigger('BboxHistory.moveTo', null, {ldelim}steps: +1{rdelim})"
       style="cursor: pointer"
{else}
       style="opacity: 0.5; filter:alpha(opacity=50);"
{/if}
 alt="Next" />
</span>
