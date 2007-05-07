/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/* toolpicker global vars */
var toolArrayRef =  new Array( 'colorPicker', 
                                'hashArray',
                                'pencilArray',
                                'symbolArray'); /*'yourtool'*/
var returnElementId;
//var toolList = new Array();
var menuList = '';
var toolPickerReturnIds = new Array();
var toolPickerInputIds = new Array();

/* colorpicker global vars */
var activeColorSpace = new Array('rgb', 'hsl');

var highZ = 11;
var pickedColor = new Array(255,255,255); // default
var modifiedColor = new Array(255,255,255); // default
var colorSpace = 'rgb'; // default
var colorLevel = 'less'; // default

var Trgb = new Array('rgbR','rgbG','rgbB');
var Thsl = new Array('hslH','hslS','hslL');
var Thsv = new Array('hsvH','hsvS','hsvV');
var Tlab = new Array('labL','labA','labB');
var Tcmy = new Array('cmyC','cmyM','cmyY');

/* symbolpicker global vars */
var currentSymbol = '';
// var symbolNamesArray dans template page
// var symbolPickerHilight dans template page

// common functions list for callback (avoid using eval() )
functionList = new Array();

functionList["RGBtoHSL"]=RGBtoHSL;
functionList["HSLtoRGB"]=HSLtoRGB;
functionList["RGBtoHEX"]=RGBtoHEX;
functionList["HSLtoHEX"]=HSLtoHEX;
functionList["HEXtoRGB"]=HEXtoRGB;

functionList["colorPickerInit"]=colorPickerInit;
functionList["hashArrayInit"]=hashArrayInit;
functionList["pencilArrayInit"]=pencilArrayInit;
functionList["symbolArrayInit"]=symbolArrayInit;

functionList["colorPickerSetup"]=colorPickerSetup;
functionList["colorArraySetup"]=colorArraySetup;
functionList["hashArraySetup"]=hashArraySetup;
functionList["pencilArraySetup"]=pencilArraySetup;
functionList["symbolArraySetup"]=symbolArraySetup;

functionList["colorPickerReturn"]=colorPickerReturn;
functionList["hashArrayReturn"]=hashArrayReturn;
functionList["pencilArrayReturn"]=pencilArrayReturn;
functionList["symbolArrayReturn"]=symbolArrayReturn;

functionList["colorPickerDisplay"]=colorPickerDisplay;
functionList["hashArrayDisplay"]=hashArrayDisplay;
functionList["pencilArrayDisplay"]=pencilArrayDisplay;
functionList["symbolArrayDisplay"]=symbolArrayDisplay;

varList = new Array();
varList["Trgb"]=Trgb;
varList["Thsl"]=Thsl;
varList["Thsv"]=Thsv;
varList["Tlab"]=Tlab;
varList["Tcmy"]=Tcmy;


/* ************** */
/* MAIN FUNCTIONS */
/* ************** */

/* initialise the selected tool and the target for the returned choice
 * @param string toollist: list of strings separated by ",", if empty, all tools are displayed. The tool whose 
 * string is first will be displayed first. If toollist is empty, the default tool is the first 
 * defined in toolArrayRef. values start at 1, not 0. 1 is colorPicker, 2 hash, 3 pencil, 4 your personalised tool, etc...
 * @param string inputlist: id of the target element for the returned value(s).
 * @param string outputlist: id of the target element for the returned value(s), can be empty
 *
 * exemple of call:
 *
 * toolPicker('4','outline_point_symbol')
 * this will call the symbol picker, the input id is outline_point_symbol, and the output id will be outline_point_symbol too
 *
 * toolPicker('1','outline_point_color', 'color_return')
 * this will call the color picker, the input id is outline_point_color, and the output id will be color_return
 *
 * toolPicker('4,1','symbol_input,color_input', 'return_symbol,return_color')
 * this will call the symbol picker and the color picker, the input id is symbol_input for the first tool 
 * (here the symbol picker) and color_input for the second tool (color picker), and the output id will be return_symbol
 * for the first tool and return_color for the second.
*/
function toolPicker(toollist, inputlist, outputlist) {
  //var menuList = '';
  //setup menu : display only menu accordingly to parameter toollist
  // if none are specified, all are displayed
  if (toollist == '') {
    toolMenuList = xGetElementsByClassName('toolmenuDisabled', null, null);
    for (i=0;i<toolMenuList.length;i++ ) {
      toolMenuList[i].className = 'toolmenuOff';
    }
  } else {
    menuList = toollist.split(",");
    for (i=0;i<menuList.length;i++) {
      if (isNaN(menuList[i])) {
        alert("only put numerical id in the first parameter of the call function");
        return;
      } else {
        // check if value are not out of bound
        if (menuList[i]>toolArrayRef.length){
          alert("id of tool, in first parameter of call function, is too high, check toolArrayRef for reference");
          return;
        }
        xGetElementById('tool'+menuList[i]+'menu').className = 'toolmenuOff';      
      }
    }
  }
 
  // display whole tool block
  // need to display block before switchToolMenu, because switchToolMenu call positionTool and 
  // positionTool get wrong values if the block is not displayed
  xGetElementById('toolcontainer').style.display = "block";

  // display the toolbox wanted, second parameter
  if (menuList == ''){
    id = 1; // by default, the first tool in targetArrayRef is displayed
  } else {
    id = menuList[0];
  } 
  
  // set incomming ids array
  if (inputlist == '') 
    return alert ("no input ids specified");
  toolPickerInputIds = inputlist.split(",");

  // set return ids array
  if (outputlist && outputlist != '') 
    toolPickerReturnIds = outputlist.split(",");

  // set incomming values
  // recover incomming value accordingly to target element passed in parameter and the tools activated
  // then launch the init function for the select tool
  // refList: list of id of activated tools
  refList = new Array();
  if (menuList == '') {
    for (var i=0;i<toolArrayRef.length;i++){
      refList[i] = i+1;
    }
  } else {
    //refList = menuList.sort();
    refList = menuList;
  }
  
  for (i=0;i<refList.length;i++) {
    t = xGetElementById(toolPickerInputIds[i]);
    if (!t) {
      Tvalue = '';
    } else {
      Tvalue = t.value;
    }
    fctName = toolArrayRef[refList[i]-1]+"Init";
    functionList[fctName](Tvalue);
  }

  // set correct display class for default selected tool (by default, all tools are "deactivated")
  switchToolMenu(id);
  //enable drag on container
  toolBoxDragSetup();
}

