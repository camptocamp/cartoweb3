<!--
/*
 * CartoWeb dev team merge manage_servers.phtml and manage_servers_body.phtml
 * and make small modifs:
 * - delete chameleon specific translation mechanism
 * - delete chameleon specific ressource location mechanism
 * - add smarty template engine modifiers to translate text and specify 
 *   ressource location
 * - delete most hidden variables and use smarty variables (especially to fetch
 *   servers properties)
 * - fetch and simplify manage_servers_js.phtml showProperties function
 * - add window.onload javascript function
 */
////////////////////////////////////////////////////////////////////////////////
// MapBrowser application                                                     //
//                                                                            //
// @project     MapLab                                                        //
// @purpose     This is the dbase database management utility page.           //
// @author      William A. Bronsema, C.E.T. (bronsema@dmsolutions.ca)         //
// @copyright                                                                 //
// <b>Copyright (c) 2002, DM Solutions Group Inc.</b>                         //
// Permission is hereby granted, free of charge, to any person obtaining a    //
// copy of this software and associated documentation files(the "Software"),  //
// to deal in the Software without restriction, including without limitation  //
// the rights to use, copy, modify, merge, publish, distribute, sublicense,   //
// and/or sell copies of the Software, and to permit persons to whom the      //
// Software is furnished to do so, subject to the following conditions:       //
//                                                                            //
// The above copyright notice and this permission notice shall be included    //
// in all copies or substantial portions of the Software.                     //
//                                                                            //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR //
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,   //
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL   //
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER //
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING    //
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER        //
// DEALINGS IN THE SOFTWARE.                                                  //
////////////////////////////////////////////////////////////////////////////////
-->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{t}Manage servers{/t}</title>
<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
<link rel="stylesheet" type="text/css" title="stylesheet"
      href="{r plugin=wmsBrowser type=css}wmsBrowser.css{/r}" />
<script type="text/javascript" 
        src="{r plugin=wmsBrowser type=js}manageServers.js{/r}"></script>
<script type="text/javascript" src="{r type=js}x_cartoweb.js{/r}"></script>
<script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
<script type="text/javascript">
  /*<![CDATA[*/
    {literal}
    initialize = function() {
      myform = document.forms['manageServersForm'];
      myform.command.value = '';
      {/literal}
      noFirstLoad = xGetElementById('noFirstLoad');
      {literal}
      if (noFirstLoad.value != 1)
        noFirstLoad.value = 1;
      else
        opener.doSubmit();
    }
    EventManager.Add(window, 'load', initialize, false);
    
    // update the properties boxes with the correct information
    function showProperties() {
      for (i=0; i<myform.Servers.length; i++) {
        if (myform.Servers[i].selected == true) {
          var serverUrl = myform.Servers[i].value;
          myform.selectedServer.value = serverUrl;
          {/literal}
          {foreach from=$servers item=item}
            var url = '{$item.capab_url}';
            {literal}
            if (url ==  serverUrl) {
              {/literal}
              myform.url.value = '{$item.capab_url}';
              myform.comment.value = '{$item.comment}';
              myform.fileStatus.value = '{$item.file_status}';
              {literal}
            }
          {/literal}
          {/foreach}
          {literal}
        }
      }
    }
    {/literal}
  /*]]>*/
</script>

