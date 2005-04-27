
          <div id="mapAnchorDiv" style="position:relative;width:{$mainmap_width}px;height:{$mainmap_height}px;">

            {literal}
            <script type="text/javascript">
    /*<![CDATA[*/
    var dhtmlDivs = new String();
    document.image = new Image;
    {/literal}document.image.src = '{$mainmap_path}';{literal}
    
    if (xIE4Up) {
      dhtmlDivs = '<div id="mapImageDiv" class="dhtmldiv" style="background-image:url('; 
      dhtmlDivs += document.image.src;
      dhtmlDivs += ');background-repeat:no-repeat;"></div>';
    } else {
      dhtmlDivs = '<div id="mapImageDiv" class="dhtmldiv" ><img ';
      {/literal}
      dhtmlDivs += 'src="' + document.image.src + '" alt="{t}Main map{/t}" title="" ';
      dhtmlDivs += 'width="{$mainmap_width}" height="{$mainmap_height}" /></div>';
      {literal}
    }
    document.write(dhtmlDivs);
        /*]]>*/
            </script>
            {/literal}
            <div id="myCanvasDiv" class="dhtmldiv"></div>
            <div id="myCanvas2Div" class="dhtmldiv"></div>
            <div id="mainDHTMLDiv" class="dhtmldiv"></div>
            <div id="diplayContainerDiv" class="dhtmldiv">
              <table border="0" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="50%"><div id="displayCoordsDiv" class="dhtmlDisplay"></div></td>
                  <td align="right" width="50%"><div id="displayMeasureDiv" class="dhtmlDisplay"></div></td>
                </tr>
              </table>
            </div>

          <div id="scaleContainer">
            <div id="floatScale">{t}Current scale:{/t} 1:{$currentScale}</div>
          </div>
          
{if $collapsibleKeymap|default:''}
            <div id="keymapContainer">
              <div id="floatkeymap">
                <input type="image" name="keymap" src="{$keymap_path}" alt="{t}keymap_alt{/t}" 
                style="width:{$keymap_width}px;height:{$keymap_height}px;" /></div>
              <div id="keymapswitcher">
                <a href="#" onclick="javascript:collapseKeymap();"><img
                src="{r type=gfx/layout}keymap_off.gif{/r}" title="{t}Collapse keymap{/t}"
                alt="" id="switcherimg" /></a>
              </div>
            </div>
{/if}
            <div id="loadbarDiv" class="dhtmldiv">
              <table style="width:{$mainmap_width}px;height:{$mainmap_height}px;">
                <tr>
                  <td align="center" valign="middle"><div id="loadbar">{t}Loading message{/t}<br />
                  <img src="{r type=gfx/layout}loadingbar.gif{/r}" width="140" height="10" alt="" /></div></td>
                </tr>
              </table>
            </div>
          </div>