/** 
 *  generic function to call tools setup functions, 
 *  @param string toolname : name of the tool to setup
*/
function setupTool(toolname) {
    fctName = toolname+'Setup';
    functionList[fctName]();
}
/** 
 *  generic function to return values 
*/
function toolPickerReturn() {
      
    // many activated tools
    // check if there is an existing id at pos of tool index
    for (i=0;i<menuList.length;i++){
      toolname = toolArrayRef[menuList[i]-1];

      if (!toolPickerReturnIds[i]) {
          if (!toolPickerInputIds[i]) {
            return alert("return id for "+toolname+", id "+menuList[i]+" is missing");
          } else {
            targetElmName = toolPickerInputIds[i];
          }
      } else {
        targetElmName = toolPickerReturnIds[i];
      }
      if (!xGetElementById(targetElmName))
        return alert ("element "+targetElmName+" does not exist");
      targetElm = xGetElementById(targetElmName);
      
      fctName = toolname+'Return';
      // call tool specific return value functions
      returnValues = functionList[fctName]();
      targetElm.value = returnValues;
      // call tool specific display result functions
      if (!xGetElementById(targetElmName+'_d'))
        return alert ("element "+targetElmName+"_d does not exist");
      targetHiddenElm = xGetElementById(targetElmName+'_d');
      toolPickerDisplay(targetHiddenElm, toolname);
    }
}
/** 
 *  generic function to display values 
*/
function toolPickerDisplay(targetElm, toolname) {
    fctName = toolname+'Display';
    functionList[fctName](targetElm);
}

/* ************* */
/* INI FUNCTIONS */
/* ************* */

/** 
 *  Initialise fct, set incoming values
 *  @param string color : color hex value
*/
function colorPickerInit(color) {
    if (color != ''){
      // check if color is a correct hex
      if (typeof(color) == 'string') {
        cl = color.length;
        if (cl == 6 || cl == 7) {
          pickedColor = modifiedColor = HEXtoRGB(color); 
        } else {
          return;
        }
      }
      //colorSpace = 'rgb'; already set as rgb by default
      for (var i=0;i<activeColorSpace.length;i++){
          updateSlider(activeColorSpace[i], pickedColor);
          updateInput(activeColorSpace[i], pickedColor);
      }
      updateColorBox(pickedColor, 'colorresult3');
      updateColorBox(pickedColor, 'colorresult2');
      updateHexColorBox (pickedColor, 'Hex');
    }

}
/** 
 *  Initialise fct, set incoming values
 *  @param string color : color rgb value
*/
function colorArrayInit (color) {

    updateColorBox(modifiedColor, 'colorresult2a');
    updateColorBox(modifiedColor, 'colorresult3a');
    updateHexColorBox (modifiedColor, 'hexStatic');
}

function hashArrayInit() {}; // unused for now
function pencilArrayInit() {}; // unused for now
// set initial symbol, if it exists
function symbolArrayInit(symbol) {
    if (symbol != ''){
      currentSymbol = symbol;
    }
};

/* *************** */
/* UNUSED FUNCTIONS */
/* *************** */

function hashArraySetup() {}; // unused for now
function pencilArraySetup() {}; // unused for now

function hashArrayReturn() {}; // unused for now
function pencilArrayReturn() {}; // unused for now

function hashArrayDisplay() {}; // unused for now
function pencilArrayDisplay() {}; // unused for now

/* *********** */
/* SYMBOL ARRAY */
/* *********** */

// setup symbolarray
function symbolArraySetup() {
  // create symbol table
  symbolTable();
  // set event listener to table cases
  setSymbolArrayListener();
}

// create symbol table
function symbolTable() {

  nbSy = 5; //nb sybol per lines
  nbLines = Math.ceil(symbolNamesArray.length/nbSy);
  if (symbolNamesArray.length%nbSy > 0)
    nbLines += 1;

  table = '';
  counter = counterL = 0;
  for (var i=0;i<nbLines;i++){
    table += '<div id="T'+counterL+'" style="float:left;clear:both;width:100%;">';
    for (j=0;j<nbSy;j++){
      if (counter == symbolNamesArray.length){
        break;
      }
      if (symbolPickerHilight == 'inversed') {
        imgOver = symbolNamesArray[counter] == currentSymbol ? '_over' : '';
        hilightstyle = 'ArS';
      } else {
        imgOver = '';
        hilightstyle = symbolNamesArray[counter] == currentSymbol ? 'ArSs' : 'ArS';
      }
      
      table += '<div style="float:left;"'
            + 'class="'+hilightstyle+'" title="'+symbolLabelArray[counter]+'"><img src="'+imgPath
            + symbolNamesArray[counter]+imgOver+'.'+symbolType+'" id="'+symbolNamesArray[counter]+'" style="height:30px;width:30px;" /></div>';
      counter++;
    }
    table += '</div>';
    counterL++;
  }
  tableElm = xGetElementById('symboltable');
  tableElm.innerHTML = table + '<div class="clear">&nbsp;</div>';
}
// set event listener on created symbol blocks
function setSymbolArrayListener() {
    children = xGetElementById('symboltable').childNodes;
    for (i=0;i<children.length;i++) {
      if (children[i].id) {
        child2 = children[i].childNodes;
        for (j=0;j<child2.length;j++) {
          addEvent(child2[j], 'click', getSymbolFromArray, false);
        }
      }
    }
}
/** 
 *  write symbol on clic
 *  @param event ev
*/
function getSymbolFromArray(ev) {
    var e = window.event ? window.event : ev;
    var t = e.target ? e.target : e.srcElement;
    if (t.id) {
      currentSymbol = t.id;
      updateSymbol();
    }
}
/** 
 *  update symbol list on click
*/
function updateSymbol(){
    // reset existing symbol
    sybmElmList = xGetElementById('symboltable').childNodes;
    for (var k = 0; k < sybmElmList.length; k++ ) {
        for (var i = 0; i < sybmElmList[k].childNodes.length; i++ ) {
          if (symbolPickerHilight == 'inversed'){
            for (var j = 0; j < sybmElmList[k].childNodes[i].childNodes.length; j++ ) {
              imgSrc = sybmElmList[k].childNodes[i].childNodes[j].src
              // position of last dot
              dotPos = imgSrc.lastIndexOf('.');
              toCheck = imgSrc.substring(dotPos-5, dotPos);
              if (toCheck == '_over'){
                sybmElmList[k].childNodes[i].childNodes[j].src = imgSrc.substring(0, dotPos-5) + '.' + symbolType;
              }
            }
          } else {
            if (sybmElmList[k].childNodes[i].nodeName.toLowerCase() == 'div')
              sybmElmList[k].childNodes[i].className = 'ArS';
          }
        }
    }
    // active new symbol
    activeSym = xGetElementById(currentSymbol);
    if (symbolPickerHilight == 'inversed'){
      activeSym.src = imgPath+currentSymbol+'_over.'+symbolType;
    } else {
      activeSym.parentNode.className = 'ArSs';
    }
    xGetElementById('symbolName').innerHTML = symbolLabelArray[getSymbolNumId(currentSymbol)];
    
}
/** 
 *  get id of symbol from name
 *  @param string symbol
 *  @return int x
*/
function getSymbolNumId(symbol) {
  //var x = 0;
  for (x in symbolNamesArray) {
    if (symbolNamesArray[x] == symbol){
      break;
    }
  }
  return x;
}
// return selected symbol to main page
function symbolArrayReturn () {
    return currentSymbol;
}
// display selected symbol
function symbolArrayDisplay (targetElm) {
    targetElm.style.backgroundImage = 'url('+imgPath+currentSymbol+'.'+symbolType+')';
    targetElm.title = symbolLabelArray[getSymbolNumId(currentSymbol)];
}

