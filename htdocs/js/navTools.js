/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

// Global variables

// dhtmlBox constructor
function dhtmlBox() {
  this.anchor = xGetElementById("mapAnchorDiv");
  this.target = xGetElementById("mainDHTMLDiv");
  this.image = xGetElementById("mapImageDiv");
  this.canvas = xGetElementById("myCanvasDiv");
  this.canvas2 = xGetElementById("myCanvas2Div");
  this.displayContainer = xGetElementById("diplayContainerDiv");
  this.displayCoords = xGetElementById("displayCoordsDiv");
  this.displayMeasure = xGetElementById("displayMeasureDiv");

  this.x1 = this.y1 = this.x2 = this.y2 = -1;
  this.drag = false;
  this.isActive = false;
  this.dblClick = false;
  this.keyEscape = false;
}

// method prototypes
function dhtmlBox_initialize() {
  jg = new jsGraphics(this.canvas.id); // a drawing canvas for the lines and points
  jg2 = new jsGraphics(this.canvas2.id); // a drawing canvas for the last moving vertex

  dhtmlBox.changeTool() //make the previous tool selected the current one
  
  this.target.style.zIndex = 1000;

  this.width = xWidth(this.anchor);
  this.height = xHeight(this.anchor);

  this.resizeAndMoveDivs()

  if (this.dispPos == "top") this.dispPosPx = -13;
  else if (this.dispPos == "bottom") this.dispPosPx = this.height;
  else this.dispPosPx = 0;
  xMoveTo(this.displayContainer,xPageX(this.anchor),xPageY(this.anchor) + this.dispPosPx);

}

function dhtmlBox_resizeAndMoveDivs() {
  xResizeTo(this.image,this.width,this.height)
  xResizeTo(this.target,this.width,this.height)
  xResizeTo(this.canvas,this.width,this.height)
  xResizeTo(this.canvas2,this.width,this.height)
  xWidth(this.displayContainer,this.width)

  xMoveTo(this.image,xPageX(this.anchor),xPageY(this.anchor))
  xMoveTo(this.target,xPageX(this.anchor),xPageY(this.anchor))
  xMoveTo(this.canvas,xPageX(this.anchor),xPageY(this.anchor))
  xMoveTo(this.canvas2,xPageX(this.anchor),xPageY(this.anchor))
  xShow(this.image)

  xClip(this.target,0,this.width,this.height,0)
  xClip(this.image,0,this.width,this.height,0)
}

// the mouse events are managed in this function according to the mapping tool selected
// shape accepted values : point, rect_or_point, rectangle, rectangle_or_point, line, polygon
// action : submit, measure
// cursorStyle : crosshair, help, move
// toolName : name of the tool (ie query, zoomin, ...)
function dhtmlBox_changetool() {
  // get the checked tool and its values
  for (var i =0; i < myform.tool.length ; i++) {
    if (myform.tool[i].checked) {
	  var toolValues = myform.tool[i].value.split(",")
	  this.shapeType = toolValues[0]
	  this.action = toolValues[1]
	  this.cursorStyle = toolValues[2]
	  this.toolName = toolValues[3]
    }
  }
  
  // clear the drawing canvas
  jg.clear();

  xRemoveEventListener(this.target,'mousedown',this.domousedown)
  xRemoveEventListener(this.target,'mouseup',this.domouseup)
  xRemoveEventListener(this.target,'mousemove',this.domousemove)
  xRemoveEventListener(this.target,'mouseout',this.domouseout)
  xRemoveEventListener(this.target, 'dblclick', this.dodblclick)
  
  xAddEventListener(this.target,'mousedown',this.domousedown)
  xAddEventListener(this.target,'mouseup',this.domouseup)
  xAddEventListener(this.target,'mousemove',this.domousemove)
  xAddEventListener(this.target,'mouseout',this.domouseout)

  xAddEventListener(document,'keydown',this.dokeydown)
 
  this.isActive = false

  if (this.shapeType == 'polygon' || this.shapeType == 'line') {
    xAddEventListener(this.target,'dblclick', this.dodblclick)
  }
  
  this.displayMeasure.innerHTML = '';
  if (this.action == 'measure') {
  	this.measure = 0;
  }

  // cursor style
  if (this.target.style) this.target.style.cursor = this.cursorStyle
  jg.paint()
}

