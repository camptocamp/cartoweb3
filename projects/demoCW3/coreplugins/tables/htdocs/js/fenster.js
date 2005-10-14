window.onunload = function()
{
  fen[1].onunload();
}

function setupFenster() 
{
  myFensters = xGetElementsByClassName('fenster', null, null);
  fen = new Array();
  if(xGetElementsByClassName('cw3table', null, null).length)
    fen[1] = new xFenster('fen1', 240, 100, 'fenBar1', 'fenClBtn1');
}


function fensterDetail(url)
{
	xGetElementById('fenDetail').src=url;
}


// object-oriented version - see drag1.php for a procedural version

function xFenster(eleId, iniX, iniY, barId, clBtnId) // object prototype
{
  // Private Properties
  var me = this;
  var ele = xGetElementById(eleId);
  var clBtn = xGetElementById(clBtnId);
  var iframeHack = xGetElementById(iframeHack);
  var dropdowns = xGetElementsByTagName("select", document)
  var tmpDpDwn = new Array();
  var x, y, w, h, maximized = false;
  // Public Methods
  this.onunload = function()
  {
    xDisableDrag(barId);
    me = ele = clBtn = null;
//    clBtn.onclick = ele.onmousedown = null;
  }
  this.paint = function()
  {
    //xMoveTo(clBtn, xWidth(ele),0);
    xMoveTo(clBtn, xWidth(ele) - xWidth(clBtn) - 3, 0);
  }
  this.close = function()
  {
    var tmpDpDwn = xGetElementsByClassName('tmpDpDwn');
    for (i=0; i < tmpDpDwn.length; i++) {
      tmpDpDwn[i].code = null;
      var parent = xParent(tmpDpDwn[i]);
      tmpDpDwn[i].removeNode(true);
      dropdowns[i].style.display = 'inline';
    }
    xHide(ele)
  }
  // Private Event Listeners
  function barOnDrag(e, mdx, mdy)
  {
    xMoveTo(ele, xLeft(ele) + mdx, xTop(ele) + mdy);

  }
  function fenOnMousedown()
  {
    xZIndex(ele, xFenster.z++);
  }
  function clOnClick()
  {
    if (document.all) me.close();
    xHide(ele);
  }
  
  // Constructor Code
  xFenster.z++;
  xMoveTo(ele, iniX, iniY);
  this.paint();
  xEnableDrag(barId, null, barOnDrag, null);
  clBtn.onclick = clOnClick;
  ele.onmousedown = fenOnMousedown;
  xZIndex(ele,xFenster.z);
  // hack change type for dropdowns
  if (document.all) {
    for (i=0;  i < dropdowns.length; i++) {
      var strg = "<input type='text' class='tmpDpDwn' ";
      strg += "style='width:"+ xWidth(dropdowns[i])+";'";
      strg += " value='"+ dropdowns[i].options[dropdowns[i].selectedIndex].text;
      strg += "' onfocus='javascript:fen[1].close()' />";
      dropdowns[i].insertAdjacentHTML("BeforeBegin",strg);
      dropdowns[i].style.display = "none";
    }
  }
  xShow(ele);
} // end xFenster object prototype

xFenster.z = 150000; // xFenster static property

EventManager.Add(window, 'load', setupFenster, false);