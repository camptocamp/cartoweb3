<h2>{t}Login dialog{/t}</h2>

<div>
{if $reason eq "logout"}
<div style="color:blue;">{t}You've been logged out{/t}</div>
{/if}

<div>{t}Please enter your login and password{/t}</div>

{if $reason eq "loginFailed"}
<div style="color:red;">Login failure</div>
{/if}

<div>
<form method="post" action="{$smarty.server.PHP_SELF}">
<div>{t}Username{/t}: <input type="text" name="username" /></div>
<div>{t}Password{/t}: <input type="password" name="password" /></div>
<input type="submit" value="{t}Submit{/t}">
</form>
<div>