/* *********** */
/* COLOR ARRAY */
/* *********** */

// setup colorarray
function colorArraySetup()
{
    stepsval = 1; //default value for color matrice calculation steps
    if (colorLevel == 'more') {
        stepsval = 0.5;
    } else if (colorLevel == 'less') {
        stepsval = 1;
    }
    colorMatrice(stepsval?stepsval:'');
    setColorArrayListener();
}
/** 
 *  create color matrice, values used to calculate color blocks
 *  @param integer steps : qtt of steps (variation) of colors
*/
function colorMatrice(steps) {
    steps = steps?steps:1; // change value to a value between 0 and 1 to get more color variations
    colorArray = new Array();
    for (i=0;i<=1;i+=steps) {
        r = Math.round(255*i);
        for (j=0;j<=1;j+=steps) {
            g = Math.round(255*j);
            for (k=0;k<=1;k+=steps) {
                b = Math.round(255*k);
                colorArray.push(new Array(r, g, b));
            }
        }
    }
  colorTable(colorArray);
}
/** 
 *  create color array, use color matrice for calculation
 *  @param array matrice : array of arrays or arrays of values between 0 and 255
*/
function colorTable(matrice) {
  table = '';
  counter = counterL = 0;
  steps = 10; // increase value to get more variation for one color
  for (i=0;i<matrice.length;i++){
      table += '<div id="L'+counterL+'" style="float:left;clear:both;width:100%;">';
      // from black to full saturation
      for (j=0;j<=steps;j++){
        r = Math.round(matrice[i][0] / steps * j);
        g = Math.round(matrice[i][1] / steps * j);
        b = Math.round(matrice[i][2] / steps * j);
        
        table += '<div id="C'+counter+'" style="float:left;background-color:rgb('+r+','+g+','+b+');" class="ArC"></div>';
        counter++;
      }
      // from full saturation to white
      for (j=1;j<=steps-1;j++){
        r = matrice[i][0] + Math.round((255 - matrice[i][0]) / steps * j);
        g = matrice[i][1] + Math.round((255 - matrice[i][1]) / steps * j);
        b = matrice[i][2] + Math.round((255 - matrice[i][2]) / steps * j);
        
        table += '<div id="C'+counter+'" style="float:left;background-color:rgb('+r+','+g+','+b+');" class="ArC"></div>';
        counter++;
      }
    table += '</div>';
    counterL++;
  }
  tableElm = xGetElementById('colortable');
  tableElm.innerHTML = table + '<div class="clear">&nbsp;</div>';
}
// set event listener on created color blocks
function setColorArrayListener() {
    children = xGetElementById('colortable').childNodes;
    for (i=0;i<children.length;i++) {
      addEvent(children[i], 'mouseover', getcolorFromArray, false);
      addEvent(children[i], 'click', getcolorUniqueFromArray, false);
    }
}
// switch between more or less colors in array
function switchColors() {
  // get current color level
  if (colorLevel == 'more') {
    newLevel = 'less';
    symbol = '+';
  }
  if (colorLevel == 'less') {
    newLevel = 'more';
    symbol = '-';
  }
  colorLevel = newLevel;
  colorArraySetup();
  switchElmCh = xGetElementById('colSwitch').childNodes;
  for (i=0; i<switchElmCh.length; i++) {
    if (switchElmCh[i].nodeName == 'A') {
      switchElmCh[i].innerHTML = symbol;
    }
  }
}
/* END COLOR ARRAY */
/* --------------- */

/* ************ */
/* COLOR PICKER */
/* ************ */

