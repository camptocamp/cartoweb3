{if $show_login}
        <a href="{$smarty.server.SCRIPT_NAME}?login">{t}login{/t}</a>
{/if}
{if $show_logout}
        <a href="{$smarty.server.SCRIPT_NAME}?logout">{t}logout{/t}</a>
{/if}
