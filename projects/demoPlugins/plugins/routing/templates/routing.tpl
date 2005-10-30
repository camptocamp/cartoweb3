<center>
<br />
<div id="start" style="text-align:left; margin-left:20px;">{t}Start{/t}</div> 
<select name="routing_from" id="routing_from" style="width:150px;">
      {html_options options=$vertices_options selected=$routing_from}
</select>
<br /><br />
<div id="finish" style="text-align:left; margin-left:20px;">{t}Finish{/t}</div>
<select name="routing_to" id="routing_to" style="width:150px;">
      {html_options options=$vertices_options selected=$routing_to}
</select>
<br /><br />
</center>
<table width="100%">
<tr><td width="50%"></td>
<td width="20%" align="center">
  <a href="javascript:this.form.routing_from.value=0;this.form.routing_to.value=0;FormItemSelected();">
    <img src="{r type=gfx/layout}reinitialize.png{/r}" alt="{t}reset routing{/t}" title="{t}Reset{/t}" />
  </a></td>
<td width="30%" align="left">
  <input type="button" name="refresh" value="{t}OK{/t}"
    class="form_button" onclick="javascript:FormItemSelected();" />
</td>
</tr>
</table>