// setup colorpicker
/* setup the slider objects and display them */
function colorPickerSetup()
{
    // display color bloc
    switchColorTool();
    // initialise brightness slider
    var c1 = xGetElementById('slider'); // slider container
    var d1 = xGetElementById('thumb'); // slider cursor
    xMoveTo(d1, 0, 0);
    xEnableDrag(d1, OnDragStart, OnDrag, OnDragEnd);
    xShow(c1);
    xShow(d1);
    // initialise sliders, set position accordingly to initial color values
    for (i=0;i<activeColorSpace.length;i++){
      varName = 'T'+activeColorSpace[i];
      colorSpaceAr = varList[varName];
      // convert initial color to other colorspace if needed
      if (activeColorSpace[i] != 'rgb'){
          fctName = "RGBto"+activeColorSpace[i].toUpperCase();
          initialColor = functionList[fctName](modifiedColor);
      } else {
          initialColor = modifiedColor;
      }

      for (j=0;j<colorSpaceAr.length;j++){
        var slider = 'slider' + colorSpaceAr[j];
        var thumb = 'thumb' + colorSpaceAr[j];
        s = xGetElementById(slider);
        d = xGetElementById(thumb);

        newX = Math.round(initialColor[j]*(xWidth(s)-xWidth(d)-2)/(colorSpaceWidth(activeColorSpace[i])));
        xMoveTo(thumb, newX, 0);


        xEnableDrag(d, OnDragStart, dOnDrag, dOnDragEnd);
        xShow(s);
        xShow(d);
      }
    }
}
/* ON DRAG START */
/** 
 *  on drag start fct for the brightness slider, setup some default values
 *  @param object ele
 *  @param integer mx
 *  @param integer my
*/
function OnDragStart(ele, mx, my)
{
    window.status = '';
    xZIndex(ele, highZ++);
    ele.totalMX = 0;
    ele.totalMY = 0;
}
/* ON DRAG */
/** 
 *  on drag fct for the brightness slider
 *  what to do when the user start draging a slider element
 *  calculate the new x position of the cursor element
 *  @param event ele
 *  @param integer mdx : x delta distance
 *  @param integer mdy : y delta distance
*/
function OnDrag(ele, mdx, mdy)
{
    newX = xLeft(ele) + mdx;
    // limit displacement in the parent element enclosure
    if (newX >= 1 && newX < xWidth(ele.parentNode) - xWidth(ele) - 2) {
        xMoveTo(ele, newX, 0);
    }
    ele.totalMX += mdx;
    // update brightness of current color
    changeBrightness();
}
/** 
 *  on drag color slider
 *  what to do when the user is draging a slider element
 *  calculate the new x position of the cursor element
 *  calculation depend of the colorspace of the input
 *  all color update are done in their own colorspace
 *  @param event ele
 *  @param integer mdx : x delta distance
 *  @param integer mdy : y delta distance
*/
function dOnDrag(ele, mdx, mdy)
{
    newX = xLeft(ele) + mdx;
    // limit displacement in the parent element enclosure
    if (newX >= 1 && newX < xWidth(ele.parentNode) - xWidth(ele) - 2) {
      xMoveTo(ele, newX, 0);
    }
    ele.totalMX += mdx;
    // get slider id, use it to access the correct input, correct the value
    elId = ele.id.substr(ele.id.length-4);    
    sliderColorSpace = getColorSpace(elId);
    // if the slider colorspace is different from the current pickedColor colorspace -> conversion
    if (sliderColorSpace != colorSpace) {
        fctName = colorSpace.toUpperCase()+"to"+sliderColorSpace.toUpperCase();
        modifiedColor = functionList[fctName](modifiedColor);
        colorSpace = sliderColorSpace;
    }
    // calculate the new color value
    newColorValue = Math.round(255 * newX / (xWidth(ele.parentNode) - xWidth(ele) - 2));
    // access the correct sliders id array, depends of the colorspace, see 'T'-like array defined 
    // at the beginning of the page
    varName = 'T'+sliderColorSpace;
    colorSpaceAr = varList[varName];
    // update the color array with the new value
    for (var i=0;i<colorSpaceAr.length;i++) {
        if (colorSpaceAr[i] == elId){
            modifiedColor[i] = newColorValue;
        }
    }
    // update all output
    for (var i=0;i<activeColorSpace.length;i++){
        if (activeColorSpace[i] != sliderColorSpace) // update only sliders whose colorspace is different from the on being modified
            updateSlider(activeColorSpace[i], modifiedColor);
        updateInput(activeColorSpace[i], modifiedColor);
    }    
    updateColorBox(modifiedColor, 'colorresult2');    
    
}
/* ON DRAG END */
/** 
 *  on drag end brightness slider
 *  what to do when the user stop draging the brightness slider element
 *  @param event ele
 *  @param integer mx
 *  @param integer my
*/
function OnDragEnd(ele, mx, my){
  setBrightness();
}
/** 
 *  on drag end color slider
 *  what to do when the user stop draging a color slider element
 *  @param event ele
 *  @param integer mx
 *  @param integer my
*/
function dOnDragEnd(ele, mx, my){
  pickedColor = modifiedColor;
  // update all output
  for (var i=0;i<activeColorSpace.length;i++){
      if (activeColorSpace[i] != colorSpace) // update only sliders whose colorspace is different from the on being modified
          updateSlider(activeColorSpace[i], modifiedColor);
      updateInput(activeColorSpace[i], modifiedColor);
  }
  updateColorBox(modifiedColor, 'colorresult3');
  updateHexColorBox (modifiedColor, 'Hex');
}

/* BRIGHTNESS */
/** 
 *  get pos x of brightness cursor
 *  @return float level : brightness level
*/
function getBrightnessLevel () {
    box = xGetElementById('slider');
    cursor = xGetElementById('thumb');
    boxX = findPosX(box); // x coord of slider box
    cursorX = findPosX(cursor); // x coord of slider cursor
    level = 1 - ((cursorX-boxX) / (xWidth(box)-xWidth(cursor)-4)); //brightness level, value between 1 and 0
    level = level<0?0:level;
    
    return level;
}
// 
/** 
 *  correct brightness accordingly to the current pos of brigtness cursor, on drag
 *  main color is not updated, only temp color is updated
*/
function changeBrightness () {
    level = getBrightnessLevel();
    newval = new Array();
    
    if (colorSpace != 'rgb') {
        fctName = colorSpace.toUpperCase()+"toRGB";
        pickedColor = functionList[fctName](pickedColor);
        modifiedColor = functionList[fctName](modifiedColor);
        colorSpace = 'rgb';
    }

    newval[0] = Math.round(level * pickedColor[0]);
    newval[1] = Math.round(level * pickedColor[1]);
    newval[2] = Math.round(level * pickedColor[2]);

    for (i=0;i<activeColorSpace.length;i++){
        updateSlider(activeColorSpace[i], newval);
        updateInput(activeColorSpace[i], newval);
    }
    updateColorBox(newval, 'colorresult2');
}
/** 
 *  correct brightness accordingly to the current pos of brigtness cursor, at the end of drag
 *  all color are updated
*/
function setBrightness() {
    level = getBrightnessLevel();
    newval = new Array();

    if (colorSpace != 'rgb') {
        fctName = colorSpace.toUpperCase()+"toRGB";
        pickedColor = modifiedColor = functionList[fctName](pickedColor);
        colorSpace = 'rgb';
    }

    newval[0] = Math.round(level * pickedColor[0]);
    newval[1] = Math.round(level * pickedColor[1]);
    newval[2] = Math.round(level * pickedColor[2]);

    for (i=0;i<activeColorSpace.length;i++){
        updateSlider(activeColorSpace[i], newval);
        updateInput(activeColorSpace[i], newval);
    }
    modifiedColor = newval;
    updateColorBox(newval, 'colorresult3');
    updateHexColorBox (newval, 'Hex');
}
/** 
 *  reset brightness cursor to pos 0
*/
function resetBrightness() {
    d = xGetElementById('thumb');
    xMoveTo(d, 0, 0);
}

