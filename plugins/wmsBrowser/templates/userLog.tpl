{strip}
{if $userLog.action != ''}
  {if $userLog.action == 'ADD'}
    {if $userLog.case == 0}
      {t}The following parameters should not be included in the URL:{/t}{"\n"}
      {foreach from=$userLog.urlParams item=param}
        {$param}{"\n"}
      {/foreach}
    {else}
      {t 1=$userLog.serverUrl}Adding server [%1] to list .... {/t}
      {if $userLog.case == 1}
        {t}Failed{/t}{"\n"}
        {t}Adding server .... cancelled [this server is already registred in wms cache]{/t}{"\n"}
      {else}
        {t}OK{/t}{"\n"}
        {t 1=$userLog.serverUrl}Testing server [%1] .... {/t} 
        {if $userLog.case == 2}
          {t}Not available{/t}{"\n"}
          {t 1=$userLog.serverUrl}Removing server [%1] .... OK{/t}{"\n"}
        {else}
          {t}OK{/t}{"\n"}
          {t 1=$userLog.serverUrl}Refreshing server [%1] .... {/t} 
          {if $userLog.case == 3}
            {t}Failed{/t}{"\n"}
            {t}An error occured parsing capabilities...{/t}{"\n"}
            {t}Removing server .... OK{/t}{"\n"}
          {else}
            {t}OK{/t}{"\n"}
          {/if}
        {/if}
      {/if}
    {/if}
  {/if}
  {if $userLog.action == 'UPDATE'}
    {if $userLog.case == 0}
      {t}The following parameters should not be included in the URL:{/t}{"\n"}
      {foreach from=$userLog.urlParams item=param}
        {$param}{"\n"}
      {/foreach}
    {else}
      {t 1=$userLog.serverUrl}Updating existing server [%1] .... OK{/t}{"\n"}
      {t 1=$userLog.serverUrl}Testing server [%1] .... {/t}
      {if $userLog.case == 1}
        {t}Not available{/t}{"\n"}
        {t 1=$userLog.serverUrl}Disconnecting server [%1] .... OK{/t}{"\n"}
      {else}
        {t}OK{/t}{"\n"}
        {t 1=$userLog.newServerUrl}Refreshing server [%1] .... {/t}
        {if $userLog.case == 2}
          {t}Failed{/t}{"\n"}
          {t}An error occured parsing capabilities...{/t}{"\n"}
          {t}Removing server .... OK{/t}{"\n"}
        {else}
          {t}OK{/t}{"\n"}
        {/if}
      {/if}
    {/if}
  {/if}
  {if $userLog.action == 'CONNECT'}
    {if $userLog.case == 0}
      {t}Connecting server .... cancelled [no server selected]{/t}{"\n"}
    {else}
      {t 1=$userLog.serverUrl}Testing server [%1] .... {/t}
      {if $userLog.case == 1}
        {t}Not available{/t}{"\n"}
        {t 1=$userLog.serverUrl}Disconnecting server [%1] .... OK{/t}{"\n"}
      {else}  
        {t}OK{/t}{"\n"}
        {t 1=$userLog.serverUrl}Connecting server [%1] .... OK{/t}{"\n"}
      {/if}
    {/if}
  {/if}
  {if $userLog.action == 'DISCONNECT'}
    {if $userLog.case == 0}
      {t}Disconnecting server .... cancelled [no server selected]{/t}{"\n"}
    {else}
      {t 1=$userLog.serverUrl}Disconnecting server [%1] .... OK{/t}{"\n"}
    {/if}
  {/if}
  {if $userLog.action == 'REMOVE'}
    {if $userLog.case == 0}
      {t}Removing server .... cancelled [no server selected]{/t}{"\n"}
    {else}
      {t 1=$userLog.serverUrl}Removing server [%1] .... OK{/t}{"\n"}
    {/if}
  {/if}
  {if $userLog.action == 'REFRESH'}
    {if $userLog.case == 0}
      {t}Removing server .... cancelled [no server selected]{/t}{"\n"}
    {else}
      {t 1=$userLog.serverUrl}Testing server [%1] .... {/t}
      {if $userLog.case == 1}
        {t}Not available{/t}{"\n"}
        {t 1=$userLog.serverUrl}Disconnecting server [%1] .... OK{/t}{"\n"}
      {else}  
        {t}OK{/t}{"\n"}
        {t 1=$userLog.serverUrl}Refreshing server [%1] .... {/t}
        {if $userLog.case == 2}
          {t}Failed{/t}{"\n"}
          {t}An error occured parsing capabilities...{/t}{"\n"}
          {t}Removing server .... OK{/t}{"\n"}
        {else}
          {t}OK{/t}{"\n"}
        {/if}
      {/if}
    {/if}
  {/if}
  {if $userLog.action == 'TEST'}
    {if $userLog.case == 0}
      {t}Testing server .... cancelled [no server selected]{/t}{"\n"}
    {else}
      {t 1=$userLog.serverUrl}Testing server [%1] .... {/t}
      {if $userLog.case == 1}
        {t}Not available{/t}{"\n"}
      {else}
        {t}OK{/t}{"\n"}
      {/if}
    {/if}
  {/if}
{/if}
{t}Building servers list .... OK{/t}{"\n"}
{if $userLog.nServers == 0}{t}No servers in the list.{/t}{"\n"}{/if}
*** {t}Execution complete{/t}{if !$userLogStatus} - {t}Errors occurred{/t}{/if} ***{"\n"}
{/strip}
