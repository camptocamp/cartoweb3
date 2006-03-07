<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <style type="text/css">
  {literal}
  body {padding: 40px; margin:70px; background-color:#efefef; border: 1px solid #cecece;}
  {/literal}
  </style>
  <title>{t}Login dialog{/t}</title>
</head>

<body>
 <table cellpadding="0" cellspacing="0" align="center">
  <form method="post" action="{$selfUrl}">
  <tr>
   <th colspan="2" align="center"><h3>{t}Login dialog{/t}</h3></th>
  </tr>
  <tr>
   <td colspan="2" align="center">
    {if $reason eq "logout"}
     <p style="color:blue;">{t}You've been logged out{/t}</p>
    {elseif $reason eq "loginFailed"}
     <p style="color:red;">Login failure</p>
    {/if}
   </td>
  </tr>
  <tr>
   <td colspan="2" align="center"><p>{t}Please enter your login and password{/t}</p></td>
  </tr>
  <tr>
   <td colspan="2" align="center"><p>{t}(for demonstration use: 'demo' 'demo'){/t}</p></td>
  </tr>
  <tr>
   <td><label>{t}Username{/t}:</label></td>
   <td align="right"><input type="text" name="username" value="" /></td>
  </tr>
  <tr>
   <td><label>{t}Password{/t}:</label></td>
   <td align="right"><input type="password" name="password" value="" /></td>
  </tr>
  <tr>
   <td colspan="2" align="center">
    <input type="submit" value="{t}Submit{/t}" />
   </td>
  </tr>
  <tr>
   <td colspan="2" align="center">
    <p><br /><a href="{$selfUrl}">{t}Click here to go back to the map{/t}</a></p>
   </td>
  </form>
 </table>
</body>
</html>