function dhtmlBox_mousedown(evt) {
  var e = new xEvent(evt);
  
  if (!(xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) && evt.button == 2) { //right clic
    dhtmlBox.rightclic = true;
  } else {
    dhtmlBox.rightclic = false;

    dhtmlBox.x1 = dhtmlBox.x2 = e.offsetX;
    dhtmlBox.y1 = dhtmlBox.y2 = e.offsetY;
	
    if (dhtmlBox.shapeType == "point") {
      jg.clear();  // if page not reloaded automaticaly, previous crosses are deleted
    }
	
	if (!dhtmlBox.isActive) {
	  dhtmlBox.isActive = true
	  dhtmlBox.dblClick = false
      dhtmlBox.Xpoints = new Array()
   	  dhtmlBox.Ypoints = new Array()
	  if (dhtmlBox.shapeType == 'polygon' || dhtmlBox.shapeType == 'line') { //init
      	  jg.clear();
	      //jg2.clear();
    	  dhtmlBox.cnv_clicks = 0;
	      dhtmlBox.draw_x = new Array();
    	  dhtmlBox.draw_y = new Array();
	      dhtmlBox.measure = 0;
		  dhtmlBox.dblClick = false;
    	  dhtmlBox.keyEscape = false;
	  }
    }
  }
  if (dhtmlBox.action == 'submit' && (dhtmlBox.shapeType == 'polygon' || dhtmlBox.shapeType == 'line')) {
    dhtmlBox.drag = false; // to provide the curves draw (ie for polygon or line selection submit, TODO test if curves can be used
  } else {
	dhtmlBox.drag = true; // the mouse is down
  }
}

function dhtmlBox_mousemove(evt) {
  var e = new xEvent(evt);

  //show the coords display
  xShow(dhtmlBox.displayContainer);

  if(dhtmlBox.drag && dhtmlBox.isActive) { //the mouse is down
    dhtmlBox.x2 = e.offsetX;
    dhtmlBox.y2 = e.offsetY;
	if (dhtmlBox.shapeType == 'point') {
      jg.clear();
      dhtmlBox.x1 = dhtmlBox.x2;
      dhtmlBox.y1 = dhtmlBox.y2;
    }
    jg2.clear();
    dhtmlBox.paint();
    if (dhtmlBox.shapeType == 'polygon') dhtmlBox.lastLinePaint(); // last line is drawn while moving
  }
  else if ((dhtmlBox.shapeType == 'polygon' || dhtmlBox.shapeType == 'line') && dhtmlBox.isActive == true) {
      dhtmlBox.x2 = e.offsetX;
      dhtmlBox.y2 = e.offsetY;
      dhtmlBox.lastLinePaint(); // the last line is drawn while moving
  }
  // display the coordinates
  dhtmlBox.displayCoords.innerHTML = dhtmlBox.coord_msg + Math.round((e.offsetX * dhtmlBox.pixel_size) + dhtmlBox.boxx)  +" / "+ Math.round(((dhtmlBox.mapHeight - e.offsetY) * dhtmlBox.pixel_size) + dhtmlBox.boxy);
}

