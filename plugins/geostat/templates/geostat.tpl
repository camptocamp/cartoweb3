<div id="geostatGUI">

{$geostat_switch}

<hr />

{$geostat_data_source}

<hr />

<h3>{t}Configuration{/t}</h3>

<fieldset>
  <legend>
    <a href="javascript:toggleGeostatPane('geostat_choropleth_dataset')">
      <img src="{r type=gfx plugin=geostat}{if $smarty.post.geostat_choropleth_dataset_display != 'block'}arrow_closed.gif{else}arrow_opened.gif{/if}{/r}"
           id ="geostat_choropleth_dataset_img" />
      {t}Configure dataset...{/t}
    </a>
  </legend>
  <input type="hidden"
         name="geostat_choropleth_dataset_display"
         id="geostat_choropleth_dataset_display"
         value="{$smarty.post.geostat_choropleth_dataset_display|default:'none'}" />
  <div id="geostat_choropleth_dataset"
       style="display: {$smarty.post.geostat_choropleth_dataset_display|default:'none'}">
    {$geostat_choropleth_dataset}
  </div>
</fieldset>

<fieldset>
  <legend>
    <a href="javascript:toggleGeostatPane('geostat_choropleth_representation')">
      <img src="{r type=gfx plugin=geostat}{if $smarty.post.geostat_choropleth_representation_display != 'block'}arrow_closed.gif{else}arrow_opened.gif{/if}{/r}"
           id ="geostat_choropleth_representation_img" />
      {t}Configure representation...{/t}
    </a>
  </legend>
  <input type="hidden"
         name="geostat_choropleth_representation_display"
         id="geostat_choropleth_representation_display"
         value="{$smarty.post.geostat_choropleth_representation_display|default:'none'}" />
  <div id="geostat_choropleth_representation" 
       style="display: {$smarty.post.geostat_choropleth_representation_display|default:'none'}">
    {$geostat_choropleth_representation}
  </div>
</fieldset>


<input type="hidden"
       name="geostatShownElementsIdCsv"
       id="geostatShownElementsIdCsv"
       value="{$geostat_shownElementsIdsCsv}" />

{literal}
<script language="javascript">
    function toggleGeostatPane(elementId) {
        if ($(elementId) == null) return;

        // Toggles element (show/hide)
        Element.toggle(elementId);

        var display = $(elementId).style.display;
        if (display == '') display = 'block';
        
        // Change the arrow img
        var imgPath = {/literal}'{r type=gfx plugin=geostat}{/r}'{literal};
        if (display == 'none') $(elementId + '_img').src = imgPath + 'arrow_closed.gif';
        else $(elementId + '_img').src = imgPath + 'arrow_opened.gif';
        
        // Writes the display state in the hidden field
        $(elementId + '_display').value = display;
    }
</script>
{/literal}

</div>