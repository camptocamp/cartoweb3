<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" href="../../{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <title>{t}Search service{/t}</title>
  <script type="text/javascript" src="../../{r type=js}x_cartoweb.js{/r}"></script>
  
</head>

<body>
<fieldset style="font-size:0.9em;">
<legend>{t}Recherche{/t}</legend>
<center>
<form method="post" action="{$selfUrl}?project=demoCW3" name="idform">
<input type="hidden" name="searchpost" value="1" />
<select name="searchLayer" onChange="this.form.submit()" class="select">
<option value="0">Recherche par ...</option>
{html_options options=$searchLayer selected=$layerSelected}
</select>
<br />
{if $inputActive|default:false}
  <input type="text" class="input_text" name="input" value="{$input|default:'Saisissez un nom'}" onfocus="this.value=''"><br />
{/if}

{if $searchInputActive|default:false}
{literal}
  <script language="JavaScript" type="text/javascript">
    <!--
    window.onload = function(){
      {/literal}
      affiche_results({$nbResults})
      {literal}
    }
    
    function affiche_results(nbResults){
      //alert(document.getElementById('list').innerHTML);
      //document.getElementById('list').style.visibility = 'visible';
      var string ='';
      var resultdiv = xGetElementById('results');
      
      if(nbResults == 0){
        string +="Aucun resultat ne correspond a votre requete";
      }  else if(nbResults == 1){
        {/literal}
        string += 'layer : {$layerSelected}';
	string += '<br />';
	string += 'id : {$value_alone}';
	carto_form=parent.document.forms["carto_form"];
        carto_form.id_recenter_layer.value="{$layerSelected}";
        carto_form.id_recenter_ids.value="{$value_alone}";
	{literal}
        carto_form.recenter_doit=1;carto_form.submit();
      }else{
        xGetElementById('list').style.visibility = 'visible';
      }
      resultdiv.innerHTML = string;
    }
    //-->
  </script>
  {/literal}
  <div id="results">
  </div>
  <div id="list" style="visibility:hidden">
    <!--{$layerSelected}-->
    <select name="recherche" id="select" class="select" style="width:150px;" onChange='carto_form=parent.document.forms["carto_form"];
    carto_form.id_recenter_layer.value="{$layerSelected}";
    carto_form.id_recenter_ids.value=this.form.recherche.value;
    alert(this.form.recherche.value);
    carto_form.recenter_doit=1;carto_form.submit();'>
      <!---->
      <option value="0">S&eacute;lectionnez un nom </option>
      {html_options options=$inputList}
   </select>
 </div>
<br />
{/if}

</form>
</center>
</fieldset>

</body>
</html>