function dhtmlBox_mouseup(evt) {
  var e = new xEvent(evt);

  if (dhtmlBox.rightclic == true) {
    jg2.clear();
  } else if (dhtmlBox.isActive) {

    dhtmlBox.drag = false; //the mouse is now up

    dhtmlBox.x2 = e.offsetX;
    dhtmlBox.y2 = e.offsetY;

    //the box is too small
    if (dhtmlBox.shapeType == 'rectangle_or_point' && ((Math.abs(dhtmlBox.x1-dhtmlBox.x2) <= dhtmlBox.jitter) || (Math.abs(dhtmlBox.y1-dhtmlBox.y2) <= dhtmlBox.jitter))) {
        dhtmlBox.x2 = dhtmlBox.x1 = Math.abs(dhtmlBox.x1-dhtmlBox.x2) /2 + Math.min(dhtmlBox.x1, dhtmlBox.x2) //zoom to center of the box
        dhtmlBox.y2 = dhtmlBox.y1 = Math.abs(dhtmlBox.y1-dhtmlBox.y2) /2 + Math.min(dhtmlBox.y1, dhtmlBox.y2)
    }

    // rectangle draw finished
    if (dhtmlBox.shapeType == 'point' || dhtmlBox.shapeType == 'rectangle' || dhtmlBox.shapeType == 'rectangle_or_point' || dhtmlBox.shapeType == 'pan')
      dhtmlBox.isActive = false;
    
    // polygon closed by click on the first point
    if (dhtmlBox.shapeType == 'polygon' && Math.abs(dhtmlBox.x2 - dhtmlBox.draw_x[1]) <= dhtmlBox.jitter && Math.abs(dhtmlBox.y2 - dhtmlBox.draw_y[1]) <= dhtmlBox.jitter && dhtmlBox.draw_x.length > 2) {
        dhtmlBox.keyEscape = true;
        dhtmlBox.isActive = false;
        jg2.clear();
    }
    dhtmlBox.paint();
  }
}

function dhtmlBox_mouseout(evt) {
  var e = new xEvent(evt);

  xHide(dhtmlBox.displayContainer);
  jg2.clear();
}

function dhtmlBox_dblclick(evt) {
  var e = new xEvent(evt)
  dhtmlBox.dblClick = true
  dhtmlBox.isActive = false
  jg2.clear()
  dhtmlBox.paint()
}

function dhtmlBox_keydown(evt) { // 
  if (evt.keyCode == '27') {
    dhtmlBox.keyEscape = true
    dhtmlBox.isActive = false
    jg2.clear()
	if (dhtmlBox.action == 'submit') {
		dhtmlBox.changeTool() // cancel the use of the current tool
		dhtmlBox.resizeAndMoveDivs()
		xHide(dhtmlBox.anchor)
	}
	else if (dhtmlBox.action == 'measure') {
		dhtmlBox.paint() //
	}
  }
}

