<div id="id_recentering">
  <table>
    <tr>
      <td align="left">
        {html_radios name="id_recenter_layer" values=$id_recenter_layers_id 
        checked=$id_recenter_selected output=$id_recenter_layers_label separator="<br />"}
      </td>
    </tr>
    <tr>
      <td>&nbsp;<input type="text" id="input_name_recenter" name="input_name_recenter" class="input_text"/>
      <input type="submit" name="OK" value="OK" class="form_button" style="margin:0px" onclick='doSubmit();'/></td>  
    </tr>
    
    {if $id_recenter_active}
    <tr>
      <td><br /></td>
    </tr>
    <tr>
      <td align="left">
        {if $nb_results != 0 && $nb_results != 1}
        <table>
        <tr><td align="center">
          <select name="id_recenter_ids">
           {html_options options=$id_recenter_ids_list}
          </select>
        </td></tr>
        <tr><td align="center">
          <input type="submit" name="refresh" value="{t}Recenter{/t}" 
          class="form_button"  onclick='doSubmit();'/>
        </td></tr>
        </table>
        {/if}
        {if $nb_results == 0}
        <table>
          <tr>
            <td>{t}No result for your query{/t}</td>
          </tr>
        </table>
        {/if}
      </td>
    </tr>
    {/if}
  </table>
</div>