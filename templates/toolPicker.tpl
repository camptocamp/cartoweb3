<!-- START TEMPLATE COLOR PICKER -->
  <script type="text/javascript" src="{r type=js}toolPicker.js{/r}"></script>

<div id="toolcontainer">
  <div id="menucontainer">
    <div name="toolMenu" class="toolmenuOff">Tools &gt;</div>
    <div name="toolMenu" id="tool1menu" class="toolmenuDisabled"><a href="javascript:switchToolMenu('1')">Color 1</a></div>
    <div name="toolMenu" id="tool2menu" class="toolmenuDisabled"><a href="javascript:switchToolMenu('2')">Hash</a></div>
    <div name="toolMenu" id="tool3menu" class="toolmenuDisabled"><a href="javascript:switchToolMenu('3')">Pencil</a></div>
    <div name="toolMenu" id="tool4menu" class="toolmenuDisabled"><a href="javascript:switchToolMenu('4')">Symbol</a></div>
    <div class="exitLink"><a href="javascript:closeTool();">X</a></div>
    <div class="exitLink"><a href="javascript:toolHelp();">?</a></div>
  </div>  
  <div id="tool1" class="toolbox">

    <div id="color1" class="colorbox">
      <div class="switchCbloc"><a href="javascript:switchColorTool('Carray')">Switch to color array</a></div>
      <div style="border:1px solid #596380; width:210px; cursor:crosshair;" ><img src="{r type=gfx/toolpicker}grad.jpg{/r}" width="200" height="100" id="colorgradient" alt="" /><img src="{r type=gfx/toolpicker}gradg.jpg{/r}" width="10" height="100" id="bwgradient" alt="" /></div>
      <div id="colorresult">
        <div id="colorresult2" class="colorBox" ></div>
        <div id="colorresult3" class="colorBox" ></div>
      </div>
      <div id='slider' class='demoBox'>
        <div id='thumb' class='dragBox'></div><div class="dragComment"><span class="dragText">Relative Brightness</span></div>
      </div>
      <div class="colorslider">
        <span class="inputContainer">R <input type="text" id="rgbR" class="colorInput" maxlength="3" /></span>
        <div id='sliderrgbR' class='demoBoxC'>
          <div id='thumbrgbR' class='dragBox'></div><div class="dragComment2"><span class="dragText">Red</span></div>
        </div>
      </div>
      <div class="colorslider">
        <span class="inputContainer">G <input type="text" id="rgbG" class="colorInput" maxlength="3" /></span>
        <div id='sliderrgbG' class='demoBoxC'>
          <div id='thumbrgbG' class='dragBox'></div><div class="dragComment2"><span class="dragText">Green</span></div>
        </div>
      </div>
      <div class="colorslider" >
        <span class="inputContainer">B <input type="text" id="rgbB" class="colorInput" maxlength="3" /></span>
        <div id='sliderrgbB' class='demoBoxC'>
          <div id='thumbrgbB' class='dragBox'></div><div class="dragComment2"><span class="dragText">Blue</span></div>
        </div>
      </div>
      <div class="colorslider">
        <span class="inputContainer">H <input type="text" id="hslH" class="colorInput" maxlength="3" /></span>
        <div id='sliderhslH' class='demoBoxC'>
          <div id='thumbhslH' class='dragBox'></div><div class="dragComment2"><span class="dragText">Hue</span></div>
        </div>
      </div>
      <div class="colorslider">
        <span class="inputContainer">S <input type="text" id="hslS" class="colorInput" maxlength="3" /></span>
        <div id='sliderhslS' class='demoBoxC'>
          <div id='thumbhslS' class='dragBox'></div><div class="dragComment2"><span class="dragText">Saturation</span></div>
        </div>
      </div>
      <div class="colorslider" >
        <span class="inputContainer">L <input type="text" id="hslL" class="colorInput" maxlength="3" /></span>
        <div id='sliderhslL' class='demoBoxC'>
          <div id='thumbhslL' class='dragBox'></div><div class="dragComment2"><span class="dragText">Luminance</span></div>
        </div>
      </div>
      <div class="buttonarea" >
        <input type="text" id="Hex" class="HexcolorInput" maxlength="20" value="#hexvalue" />
        <input type="button" onclick="javascript:toolPickerReturn();javascript:closeTool();" value="OK" />
      </div>
    </div>

    <div id="color2" class="colorbox">
      <div class="switchCbloc"><a href="javascript:switchColorTool('Cgradient')">Switch to color gradient</a></div>
      <div id="colortable" style="width=auto;"></div>
      <div id="colorresult">
        <div id="colorresult2a" class="colorBox" ></div>
        <div id="colorresult3a" class="colorBox" ></div>  
      </div>
      <div id="colSwitch" class="defaultFont" style="position: absolute; top: 5px; right: 5px;" title="more colors">[<a href="javascript:switchColors();">+</a>]</div>
      <div class="buttonarea" >
        <span id="hexStatic" class="defaultFont">#hexvalue</span>
        <input type="button" onclick="javascript:toolPickerReturn();javascript:closeTool();" value="OK" />
      </div>
    </div>

  </div>
  <div id="tool2" class="toolbox" ></div>
  <div id="tool3" class="toolbox"></div>
  <div id="tool4" class="toolbox">
    <div id="symboltable" style="width=auto;"></div>
    <div class="buttonarea" >
      <input type="button" onclick="javascript:toolPickerReturn();javascript:closeTool();" value="OK" />
      <span id="symbolName" class="defaultFont"></span>
    </div>  
  </div>
  <div id="toolHelp" class="helpbox">
      <a href="javascript:toolHelp(true);">close</a>
      <span class="hts">COLOR</span>
      <span class="htst">Relative Brightness:</span>
      <p>Modify current picked color brightness, from the current color up to black.
      Click will update the temporary color area.
      Releasing the click will modify the final color area.</p>
      <span class="htst">Red, Green, Blue sliders:</span>
      <p>Modify current picked color RGB.
      Click will update the temporary color area.
      Releasing the click will modify the final color area.</p>
      <span class="htst">Red, Green, Blue input:</span>
      <p>Inputed RGB value will update both the temporary and final color area.</p>
      <span class="htst">#hexvalue input:</span>
      <p>Inputed #hexadecimal color code will update both the temporary and final color area.</p>
      <a href="javascript:toolHelp(true);">close</a>
  </div>
</div>
<!-- END TEMPLATE COLOR PICKER -->