function dhtmlBox_paint() { // draws alternatively boxes, lines, polylines, crosses, or pan the map
  var x, y, w, h;

  x = Math.min(this.x1, this.x2);
  y = Math.min(this.y1, this.y2);

  if (this.shapeType == 'pan') {
    var dx = this.x2 - this.x1
    var dy = this.y2 - this.y1

    xMoveTo(this.image,dx + xPageX(this.anchor),dy + xPageY(this.anchor))
    xClip(this.image,(dy<0)? Math.abs(dy):0,(dx>0)? this.width - dx : this.width,(dy>0)? this.height-dy:this.height,(dx<0)? Math.abs(dx):0)
	
	this.Xpoints[0] = dhtmlBox.width/2 - (dhtmlBox.x2 - dhtmlBox.x1)
    this.Ypoints[0] = dhtmlBox.height/2 - (dhtmlBox.y2 - dhtmlBox.y1)
    //xResizeTo(this.image,(dy<0)? Math.abs(dy):0,(dx>0)? this.width - dx : this.width,(dy>0)? this.height-dy:this.height,(dx<0)? Math.abs(dx):0);
  }
  else if (this.shapeType == 'point' ||
	(this.shapeType == 'rectangle_or_point' && this.x1==this.x2 && this.y1==this.y2)) { //draws only a cross
	jg.clear();
    jg.drawLineW(x-this.cursorsize,y - this.thickness /2,this.cursorsize * 2);
    jg.drawLineH(x - this.thickness /2,y-this.cursorsize ,this.cursorsize * 2);
    jg.paint();
    this.Xpoints[0] = x
    this.Ypoints[0] = y
    
    if (this.shapeType == 'rectangle_or_point') { // submit a rectangle coords (2 equal coords)
      this.Xpoints[1] = this.x2
      this.Ypoints[1] = this.y2
    }
  }
  else if (this.shapeType == 'rectangle' || this.shapeType == 'rectangle_or_point') {
      w = Math.abs(this.x1-this.x2)
      h = Math.abs(this.y1-this.y2)
      jg.clear()
      jg.drawRect(x,y,w,h,this.thickness)
      jg.paint()
      this.Xpoints[0] = this.x1
      this.Ypoints[0] = this.y1
      this.Xpoints[1] = this.x2
      this.Ypoints[1] = this.y2
  }
  else if (this.shapeType == 'line' || this.shapeType == 'polygon') {
    if (!this.keyEscape) { // Escape key is pressed
      ++this.cnv_clicks;
      this.draw_x[this.cnv_clicks] = this.x2;
      this.draw_y[this.cnv_clicks] = this.y2;
    }
    this.Xpoints[this.cnv_clicks - 1] = this.draw_x[this.cnv_clicks];
    this.Ypoints[this.cnv_clicks - 1] = this.draw_y[this.cnv_clicks];
    if (xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) { // IE/Mac specificity
	  if (!this.isActive && this.shapeType == 'polygon') {
        this.Xpoints[this.cnv_clicks] = this.draw_x[1];
        this.Ypoints[this.cnv_clicks] = this.draw_y[1];	  	
	  }
      jg.clear();
      jg.drawPolylinePts(this.Xpoints,this.Ypoints, this.d2pts);
    }
    else {
        jg.drawLinePts(this.draw_x[this.cnv_clicks],this.draw_y[this.cnv_clicks],this.draw_x[this.cnv_clicks - 1],this.draw_y[this.cnv_clicks - 1],this.d2pts);
      	if (!this.isActive  && this.shapeType == 'polygon') // close the polygon
          jg.drawLinePts(this.draw_x[this.cnv_clicks],this.draw_y[this.cnv_clicks],this.draw_x[1],this.draw_y[1],this.d2pts);		
      }
    }
    jg.paint();
	// submit the form with the values
	if (dhtmlBox.action == 'submit' && !this.isActive ) {
          if (dhtmlBox.shapeType == 'rectangle_or_point')
            dhtmlBox.shapeType = 'rectangle'
          dhtmlBox.submitForm()
	}
	if (dhtmlBox.action == 'measure') {
		dhtmlBox.measureShape()
	}
}

function dhtmlBox_lastLinePaint() {

  var x2 = this.x2;
  var y2 = this.y2;
  var x = this.draw_x[this.cnv_clicks];
  var y = this.draw_y[this.cnv_clicks];
  var x0 = this.draw_x[1];
  var y0 = this.draw_y[1];

//  if (xIE || xUA.indexOf('mac')!=-1) { //use the drawing API
  if (xUA.indexOf('mac')!=-1) { //use the drawing API
    jg2.clear();
    jg2.drawLinePts(x2,y2,x,y,this.d2pts); //draw the last vertex
    if (this.shapeType == 'polygon') {
      jg2.drawLinePts(x2,y2,x0,y0,this.d2pts) // also draw the line to close the polygon
    }
    jg2.paint()
  }
  else { //doesn't use the drawing API
  
    if (this.canvas2.innerHTML == '') { // create the DIVs if doesn't exist
      for (var i=0; i< this.nbPts * 2; i++) {
        this["vertexPt"+i] = xCreateElement('div')
        xAppendChild(this.canvas2,this["vertexPt"+i],200)
        this["vertexPt"+i].className = "point"
        xHide(this["vertexPt"+i])
        if (this["vertexPt"+i].style) this["vertexPt"+i].style.position = "absolute"
      }
    }
    var dx = x2 - x
    var dy = y2 - y
    for (var i=0;i<this.nbPts;i++) {
      var a = this["vertexPt"+i]
      xMoveTo(a,x + dx *i/ this.nbPts,y + dy *i/ this.nbPts)
      xShow(a)
    }
    if (this.shapeType == 'polygon') {
      var dx0 = x2 - x0
      var dy0 = y2 - y0
      for (var i=this.nbPts;i<this.nbPts * 2;i++) {
        var a = this["vertexPt"+i]
        xMoveTo(a,x0 + dx0 * (i-this.nbPts)/this.nbPts,y0 + dy0 * (i-this.nbPts) / this.nbPts)
        xShow(a);
      }
    }
  }
}

