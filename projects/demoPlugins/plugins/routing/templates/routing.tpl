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
  <input type="image" src="{r type=gfx/layout}reinitialize.png{/r}" 
  name="routing_reset" alt="{t}reset routing{/t}" value="{t}Reset{/t}"/>
</td>
<td width="30%" align="left">
  <input type="submit" name="routing_submit" value="{t}OK{/t}"
    class="form_button"/>
</td>
</tr>
</table>

