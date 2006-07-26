<script type="text/javascript" src="{r type=js plugin=locate}scriptaculous.js{/r}?load=effects,controls" ></script>
<script type="text/javascript">
{literal}
function locate(input_element, item) {
    Logger.trace('locate called from locate.tpl');
    if (!input_element.alt){
        throw ('alt undefined for element ' + input_element.id + '. The alt value must contain the recenter layer.');
    }
    if((typeof document.forms['carto_form'].id_recenter_layer =='undefined') || 
       (typeof document.forms['carto_form'].id_recenter_ids == 'undefined'))
       throw('id_recenter inputs are needed in the html form');
    document.forms['carto_form'].id_recenter_layer.value = input_element.alt;
    document.forms['carto_form'].id_recenter_ids.value = item.id;
    CartoWeb.trigger('Location.Recenter', 'doSubmit()');
}
{/literal}
</script>
<div id="recherche_locate" class="locate">
  <table width="100%" cellspacing="0" cellpadding="0">
    {foreach from=$locates item=locate key=index}
      <tr id="locate_{$locate->id}_tr">
      <td class="label">
        <label for="locate_{$locate->id}">{t}{$locate->label}{/t}</label>
      </td>
      <td colspan="2">
        <input type="text" name="locate_{$locate->id}" id="locate_{$locate->id}" alt="{$locate->id}" class="locinput" />
        <div class="locate_results" id="locate_{$locate->id}_results"></div>
        <script type="text/javascript">
          new Ajax.Autocompleter('locate_{$locate->id}', 'locate_{$locate->id}_results', '{$selfUrl}?locate_layer_id={$locate->id}', {literal}{afterUpdateElement: locate}{/literal});
        </script>
      </td>
    </tr>
    {/foreach}
  </table>
</div>