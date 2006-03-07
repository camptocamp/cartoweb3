<script type="text/javascript">
  /*<![CDATA[*/
    {literal}
    loadLoadingbar = function() {
      //compute loading bar position
      xGetElementById('loadbarDivTable').style.width = 
      window.innerWidth;
      xGetElementById('loadbarDivTable').style.height = 
      window.innerHeight;
      //hide loading bar
      xHide(xGetElementById('loadbarDiv'));
    }
    EventManager.Add(window, 'load', loadLoadingbar, false);
    {/literal}
  /*]]>*/
</script>
<!--loading bar-->
<div id="loadbarDiv" class="dhtmldiv" 
     style="position:absolute;top:0;z-index:3;">
  <table id="loadbarDivTable">
    <tr><td align="center" valign="middle">
      <div id="loadbar">{t}Loading message{/t}<br />
        <img src="{r plugin=wmsBrowser type=gfx/layout}loadingbar.gif{/r}"
             width="32" height="32" alt="" />
      </div>
    </td></tr>
  </table>
</div>  
