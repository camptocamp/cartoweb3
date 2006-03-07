<!--
/* Modifs by CartoWeb dev team on mapbrowser_iframe_en.html:
 * - delete all chameleon widgets
 * - add smarty template engine modifiers to translate text and specify 
 *   ressource location
 * - fetch mapbrowser_template_en.html myTreeClicked and showActivity javascript
 *   functions
 * - simplify myTreeClicked function and rename it to showAbstract
 * - add window.onload, initializeAbstract javascript function
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
  <title>{t}Explore WMS layers - iframe{/t}</title>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" title="stylesheet"
        href="{r plugin=wmsBrowser type=css}wmsBrowser.css{/r}" />
  <script type="text/javascript" src="{r type=js}x_cartoweb.js{/r}"></script>
  <script type="text/javascript" 
          src="{r plugin=wmsBrowser type=js}exploreWmsLayersIframe.js{/r}">
  </script>
  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript">
  /*<![CDATA[*/
    {literal}
    onload = function() {
      myform = document.forms['exploreWmsLayersIframeForm'];
      myform.addWmsLayer.value = 0;
      myform.selectedWmsLayer.value = "";
    }
    EventManager.Add(window, 'load', onload, false);
    function initializeAbstract() {
      {/literal}
      var abstractContent = "<strong>&nbsp;{t}Select a layer{/t}</strong>";
      parent.document.getElementById('abstract').innerHTML = abstractContent;
      {literal}
    }
    function checkMetadatas(metadatas) {
      for (var i = 0; i < metadatas.length; i++) {
        if (metadatas[i] == '') {
          alert("{t}You can't add this layer, one of its metadatas values is null{/t}");
          return false;
        }
      }
      return true;
    }
    function showAbstract(title, abs, metadataUrl) {
      {/literal}
      var abstractContent = "<strong>{t}Title: {/t}</strong>&nbsp;";
      abstractContent += title + "<br />";
      {literal}
      if (abs != '') {
      {/literal}
        abstractContent += "<strong>{t}Abstract: {/t}</strong>&nbsp;"
        abstractContent += abs + "<br />";
      {literal}
      }
      if (metadataUrl != '') {
      {/literal}
        abstractContent += "<strong>{t}Metadatas: {/t}</strong>&nbsp;"
        abstractContent += metadataUrl + "<br />";
      {literal}
      }
      parent.document.getElementById('abstract').innerHTML = abstractContent;
    }
    {/literal}
  /*]]>*/
  </script>
</head>

<body bgcolor="#f0f0f0" >
<form method="post" name="exploreWmsLayersIframeForm" onsubmit="doSubmit()"
      action="{$selfUrl}?project={$project}&amp;exploreWmsLayersIframe=1">
  <input type="hidden" name="addWmsLayer" id="addWmsLayer" />
  <input type="hidden" name="selectedWmsLayer" id="selectedWmsLayer" />
  <input type="hidden" id="openNodes" name="openNodes" />
  <table border="0" cellpadding="0" cellspacing="5">
    <tr>
      <td>
        <select name="wmsServers" id="wmsServers" 
                onchange="initializeAbstract();doSubmit();">
          <option value="0">{t}Select a server...{/t}</option>
          {foreach from=$wmsServers item=server}
          <option value="{$server.capab_url}" {if $server.capab_url == 
                  $selectedWmsServer}selected="selected"{/if}>
            {$server.title|truncate:28:"...":true}
          </option>
          {/foreach}
        </select>
      </td><td>
        <a href="#">
          <img  src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}manageServers_{$currentLang}.gif{/r}" 
                border="0" title="{t}Manage available WMS servers{/t}"
                alt="{t}Manage servers{/t}"
                onclick="manageServersPopup=window.open('{$selfUrl}?project={$project}&amp;manageServersPopup=1','manageServers','scrollbars=yes,resizable=yes,toolbar=no,location=no,width=470,height=680');manageServersPopup.focus();" />
        </a>
      </td>
    </tr><tr>
      <td colspan="2" valign="bottom">
        <div class="rbevel1" id="Layer1" 
             style="position:relative; width:320px; height:320px; overflow: scroll;">
        {if $listLayers}
          {if $wmsLayers.con_status}
            {include file="wmsServerLayersTree.tpl"}
          {else}
            <table width="100%" height="80%">
              <tr><td id="disconnected"><span>
                {t}This server is disconnected. Please connect it in manage servers popup to explore its layers.{/t}
              </span></td></tr>
            </table>
          {/if}
        {/if}
        </div>
      </td>
    </tr>
  </table>
  {include file="loadingbar.tpl"}
</form>
</body>
</html>
