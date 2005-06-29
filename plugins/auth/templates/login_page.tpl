<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <style type="text/css">
  {literal}
  body {margin:10px;}
  form div {width:250px;height:20px;}
  input {float:right;}
  {/literal}
  </style>
  <title>{t}Cartoclient Title{/t} - {t}Login dialog{/t}</title>
</head>

<body>

<h3>{t}Cartoclient Title{/t} - {t}Login dialog{/t}</h3>

{if $reason eq "logout"}
<p style="color:blue;">{t}You've been logged out{/t}</p>
{elseif $reason eq "loginFailed"}
<p style="color:red;">Login failure</p>
{/if}

<p>{t}Please enter your login and password{/t}</p>

<form method="post" action="{$selfUrl}">
<div><input type="text" name="username" value="" />{t}Username{/t}: </div>
<div><input type="password" name="password" value="" />{t}Password{/t}:</div>
<div><input type="submit" value="{t}Submit{/t}" /></div>
</form>

<p><a href="{$selfUrl}">{t}Click here to go back to the map{/t}</a></p>

</body>
</html>
