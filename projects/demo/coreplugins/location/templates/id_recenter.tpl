<div id="id_recentering">
<fieldset>
<legend>{t}Id Recentering{/t}</legend>
  <table width="100%">
    <tr>
      <td align="left">
        <label>{t}Layer to center on:{/t}</label>
      </td>
    </tr>
    <tr>
      <td align="center">
        <select name="id_recenter_layer" id="id_recenter_layer">
        {html_options values=$id_recenter_layers_id 
           selected=$id_recenter_selected 
           output=$id_recenter_layers_label}
    </select>
      </td>
    </tr>
    <tr>
      <td align="left"><label>{t}Comma separated id's:{/t}</label></td>
    </tr>
    <tr>
      <td align="center">
        <input type="text" id="id_recenter_ids" name="id_recenter_ids" /> 
      </td>
    </tr>
    <tr>
      <td align="center">
        <input type="submit" name="refresh" value="{t}Recenter{/t}" 
     class="form_button" onclick="javascript:AjaxHandler.doAction('Location.Recenter');return false;" />
      </td>
    </tr>
  </table>
</fieldset>
</div>