/* END COLOR PICKER */

/* COMMON POSITION */
/** 
 *  get X coordinate of obj
 *  @param object obj
 *  @return integer curLeft : x coordinate of the object from the left of window
*/
function findPosX(obj) {
    var curLeft = 0;
    if (obj.offsetParent) {
        do {
            curLeft += obj.offsetLeft;
        } while (obj = obj.offsetParent);
    } else if (obj.x) {
        curLeft += obj.x;
    }
    return curLeft;
}
/** 
 *  get Y coordinate of obj
 *  @param object obj
 *  @return integer curTop : y coordinate of the object from the top of window
*/
function findPosY(obj) {
    var curTop = 0;
    if (obj.offsetParent) {
        do {
            curTop += obj.offsetTop;
        } while (obj = obj.offsetParent);
    } else if (obj.y) {
        curTop += obj.y;
    }
    return curTop;
}
/** 
 *  get pox XY of mouse cursor
 *  @param event ev
 *  (@param boolean relative)
 *  @return array posXY : x,y coordinate
*/
function getPos (ev, relative) {
    var e = window.event ? window.event : ev;
    var t = e.target ? e.target : e.srcElement;

    var mX, mY;
    // calculate x,y coordinate of cursor, it depends of the browser used
    if (e.pageX && e.pageY) {
        mX = e.pageX;
        mY = e.pageY;
    } else if (e.clientX && e.clientY) {
        mX = e.clientX;
        mY = e.clientY;

        if( !( ( window.navigator.userAgent.indexOf( 'Opera' ) + 1 ) ||
        ( window.ScriptEngine && ScriptEngine().indexOf( 'InScript' ) + 1 ) ||
        window.navigator.vendor == 'KDE' ) ) {
            if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
                //IE 4, 5 & 6 (in non-standards compliant mode)
                mX += document.body.scrollLeft;
                mY += document.body.scrollTop;
            } else if( document.documentElement &&
            ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
                //IE 6 (in standards compliant mode)
                mX += document.documentElement.scrollLeft;
                mY += document.documentElement.scrollTop;
            }
        }
    }
    // get position of the mouse inside the element target (otherwise the reference is the page)
    if (relative) {
        var xPos = mX - findPosX(t);
        var yPos = mY - findPosY(t);      
    } else {
        var xPos = mX;
        var yPos = mY;      
    }
    var posXY = new Array (xPos,yPos);

    return posXY
}
// ###############################################################################################################################
/** 
 *  return the colorspace width
 *  for color calculation, the max values are not always the same, it depends of the colorspace
 *  @param string colorSpace
 *  @return integer
*/
function colorSpaceWidth(colorSpace) {
    if (colorSpace == 'rgb' || colorSpace == 'hsl') 
        return 255;
    if (colorSpace == 'lab') 
        return 100;
}

/* NEW OUTPUT FUNCTIONS */
/** 
 *  update sliders position accordingly to new calculated color
 *  @param string outputColorSpace : type of colorspace of the element which activated the color modification
 *  @param array colorValues
*/
function updateSlider (outputColorSpace, colorValues) {
    // colorSpace is global
    if (outputColorSpace == colorSpace) {
        // if the event colorspace is the same as the current colorspace, use color values directly
        color = colorValues;
    } else {
        // convert color values to output colorspace, see functionList array at the beginning of the page
        fctName = colorSpace.toUpperCase()+"to"+outputColorSpace.toUpperCase();        
        color = functionList[fctName](colorValues);
    }
    varName = 'T'+outputColorSpace;
    colorSpaceAr = varList[varName];
    // move the sliders
    for (var i=0;i<colorSpaceAr.length;i++) {
        ele = xGetElementById('thumb' + colorSpaceAr[i]); 
        newX = Math.round(color[i]*(xWidth(ele.parentNode)-xWidth(ele)-2)/(colorSpaceWidth(outputColorSpace)));
        xMoveTo(ele, newX, 0);
    }
}
/** 
 *  update inputs values accordingly to new calculated color
 *  @param string outputColorSpace : type of colorspace of the element to update
 *  @param array colorValues
*/
function updateInput (outputColorSpace, colorValues) {
    if (outputColorSpace == colorSpace) {
        color = colorValues;
    } else {
        fctName = colorSpace.toUpperCase()+"to"+outputColorSpace.toUpperCase();
        color = functionList[fctName](colorValues);
    }    
    varName = 'T'+outputColorSpace;
    colorSpaceAr = varList[varName];
    // update input's value
    for (var i = 0; i < colorSpaceAr.length; i++) {
        colorValueCont = xGetElementById(colorSpaceAr[i]);
        colorValueCont.value = color[i] < 0 ? 0 : color[i] > colorSpaceWidth(outputColorSpace) ? colorSpaceWidth(outputColorSpace) : color[i];
    }
}
/** 
 *  update color boxs accordingly to new calculated color
 *  (the color box are the two large cases under the gradient area)
 *  colorspace for boxs is always rgb because rgb is needed for css stlye
 *  @param string outputColorSpace : type of colorspace of the element to update
 *  @param array colorValues
 *  @param string boxID : id of the box to update
*/
function updateColorBox (colorValues, boxID) {
    if (colorSpace == 'rgb') {
        color = colorValues;
    } else {
        fctName = colorSpace.toUpperCase()+"toRGB";
        color = functionList[fctName](colorValues);
    }
    colorContainer = xGetElementById(boxID);
    // set background color of container
    colorContainer.style.backgroundColor = 'rgb('+color[0]+','+color[1]+','+color[2]+')';
}
/** 
 *  update hex box accordingly to new calculated color
 *  @param string outputColorSpace : type of colorspace of the element to update
 *  @param array colorValues
 *  @param string boxID : id of the box to update
*/
function updateHexColorBox (colorValues, boxID) {
    fctName = colorSpace.toUpperCase()+"toHEX";
    color = functionList[fctName](colorValues);
    // update input's value
    colorContainer = xGetElementById(boxID);
    colorContainer.value = color;
}
// ###############################################################################################################################

