<div id="id_recentering">
  <table>
    <tr>
      <td align="left">
        {html_radios name="id_recenter_layer" values=$id_recenter_layers_id 
        checked=$id_recenter_selected output=$id_recenter_layers_label separator="<br />"}
      </td>
    </tr>
    <tr>
      <td>&nbsp;<input type="text" id="input_name_recenter" name="input_name_recenter" />
      <input type="submit" name="OK" value="OK" class="form_button" style="margin:0px"/></td>
    </tr>
    
    {if $id_recenter_active}
      {literal}
      <script language="JavaScript" type="text/javascript">
      <!--
      window.onload = function(){
      {/literal}
      affiche_results({$nb_results})
        {literal}
      }
    
      function affiche_results(nb_results){
        if(nb_results == 0){
          xGetElementById('result_div').style.display = 'none';
          xGetElementById('result_div_empty').style.display = 'inline';
        }else{
          xGetElementById('result_div').style.display = 'inline';
          xGetElementById('result_div_empty').style.display = 'none';
        }
      }
      //-->
      </script>
      {/literal}
      
    <tr>
      <td><br /></td>
    </tr>
    <tr>
      <td align="left">
        <div id="result_div" style="display:none">
          <table>
          <tr><td align="center">
              <select name="id_recenter_ids">
                {html_options options=$id_recenter_ids_list}
              </select>
          </td></tr>
          <tr><td align="center">
            <input type="submit" name="refresh" value="{t}Recenter{/t}" 
            class="form_button" />
          </td></tr>
          </table>
        </div>
        <div id="result_div_empty" style="display:none">
          <table>
          <tr>
            <td>{t}Aucun resultat ne correspond a votre requete{/t}</td>
          </tr>
          </table>
        </div>
      </td>
    </tr>
    {/if}
  </table>
</div>
