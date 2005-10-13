{if $show_login}
        <a href="{$smarty.server.PHP_SELF}?login">{t}login{/t}</a>
{/if}
{if $show_logout}
        <a href="{$smarty.server.PHP_SELF}?logout">{t}logout{/t}</a>
{/if}