/* COMMON COLOR */
/** 
 *  write color on move
 *  @param event e
*/
function getcolor(e) {
    // if current colorspace is different from rgb, convert the stored color to rgb
    // to prevent colorspace conflict between modified and picked color
    if (colorSpace != 'rgb'){
        fctName = colorSpace.toUpperCase()+"toRGB";
        modifiedColor = functionList[fctName](modifiedColor);
    }

    tmpColor = coordToRGB (e); // get rgb from xy and store values
    colorSpace = 'rgb';
    for (i=0;i<activeColorSpace.length;i++){
        updateSlider(activeColorSpace[i], tmpColor);
        updateInput(activeColorSpace[i], tmpColor);
    }
    updateColorBox(tmpColor, 'colorresult2');
}
/** 
 *  write color on clic
 *  @param event e
*/
function getcolorUnique(e) {
    pickedColor = coordToRGB (e); // get rgb from xy and store values
    resetBrightness();
    colorSpace = 'rgb';
    for (i=0;i<activeColorSpace.length;i++){
        updateSlider(activeColorSpace[i], pickedColor);
        updateInput(activeColorSpace[i], pickedColor);
    }
    updateColorBox(pickedColor, 'colorresult3');
    updateHexColorBox (pickedColor, 'Hex');
    modifiedColor = pickedColor;
}
/** 
 *  write color on mouseover
 *  @param event ev
*/
function getcolorFromArray(ev) {
    var e = window.event ? window.event : ev;
    var t = e.target ? e.target : e.srcElement;
    
    if (t.id) {
      color = t.style.backgroundColor;
      xGetElementById('colorresult2a').style.backgroundColor = color;
    }
}
/** 
 *  write color on clic
 *  @param event ev
*/
function getcolorUniqueFromArray(ev) {
    var e = window.event ? window.event : ev;
    var t = e.target ? e.target : e.srcElement;
    
    if (t.id) {
      color = t.style.backgroundColor;
      xGetElementById('colorresult3a').style.backgroundColor = color;
    }
    colorS = color.indexOf('(');
    colorE = color.indexOf(')');
    colorV = color.substring(colorS+1,colorE);
    modifiedColor = colorV.split(',');
    xGetElementById('hexStatic').innerHTML = RGBtoHEX(modifiedColor);
}
/** 
 *  get colorspace corresponding to id of the element which started the event
 *  @param string elId
 *  @return string inputColorSpace
*/
function getColorSpace(elId) {
    inputColorSpace = '';
    for (i=0;i<activeColorSpace.length;i++){
      varName = 'T'+activeColorSpace[i];
      colorSpaceAr = varList[varName];

      for (j=0;j<colorSpaceAr.length;j++){
        if (elId == colorSpaceAr[j]){
          inputColorSpace = activeColorSpace[i];
        }
      }
    }
    try {
      if (inputColorSpace == ''){
        throw new Error("unknown colorspace");
      }
    }
    catch (e){
      alert(e);   
    }
    return inputColorSpace;
}
/** 
 *  change Color depending of values typed in an Input element
 *  @param event ev
*/
function changeColorFromInput(ev) {
  var e = window.event ? window.event : ev;
  var t = e.target ? e.target : e.srcElement;
  elId = t.id;
  inputColorSpace = getColorSpace(elId);
  // if the input colorspace is different from the current pickedColor colorspace -> conversion
  if (inputColorSpace != colorSpace) {
      fctName = colorSpace.toUpperCase()+"to"+inputColorSpace.toUpperCase();
      newColor = functionList[fctName](modifiedColor);
      modifiedColor = newColor;
      colorSpace = inputColorSpace;
  }
  // get the new color value
  newColorValue = t.value;
  // check if value is in acceptable range
  if (newColorValue > 255) {
    newColorValue = 255;
    t.value = 255;
  }
  if (newColorValue < 0) {
    newColorValue = 0;
    t.value = 0;
  }
  // access the correct input id array, depends of the colorspace, see 'T'-like array defined 
  // at the beginning of the page
  varName = 'T'+inputColorSpace;
  colorSpaceAr = varList[varName];
  // update the color array with the new value
  for (var i=0;i<colorSpaceAr.length;i++) {
      if (colorSpaceAr[i] == elId){
          modifiedColor[i] = newColorValue;
      }
  }
  pickedColor = modifiedColor;
  resetBrightness();
  // update sliders, input and colorbox
  for (i=0;i<activeColorSpace.length;i++){
      updateSlider(activeColorSpace[i], pickedColor);
    if (colorSpace != activeColorSpace[i]) // only update input if different from current input
      updateInput(activeColorSpace[i], pickedColor);
  }
  updateColorBox(pickedColor, 'colorresult3');
  updateColorBox(pickedColor, 'colorresult2');
  updateHexColorBox (pickedColor, 'Hex');
}

/** 
 *  change Color depending of values typed in an Hexadecimal Input element
 *  @param event ev
*/
function changeColorFromHexInput(ev) {
    var e = window.event ? window.event : ev;
    var t = e.target ? e.target : e.srcElement;

    hex = t.value;
    if (hex.substring(0,1) == '#') hex = hex.substr(1,hex.length);
    if (hex.length != 6)
        return;
    pickedColor = modifiedColor = HEXtoRGB(hex);
    colorSpace = 'rgb';
    resetBrightness();
    // update sliders, input and colorbox
    for (i=0;i<activeColorSpace.length;i++){
        updateSlider(activeColorSpace[i], pickedColor);
        updateInput(activeColorSpace[i], pickedColor);
    }
    updateColorBox(pickedColor, 'colorresult3');
    updateColorBox(pickedColor, 'colorresult2');
    updateHexColorBox (pickedColor, 'Hex');
}

