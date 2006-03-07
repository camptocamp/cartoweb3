<!--
/* Modifs by CartoWeb dev team on mapbrowser_template_en.html:
 * - delete all chameleon widgets
 * - add smarty template engine modifiers to translate text and specify 
 *   ressource location
 * - delete all javascript functions except hideActivityLayer
 * - add addWmsLayer, doSubmit javascript functions
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
  <title>{t}Explore WMS layers{/t}</title>
  <meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
  <link rel="stylesheet" type="text/css" title="stylesheet"
        href="{r plugin=wmsBrowser type=css}wmsBrowser.css{/r}" />
  <script type="text/javascript" src="{r type=js}EventManager.js{/r}"></script>
  <script type="text/javascript" src="{r type=js}x_cartoweb.js{/r}"></script>
  <script type="text/javascript">
  /*<![CDATA[*/
    {literal}
    function hideActivityLayer() {
      xHide(xGetElementById('ActivityLayer'));
    }
    EventManager.Add(window, 'load', hideActivityLayer, false);
    function addWmsLayer() {
      window.frames['exploreWmsLayersIframe'].document.getElementById('addWmsLayer').value = 1;
      var layerName = window.frames['exploreWmsLayersIframe'].document.getElementById('selectedWmsLayer').value;
      if (layerName == "") {
        {/literal}
        alert('{t}You need to choose a layer to add it in the mapfile{/t}');
        {literal}
      } else {
        window.frames['exploreWmsLayersIframe'].doSubmit();
        opener.doSubmit();
      }
    }
    function doSubmit() {
      myform = document.forms['exploreWmsLayersForm'];
      myform.submit();
    }
    {/literal}
  /*]]>*/
  </script>
</head>
<body style="margin:0;">
<form method="post" name="exploreWmsLayersForm"
      action="{$selfUrl}?project={$project}&amp;exploreWmsLayers=1"> 
  <table border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td colspan="2" class="titleArea">
        <img src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}titleLayers.gif{/r}"
             width="22" height="22" align="top" alt="titleLayers" />
        <span class="title">&nbsp;{t}Explore WMS layers{/t}</span>
      </td>
    </tr><tr>
      <td colspan="2" class="bevel2">
        <div class="help">
          <strong>{t}BROWSE: {/t}</strong>{t}Select a server in the list below to browse its WMS layers{/t}.<br />
          <strong>{t}ADD LAYER: {/t}</strong>{t}Click the 'Add' button at the bottom of this dialog to add the selected layer to the map{/t}.<br />
        </div>
      </td>
    </tr><tr>
      <td class="bevel5" valign="bottom" style="width:350px;">
        <iframe frameborder="0" name="exploreWmsLayersIframe" 
                id="exploreWmsLayersIframe" 
                scrolling="no" width="340" height="395"
                src="{$self_url}?project={$project}&amp;exploreWmsLayersIframe=1">
        </iframe>
      </td>
      <td class="bevel3" valign="top">
        <table border="0" cellpadding="0" cellspacing="5">
          <tr>
            <td>
              <div id="ActivityLayer" 
                   style="position:absolute;width:240;height:240">
                <table border="0" cellpadding="0" cellspacing="0" 
                       style="height:100%;width:100%;">
                  <tr><td valign="middle" align="center">
                     <div id="loadbarPreview">
                       <img name="activity" src="{r plugin=wmsBrowser type=gfx/layout}loadingbar.gif{/r}"
                            width="32" height="32" alt="activity" border="1" 
                            style="vertical-align:middle" />
                       {t}Loading image...{/t}
                     </div>
                  </td></tr>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td width="240" height="240" align="center" class="rbevel4">
              <img name="mapimage" width="240" height="240" alt="mapimage" 
                   border="1" 
                   src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}pixel.gif{/r}"
                   onload="hideActivityLayer()" />
            </td>
          </tr><tr>
            <td>
              <div class="rbevel2" id="abstract" 
                   style="position:relative;width:240px;height:140px;overflow:scroll;">
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr><tr>
      <td colspan="2" align="right">
        <table border="0" cellpadding="0" cellspacing="5">
          <tr>
            <td width="24">
              <a href="javascript:addWmsLayer();">
                <img src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}addLayer_{$currentLang}.gif{/r}"
                     title="{t}Add selected layer to the map{/t}" 
                     alt="{t}Add layer{/t}" />
              </a>
            </td>
            <td width="24">
              <a href="javascript:window.close();">
                <img src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}close_{$currentLang}.gif{/r}"
                     alt="{t}Close{/t}" title="{t}Close this dialog{/t}" />
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>
</body>
</html>