function dhtmlBox_submitForm() {
	var coords = new String()
	if (this.dblClick) { // manually delete 2 not needed values
		this.Xpoints.splice(this.Xpoints.length - 2, 2)
	}
	for (i = 0; i < this.Xpoints.length; i++) {
		coords += this.Xpoints[i] +"," + this.Ypoints[i] + ";"
	}
	if (dhtmlBox.shapeType == 'polygon') coords += this.Xpoints[0] +"," + this.Ypoints[0] + ";"  // last point equal to first
	myform.selection_coords.value = coords.substring(0,coords.length - 1) // delete the last coma
	// change the value of the tool form input
/*    for (var i =0; i < myform.tool.length ; i++) {
        if (myform.tool[i].checked) {
		    myform.tool[i].value = this.toolName
		}
	}
*/
	myform.selection_type.value = (this.shapeType == "pan") ? "point" : this.shapeType
//	alert ("type : " + myform.selection_coords.value + "\n coords : " + myform.selection_coords.value)
	xShow(dhtmlBox.anchor);
   	myform.submit();
}

function dhtmlBox_measureShape() {
	if (this.shapeType == 'line') { //Calculate the distance and display it
	  if (this.cnv_clicks > 1 && !this.keyEscape) {
        // distance calculation
        this.dist_x = (this.draw_x[this.cnv_clicks] - this.draw_x[this.cnv_clicks - 1]) * this.pixel_size;
        this.dist_y = (this.draw_y[this.cnv_clicks] - this.draw_y[this.cnv_clicks - 1]) * this.pixel_size;
        this.measure += Math.sqrt(this.dist_x * this.dist_x + this.dist_y * this.dist_y);
      }
	  
      if (this.dist_unit.indexOf('k') != -1) this.measureDisp = Math.round(this.measure*100)/100
      else this.measureDisp = Math.round(this.measure)
      this.displayMeasure.innerHTML = this.dist_msg + this.measureDisp.toString() + this.dist_unit
	}
	if (this.shapeType == 'polygon') { // calculate the surface and display it
	  if (this.cnv_clicks > 1  && !this.keyEscape) {
        //surface calculation
        var i = 0;
        this.measure = 0;
        while (i < this.cnv_clicks - 1) {
          this.measure += this.Xpoints[i] * this.Ypoints[i+1] - this.Xpoints[i+1] * this.Ypoints[i];
          ++i;
        }
        this.measure += this.Xpoints[this.cnv_clicks -1] * this.Ypoints[0] - this.Xpoints[0] * this.Ypoints[this.cnv_clicks -1];
        var pix_surf = this.pixel_size * this.pixel_size;
        this.measure = Math.abs(this.measure.toString()) / 2 * pix_surf;
	  }
      if (this.surf_unit.indexOf('k') != -1) this.measure = Math.round(this.measure*10000)/10000
	  else this.measure = Math.round(this.measure)
      this.displayMeasure.innerHTML = this.surf_msg+ this.measure +this.surf_unit;
    }
}

new dhtmlBox(0);

dhtmlBox.prototype.initialize = dhtmlBox_initialize // create instance method
dhtmlBox.prototype.resizeAndMoveDivs = dhtmlBox_resizeAndMoveDivs
dhtmlBox.prototype.domousedown = dhtmlBox_mousedown
dhtmlBox.prototype.domousemove = dhtmlBox_mousemove
dhtmlBox.prototype.domouseup = dhtmlBox_mouseup
dhtmlBox.prototype.domouseout = dhtmlBox_mouseout
dhtmlBox.prototype.dodblclick = dhtmlBox_dblclick
dhtmlBox.prototype.dokeydown = dhtmlBox_keydown
dhtmlBox.prototype.paint = dhtmlBox_paint
dhtmlBox.prototype.lastLinePaint = dhtmlBox_lastLinePaint // draw the last straight vertex for the measure tool
dhtmlBox.prototype.changeTool = dhtmlBox_changetool
dhtmlBox.prototype.submitForm = dhtmlBox_submitForm
dhtmlBox.prototype.measureShape = dhtmlBox_measureShape