/** 
 *  convert xy pos to rgb
 *  @param event ev
 *  @return array color
*/
function coordToRGB (ev) {
    var posXY = getPos(ev, true);
    var color = new Array();
    if (colorType == 'C') {
        color[0] = Math.round(255 * ( 1.0- ((posXY[1]/100) * ((1+Math.sin (6.3*posXY[0]/200)) /2) )));
        color[1] = Math.round(255 * ( 1.0- ((posXY[1]/100) * ((1+Math.cos (6.3*posXY[0]/200)) /2) )));
        color[2] = Math.round(255 * ( 1.0- ((posXY[1]/100) * ((1-Math.sin (6.3*posXY[0]/200)) /2) )));
    } else {
        ct = Math.round(255 * (1.0 - (posXY[1]/100)));
        color[0] = color[1] = color[2] = ct<0?0:ct;
    }
    return color;
}
//copied from HTML Color Editor v1.2 (c) 2000 by Sebastian Weber <webersebastian@yahoo.de>
/** 
 *  convert RGB to HSL
 *  @param array colorset
 *  @return array
*/
function RGBtoHSL(colorset) {
    r = colorset[0]; g = colorset[1]; b = colorset[2];
    min = Math.min(r,Math.min(g,b));
    max = Math.max(r,Math.max(g,b));
    // l (L)
    l = Math.round((max+min)/2);
    // achromatic ?
    if(max==min) {h=160;s=0;}
    else {
    // s
      if (l<128) s=Math.round(255*(max-min)/(max+min));
      else s=Math.round(255*(max-min)/(510-max-min));
    // h
      if (r==max)h=(g-b)/(max-min);
      else if (g==max) h=2+(b-r)/(max-min);
      else h=4+(r-g)/(max-min);
      h*=60;
      if (h<0) h+=360;
      h=Math.round(h*255/360);
    }
    return Array(h,s,l);
}
//copied from HTML Color Editor v1.2 (c) 2000 by Sebastian Weber <webersebastian@yahoo.de>
/** 
 *  convert HSL to RGB
 *  @param array colorset
 *  @return array
*/
function HSLtoRGB (colorset) {
    h = colorset[0]; s = colorset[1]; l = colorset[2];
    if (s == 0) s = 1;
    h=h*360/255;s/=255;l/=255;
    if (l <= 0.5) rm2 = l + l * s;
    else rm2 = l + s - l * s;
    rm1 = 2.0 * l - rm2;
    r = ToRGB1(rm1, rm2, h + 120.0)
    g = ToRGB1(rm1, rm2, h)
    b = ToRGB1(rm1, rm2, h - 120.0)

    return Array(r, g, b);
}
/** 
 *  HSLtoRGB associed function
 *  @param float rm1
 *  @param float rm1
 *  @param float rh
 *  @return integer
*/
function ToRGB1(rm1,rm2,rh) {
    
    if      (rh > 360.0) rh -= 360.0;
    else if (rh <   0.0) rh += 360.0;
    if      (rh <  60.0) rm1 = rm1 + (rm2 - rm1) * rh / 60.0;
    else if (rh < 180.0) rm1 = rm2;
    else if (rh < 240.0) rm1 = rm1 + (rm2 - rm1) * (240.0 - rh) / 60.0;
    return Math.round(rm1 * 255);
}
/** 
 *  convert RGB to HEX
 *  @param array colorset
 *  @return string color
*/
function RGBtoHEX(colorset) {
    var c="0123456789abcdef";
    r = colorset[0]; g = colorset[1]; b = colorset[2];
    r1=c.substring(Math.floor(r/16),Math.floor(r/16)+1);
    r2=c.substring(r%16,(r%16)+1);
    g1=c.substring(Math.floor(g/16),Math.floor(g/16)+1);
    g2=c.substring(g%16,(g%16)+1);
    b1=c.substring(Math.floor(b/16),Math.floor(b/16)+1);
    b2=c.substring(b%16,(b%16)+1);
    color = '#'+r1+''+r2+''+g1+''+g2+''+b1+''+b2;
    return color;
}
/** 
 *  convert HSL to HEX
 *  @param array colorset
 *  @return string
*/
function HSLtoHEX(colorset) {
    return RGBtoHEX(HSLtoRGB (colorset));
}
/** 
 *  convert HEX to RGB
 *  @param string hex
 *  @return array
*/
function HEXtoRGB(hex) {
    var c="0123456789abcdef";
    if (hex.substring(0,1) == '#') hex = hex.substr(1,6);
    red=c.indexOf(hex.substring(0,1))*16+c.indexOf(hex.substring(1,2));
    green=c.indexOf(hex.substring(2,3))*16+c.indexOf(hex.substring(3,4));
    blue=c.indexOf(hex.substring(4,5))*16+c.indexOf(hex.substring(5,6));

    return new Array(red, green, blue);
}
/** 
 *  return color picked to targets page elements
 *  @return array
*/
function colorPickerReturn () {
    
    fctName = colorSpace.toUpperCase()+"toHEX";
    newColor = functionList[fctName](modifiedColor);

    return newColor;
} 
function colorPickerDisplay(targetElm) {

    if (colorSpace != 'rgb') {
        fctName = colorSpace.toUpperCase()+"toRGB";
        modifiedColor = functionList[fctName](modifiedColor);
        colorSpace = 'rgb';
    }

    targetElm.style.backgroundColor = 'rgb('+modifiedColor[0]+','+modifiedColor[1]+','+modifiedColor[2]+')';
    targetElm.title = RGBtoHEX(modifiedColor);
}