</head>
<body bgcolor="#ffffff">
<form method="post" name="manageServersForm" onsubmit="doSubmit();"
      action="{$selfUrl}?project={$project}&amp;manageServersPopup=1"> 
  <input type="hidden" id="selectedServer" name="selectedServer" value="" />
  <input type="hidden" id="command" name="command" value="" />
  <input type="hidden" id="noFirstLoad" name="noFirstLoad" value="{$noFirstLoad}" />
  <table id="content" border="0" cellpadding="1" cellspacing="10" 
         style="z-index:0;">
    <tr><td class="layoutTable">
      <table width="100%" border="0" cellpadding="2" cellspacing="0">
        <tr><td class="titleArea">
          <img width="22" height="22" alt="manageServersTitle"
               src="{r plugin=wmsBrowser type=gfx/layout/manageServers}manageServersTitle.gif{/r}" />
          <span class="title">
            &nbsp;{t}Manage servers{/t}
          </span>
        </td></tr>
      </table>
      <table class="contentArea" width="100%" border="0" cellpadding="4" cellspacing="0">
        <tr><td align="center">
          <p class="helpArea">
{t}In the list of available datastores below, server names prefaced with [c] and [d] are 'Connected' and 'Disconnected' respectively.
To modify a server's properties, select it from the list.{/t}
          </p>
        </td></tr>
      </table>
      <table class="contentArea" border="0" cellspacing="0" 
             cellpadding="4" width="100%">
        <tr><td colspan="2">
          <span class="label">
            <strong>{t}Available servers{/t}</strong>
          </span>
        </td></tr>
        <tr><td valign="top">
          <span class="inputWrap">
            <select class="inputList" name="Servers" size="8" 
                    onchange="javascript:showProperties()">
              {foreach from=$servers item=server}
              <option value="{$server.capab_url}">
                {if $server.con_status}
                [c] - {$server.title|truncate:30:"...":true}
                {else}
                [d] - {$server.title|truncate:30:"...":true}
                {/if}
              </option>
              {/foreach}
            </select>
          </span>
        </td><td>
      <table cellpadding="2" cellspacing="0" border="0">
        <tr><td>
          <a href="javascript:processCommand('CONNECT')">
            <img name="connect" alt="{t}Connect selected server{/t}"
                 src="{r plugin=wmsBrowser type=gfx/layout/manageServers}connect_{$currentLang}.gif{/r}"
                 title="{t}Connect selected server{/t}" 
                 border="0" />
          </a>
          </td></tr>
          <tr><td>
            <a href="javascript:processCommand('DISCONNECT')">
              <img name="disconnect" alt="{t}Disconnect selected server{/t}" 
                   src="{r plugin=wmsBrowser type=gfx/layout/manageServers}disconnect_{$currentLang}.gif{/r}"
                   title="{t}Disconnect selected server{/t}" border="0" />
            </a>
          </td></tr>
          <tr><td>
            <a href="javascript:processCommand('REFRESH')">
              <img name="refresh" alt="{t}Refresh selected server{/t}"
                   src="{r plugin=wmsBrowser type=gfx/layout/manageServers}refresh_{$currentLang}.gif{/r}"
                   title="{t}Refresh selected server{/t}" border="0" />
            </a>
          </td></tr>
          <tr><td>
            <a href="javascript:processCommand('TEST')">
              <img name="test" alt="{t}Test selected server{/t}"
                   src="{r plugin=wmsBrowser type=gfx/layout/manageServers}test_{$currentLang}.gif{/r}" 
                   title="{t}Test selected server{/t}" border="0" />
            </a>
          </td></tr>
          <tr><td>
            <a href="javascript:processCommand('REMOVE')">
              <img name="remove" alt="{t}Remove selected server{/t}" 
                   src="{r plugin=wmsBrowser type=gfx/layout/manageServers}remove_{$currentLang}.gif{/r}"
                   title="{t}Remove selected server{/t}" border="0" />
            </a>
          </td></tr>
        </table>
      </td></tr>
    </table>
    <table class="contentArea" width="100%" border="0" cellpadding="4" 
           cellspacing="0">
      <tr><td align="center">
        <p class="helpArea">
{t}Please supply the properties below then click 'Add' to add a new server to the datastores list. 
*The only required field is URL.{/t}
        </p>
      </td></tr>
    </table>
    <table class="contentArea" width="100%" border="0" cellpadding="4" 
           cellspacing="0">
      <tr><td colspan="2"><span class="label">
        <strong>{t}Server properties{/t}</strong>
      </span></td></tr>
      <tr>
        <td valign="top" align="right">
          <span class="label">URL*:</span>
        </td><td>
          <input type="text" class="inputBox" name="url" id="url" size="50" />
        </td>
      </tr><tr>
        <td valign="top" align="right">
          <span class="label">{t}Comments{/t}:</span>
        </td><td>
          <textarea class="inputBox" name="comment" rows="2" cols="25"></textarea>
          <table cellpadding="2" cellspacing="0" border="0">
            <tr>
              <td><a href="javascript:processCommand('ADD')">
                <img name="add" alt="{t}Add server{/t}"
                     src="{r plugin=wmsBrowser type=gfx/layout/manageServers}add_{$currentLang}.gif{/r}" 
                     title="{t}Add server to list{/t}" border="0" />
              </a></td>
              <td><a href="javascript:processCommand('UPDATE')">
                <img name="update" alt="{t}Update server entry{/t}" 
                     src="{r plugin=wmsBrowser type=gfx/layout/manageServers}update_{$currentLang}.gif{/r}" 
                     title="{t}Update server entry{/t}" border="0" />
              </a></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr><td colspan="2">
        <hr size="1" noshade="noshade" />
      </td></tr>
      <tr>
        <td align="right">
          <span class="label">{t}Server last refreshed{/t} :</span>
        </td><td>
          <input type="text" class="inputBox" name="fileStatus" size="50" />
        </td>
      </tr><tr>
        <td align="right">
          <span class="label">{t}Output{/t} :</span><br />
            <img width="65" height="40" 
                 src="{r plugin=wmsBrowser type=gfx/layout/manageServers}{if $userLogStatus}pixel{else}alert{/if}.gif{/r}"
                 alt="userLogImg" />
        </td><td>
          <textarea class="inputBox" name="status" rows="4" cols="40">
{include file="userLog.tpl"}
          </textarea>
        </td></tr>
      </table>    
    </td></tr>
    <tr><td align="right">
      <a href="javascript:window.close()">
        <img border="0" 
             title="{t}Close this dialog{/t}" alt="{t}Close{/t}"
             src="{r plugin=wmsBrowser type=gfx/layout/manageServers}close_{$currentLang}.gif{/r}" />
      </a>
    </td></tr>
  </table>
  {include file="loadingbar.tpl"}
</form>
</body>
</html>