/* NAVIGATION */
/** 
 *  switch tools, change the visible tool panels and menus
 *  @param integer id
*/
function switchToolMenu(id) {
    onoff = new Array('On', 'Off');
    toolMenuOn = xGetElementsByClassName('toolmenuOn', null, null);
    toolMenuOff = xGetElementsByClassName('toolmenuOff', null, null);
    toolBoxs = xGetElementsByClassName('toolbox', null, null);
    varList["toolMenuOn"]=toolMenuOn;
    varList["toolMenuOff"]=toolMenuOff;
    // set label
    for (j=0;j<onoff.length;j++) {
      varName = 'toolMenu'+onoff[j];
      toolMenu = varList[varName];

      for (i=0;i<toolMenu.length;i++) {
          cId = toolMenu[i].id.charAt(4);
          if (cId != id) {
              toolMenu[i].className = "toolmenuOff";
          } else {
              toolMenu[i].className = "toolmenuOn";
          }
      }
    }
    // set box
    for (i=0;i<toolBoxs.length;i++) {
        cId = toolBoxs[i].id.charAt(4);
        if (cId != id) {
            toolBoxs[i].style.display = "none";
        } else {
            toolBoxs[i].style.display = "block";
        }
    }
    // init tool function, id start at 1, toolArrayRef start at 0, hence id-1
    setupTool(toolArrayRef[id-1]);
    positionTool();
}
/** 
 *  switch between gradient and array color tool
 *  @param string panel
*/
function switchColorTool(panel) {
    // change color tool panel display
    if (panel) {
      C1 = xGetElementById('color1');
      C2 = xGetElementById('color2');
      if (panel == 'Carray') {
           C1.style.display = 'none';
           C2.style.display = 'block';
           colorArraySetup();
           colorArrayInit(modifiedColor);
      } else {
           C1.style.display = 'block';
           C2.style.display = 'none';
           colorPickerSetup();
           fctName = colorSpace.toUpperCase()+"toHEX";
           color = functionList[fctName](modifiedColor);
           colorPickerInit(color);
      }
    } else {
        // display color block
        xGetElementById('color1').style.display = "block";
    }
    pickedColor = modifiedColor;
}
/** 
 *  position the toolcontainer at the same x and y than the target element, correct if not enough space horizontaly
*/
function positionTool(){
 
  refElmClass = '#toolcontainer';
  elm = xGetElementById(toolPickerInputIds[0]+'_d');
  Pelm =  xGetElementById('toolcontainer');
  px = findPosX(elm);
  py = findPosY(elm);
  
  //opera Netscape 6 Netscape 4x Mozilla 
  if (window.innerWidth || window.innerHeight){ 
    docwidth = window.innerWidth; 
    docheight = window.innerHeight; 
  } 
  //IE Mozilla 
  if (document.body.clientWidth || document.body.clientHeight){ 
    docwidth = document.body.clientWidth; 
    docheight = document.body.clientHeight;
  }
  if (document.documentElement) {
    docwidth2 = document.documentElement.offsetWidth;
    docheight2 = document.documentElement.offsetHeight;
  }

  if (docwidth > docwidth2) {
    width = docwidth;
  } else width = docwidth2;
  if ((px+Pelm.offsetWidth)>width){
    px = width - Pelm.offsetWidth;
  }
  
  /*
  if (docheight > docheight2) {
    height = docheight;
    alert("IE");
  } else height = docheight2;
  */
  height = docheight;
  if ((py+Pelm.offsetHeight)>height){
    py = height - Pelm.offsetHeight;
  }

  Pelm.style.top = py+'px';
  Pelm.style.left = px+'px';
}
/** 
 *  close tool panel
*/
function closeTool() {
  //reset color array, prenvent a display bug in IE
  colorLevel = 'less';
  // reset display state of tool block
  for (i=0;i<toolArrayRef.length;i++) {
    xGetElementById('tool'+(i+1)+'menu').className = 'toolmenuDisabled';
    xGetElementById('tool'+(i+1)).style.display = 'none';
  }
  // reset color sub block
  xGetElementById('color1').style.display = 'none';
  xGetElementById('color2').style.display = 'none';

  xGetElementById('toolcontainer').style.display = "none";
  disableSubmit = false; 
}
/** 
 *  prevent form from being submited on key enter press
*/
function cancelEnter(ev) {    
    disableSubmit = true; 
}

/* HELP */
/** 
 *  display and hide help block
 *  @param boolean close
*/
function toolHelp(close){
  if (close) {
    xGetElementById('toolHelp').style.display = 'none';
  } else {
    xGetElementById('toolHelp').style.display = 'block';    
  }
}

/* UTILE DIVERS */
function isArray(a) {
    return isObject(a) && a.constructor == Array;
}
function isObject(a) {
    return (a && typeof a == 'object') || isFunction(a);
}
function isFunction(a) {
    return typeof a == 'function';
}
function isNumber(a) {
    return typeof a == 'number' && isFinite(a);
}
/* ON EVENT FUNCTIONS */
// set event listener action
function addEvent(elm, evType, fn, useCapture) {
    xAddEventListener(elm, evType, fn, useCapture);
}
// unset event listener
function removeEvent(elm, evType, fn, useCapture) {
    xRemoveEventListener(elm, evType, fn, useCapture)
}
/** 
 *  on mouse over action, start
*/
function startMouseCoord(ev) {
    var e = window.event ? window.event : ev;
    var t = e.target ? e.target : e.srcElement;

    colorType = t.id == 'colorgradient' ? 'C' : t.id == 'bwgradient' ? 'G' : '';

    addEvent(t, 'mousemove', getcolor, false);
    addEvent(t, 'click', getcolorUnique, false);
}
/** 
 *  on mouse over action, stop
*/
function stopMouseCoord () {
    for (var i=0;i<activeColorSpace.length;i++){
        updateSlider(activeColorSpace[i], modifiedColor);
        updateInput(activeColorSpace[i], modifiedColor);
    }
    updateColorBox(modifiedColor, 'colorresult2');

    var colorgradient = xGetElementById('colorgradient');
    removeEvent(colorgradient, 'mousemove', getcolor, false);
}

// enable drag on the main toolbox container
function toolBoxDragSetup() {
  xEnableDrag('fixedtoolmenu', toolBoxOnDragStart, toolBoxOnDrag, null);
}
function toolBoxOnDragStart(ele, mx, my) {
  xZIndex('toolcontainer', highZ++);
}
function toolBoxOnDrag(ele, mdx, mdy) {
  xMoveTo('toolcontainer', xLeft('toolcontainer') + mdx, xTop('toolcontainer') + mdy);
}


/**
 *  attach event listeners to interactives elements in the colorpicker
 */
function addListeners() {
    // attach event to gradients
    var colorgradient = xGetElementById('colorgradient');
    addEvent(colorgradient, 'mouseover', startMouseCoord, false);
    addEvent(colorgradient, 'mouseout', stopMouseCoord, false);
    var bwgradient = xGetElementById('bwgradient');
    addEvent(bwgradient, 'mouseover', startMouseCoord, false);
    addEvent(bwgradient, 'mouseout', stopMouseCoord, false);
    // attach event to inputs
    for (i=0;i<activeColorSpace.length;i++){
      varName = 'T'+activeColorSpace[i];
      colorSpaceAr = varList[varName];
      for (j=0;j<colorSpaceAr.length;j++){
        //colorSpaceAr[j] //id
        var colorInput = xGetElementById(colorSpaceAr[j]);
        addEvent(colorInput, 'keyup', changeColorFromInput, false);
        addEvent(colorInput, 'focus', cancelEnter, false);
      }
    }
    var HexBox = xGetElementById('Hex');
    addEvent(HexBox, 'keyup', changeColorFromHexInput, false);
    addEvent(HexBox, 'focus', cancelEnter, false);
}
// start event listener when page load, EventManager is external
EventManager.Add(window, 'load', addListeners, false);