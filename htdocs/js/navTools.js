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

  // make the previous tool selected the current one
  for (var i =0; i < myform.TOOLBAR_CMD.length; i++) {
    if (myform.TOOLBAR_CMD[i].checked) {
      dhtmlBox.changeTool(myform.TOOLBAR_CMD[i].value);
    }
  }	
  this.target.style.zIndex = 1000;

  this.width = xWidth(this.anchor);
  this.height = xHeight(this.anchor);

  xResizeTo(this.image,this.width,this.height);
  xResizeTo(this.target,this.width,this.height);
  xResizeTo(this.canvas,this.width,this.height);
  xResizeTo(this.canvas2,this.width,this.height);
  xWidth(this.displayContainer,this.width);

  xMoveTo(this.image,xPageX(this.anchor),xPageY(this.anchor));
  xMoveTo(this.target,xPageX(this.anchor),xPageY(this.anchor));
  xMoveTo(this.canvas,xPageX(this.anchor),xPageY(this.anchor));
  xMoveTo(this.canvas2,xPageX(this.anchor),xPageY(this.anchor));
  xShow(this.image);

  if (this.dispPos == "top") this.dispPos = -13;
  else if (this.dispPos == "bottom") this.dispPos = this.height;
  else this.dispPos = 0;
  xMoveTo(this.displayContainer,xPageX(this.anchor),xPageY(this.anchor) + this.dispPos);


  xClip(this.target,0,this.width,this.height,0);
}


function dhtmlBox_changetool(tool) {// the mouse events are managed in this function according to the mapping tool selected
  myform.TOOLBAR_CMD.value = tool;
  this.currentTool = tool.toLowerCase();

  // clear the drawing canvas
  jg.clear();

  xRemoveEventListener(this.target,'mousedown',this.domousedown);
  xRemoveEventListener(this.target,'mouseup',this.domouseup);
  xRemoveEventListener(this.target,'mousemove',this.domousemove);
  xRemoveEventListener(this.target,'mouseout',this.domouseout);
  xRemoveEventListener(this.target, 'dblclick', this.dodblclick);
  
  xAddEventListener(this.target,'mousedown',this.domousedown);
  xAddEventListener(this.target,'mouseup',this.domouseup);
  xAddEventListener(this.target,'mousemove',this.domousemove);
  xAddEventListener(this.target,'mouseout',this.domouseout);

  xAddEventListener(document,'keydown',this.dokeydown);

  if (this.currentTool == 'measure' || this.currentTool == 'surface' || this.currentTool == 'polygon' || this.currentTool == 'line') {
    this.isActive = false;
    this.measure = 0;
    this.displayCoords.innerHTML = '';
    this.displayMeasure.innerHTML = '';
    xAddEventListener(this.target,'dblclick', this.dodblclick);
  }

  // cursor style
  if (this.currentTool == 'zoom_in' || this.currentTool == 'zoom_out' || this.currentTool == 'measure' || this.currentTool == 'surface' || this.currentTool == 'polygon' || this.currentTool == 'line') {
    if (this.target.style) this.target.style.cursor = "crosshair";
  } else if (this.currentTool == 'pan') {
    if (this.target.style) this.target.style.cursor = "move";
  } else if (this.currentTool == 'query') {
    if (this.target.style) this.target.style.cursor = "help";
  }
  jg.paint();
}

function dhtmlBox_mousedown(evt) {
  var e = new xEvent(evt);
  
  if (!(xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) && evt.button == 2) { //right clic
    dhtmlBox.rightclic = true;
  } else {
    dhtmlBox.rightclic = false;

    dhtmlBox.x1 = dhtmlBox.x2 = e.offsetX;
    dhtmlBox.y1 = dhtmlBox.y2 = e.offsetY;
	

    if (dhtmlBox.currentTool == "zoom_out") {
      jg.clear();  // if page not reloaded automaticaly, previous crosses are deleted
    }

    if (!dhtmlBox.isActive && (dhtmlBox.currentTool == 'measure' || dhtmlBox.currentTool == 'surface' || dhtmlBox.currentTool == 'polygon'  || dhtmlBox.currentTool == 'line')) { //init
      jg.clear();
      //jg2.clear();
      dhtmlBox.cnv_clicks = 0;
      dhtmlBox.draw_x = new Array();
      dhtmlBox.draw_y = new Array();
      dhtmlBox.Xpoints = new Array();
      dhtmlBox.Ypoints = new Array();
      dhtmlBox.measure = 0;
      dhtmlBox.isActive = true;
	  dhtmlBox.dblClick = false;
      dhtmlBox.keyEscape = false;
    }
  }
  if (dhtmlBox.currentTool == 'polygon'  || dhtmlBox.currentTool == 'line') {
    dhtmlBox.drag = false; // to provide the curves draw
  } else {
	dhtmlBox.drag = true; // the mouse is down
  }
}

function dhtmlBox_mousemove(evt) {
  var e = new xEvent(evt);
  var name = e.target.id;

  //show the coords display
  xShow(dhtmlBox.displayContainer);


  if(dhtmlBox.drag) { //the mouse is down
    dhtmlBox.x2 = e.offsetX;
    dhtmlBox.y2 = e.offsetY;
    if(dhtmlBox.currentTool == 'zoom_out') { // only one click events
      jg.clear();
      dhtmlBox.x1 = dhtmlBox.x2;
      dhtmlBox.y1 = dhtmlBox.y2;
    }
    dhtmlBox.paint();
  }
  else if ((dhtmlBox.currentTool == 'measure' || dhtmlBox.currentTool == 'surface' || dhtmlBox.currentTool == 'polygon'  || dhtmlBox.currentTool == 'line') && dhtmlBox.isActive == true) {
      dhtmlBox.x2 = e.offsetX;
      dhtmlBox.y2 = e.offsetY;
      dhtmlBox.lastLinePaint(); // the last line is drawn while moving
  }
    // display the coordinates
    dhtmlBox.displayCoords.innerHTML = dhtmlBox.coord_msg + Math.round((e.offsetX * dhtmlBox.pixel_size) + dhtmlBox.boxx)  +" / "+ Math.round(((dhtmlBox.mapHeight - e.offsetY) * dhtmlBox.pixel_size) + dhtmlBox.boxy);
}

function dhtmlBox_mouseup(evt) {
  var e = new xEvent(evt);
  var name = e.target.id;

  if (dhtmlBox.rightclic == true) {
    jg2.clear();
  } else {

    dhtmlBox.drag = false; //the mouse is now up

    dhtmlBox.x2 = e.offsetX;
    dhtmlBox.y2 = e.offsetY;

    //the box is too small
    if(((Math.abs(dhtmlBox.x1-dhtmlBox.x2) <= dhtmlBox.jitter) || (Math.abs(dhtmlBox.y1-dhtmlBox.y2) <= dhtmlBox.jitter)) && dhtmlBox.currentTool == 'zoom_in') {
      dhtmlBox.x2 = dhtmlBox.x1 = Math.abs(dhtmlBox.x1-dhtmlBox.x2) /2 + Math.min(dhtmlBox.x1, dhtmlBox.x2); //zoom to center of the box
      dhtmlBox.y2 = dhtmlBox.y1 = Math.abs(dhtmlBox.y1-dhtmlBox.y2) /2 + Math.min(dhtmlBox.y1, dhtmlBox.y2);;
    }

    // submit the form with the values
    if (dhtmlBox.currentTool == 'zoom_in' || dhtmlBox.currentTool == 'zoom_out' || dhtmlBox.currentTool == 'query') {
      dhtmlBox.removeEventsWait();
      myform.INPUT_TYPE.value = "auto_rect";
      myform.INPUT_COORD.value = dhtmlBox.x1+","+dhtmlBox.y1+","+dhtmlBox.x2+","+dhtmlBox.y2
      myform.submit();
    }
    else if (dhtmlBox.currentTool == 'pan') {
      myform.INPUT_TYPE.value = "auto_point";
      // pan or simple pan click
      if (dhtmlBox.x2 == dhtmlBox.x1 && dhtmlBox.y2 == dhtmlBox.y1) { //simple click
        var x = dhtmlBox.x2;
        var y = dhtmlBox.y2
        myform.INPUT_COORD.value = x+","+y+";"+x+","+y;
      } else {// pan
        //new center coordinates
        var x = dhtmlBox.width/2 - (dhtmlBox.x2 - dhtmlBox.x1);
        var y = dhtmlBox.height/2 - (dhtmlBox.y2 - dhtmlBox.y1);
        myform.INPUT_COORD.value = x+","+y+";"+x+","+y;
      }
      myform.submit();
    } else if (dhtmlBox.currentTool == 'surface' || dhtmlBox.currentTool == 'polygon') {
		// polygon closed by click on the first point
		if (Math.abs(dhtmlBox.x2 - dhtmlBox.draw_x[1]) <= dhtmlBox.jitter && Math.abs(dhtmlBox.y2 - dhtmlBox.draw_y[1]) <= dhtmlBox.jitter && dhtmlBox.draw_x.length > 2) {
			dhtmlBox.keyEscape = true;
			dhtmlBox.isActive = false;
			jg2.clear();
		}
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
  var e = new xEvent(evt);
  dhtmlBox.dblClick = true;
  dhtmlBox.isActive = false;

  jg2.clear();
  dhtmlBox.paint();
}

function dhtmlBox_keydown(evt) { // ne peut etre utilisé que pour la dhtmlBox "main"
  evt = (evt) ? evt : ((event) ? event : null);
  dhtmlBox = dhtmlBox;
  if (evt.keyCode == '27' && (dhtmlBox.currentTool == 'measure' || dhtmlBox.currentTool == 'surface' || dhtmlBox.currentTool == 'polygon'  || dhtmlBox.currentTool == 'line')) {
    dhtmlBox.keyEscape = true;
    dhtmlBox.isActive = false;
    jg2.clear();
    dhtmlBox.paint();
  }
}

function dhtmlBox_paint() { // draws alternatively boxes, lines, polylines, crosses, or pan the map
  var x, y, w, h;

  x = Math.min(this.x1, this.x2);
  y = Math.min(this.y1, this.y2);

  if (this.currentTool == 'zoom_out') { //draws only a cross
      jg.drawLineW(x-this.cursorsize,y - this.thickness /2,this.cursorsize * 2);
      jg.drawLineH(x - this.thickness /2,y-this.cursorsize ,this.cursorsize * 2);
      jg.paint();
  }

  else if (this.currentTool == 'zoom_in' || this.currentTool == 'query') {
    if(this.x1==this.x2 && this.y1==this.y2) {
        jg.clear();
        jg.drawLineW(x-this.cursorsize,y - this.thickness /2,this.cursorsize * 2);
        jg.drawLineH(x - this.thickness /2,y-this.cursorsize ,this.cursorsize * 2);
        jg.paint();

    } else {
      w = Math.abs(this.x1-this.x2);
      h = Math.abs(this.y1-this.y2);
      jg.clear();
          jg.drawRect(x,y,w,h,this.thickness);
      jg.paint();
    }
  }
  else if (this.currentTool == 'pan') {
    var dx,dy

    dx = this.x2 - this.x1;
    dy = this.y2 - this.y1;

    xMoveTo(this.image,dx + xPageX(this.anchor),dy + xPageY(this.anchor));
    xClip(this.image,(dy<0)? Math.abs(dy):0,(dx>0)? this.width - dx : this.width,(dy>0)? this.height-dy:this.height,(dx<0)? Math.abs(dx):0);
    //xResizeTo(this.image,(dy<0)? Math.abs(dy):0,(dx>0)? this.width - dx : this.width,(dy>0)? this.height-dy:this.height,(dx<0)? Math.abs(dx):0);
  }

  else if (this.currentTool == 'measure' || this.currentTool == 'line') {
    if (!this.keyEscape) { // Escape key is pressed
      ++this.cnv_clicks;
      this.draw_x[this.cnv_clicks] = this.x2;
      this.draw_y[this.cnv_clicks] = this.y2;
    }
    this.Xpoints[this.cnv_clicks - 1] = this.draw_x[this.cnv_clicks];
    this.Ypoints[this.cnv_clicks - 1] = this.draw_y[this.cnv_clicks];
    if (xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) { // IE/Mac specificity
      jg.clear();
      jg.drawPolylinePts(this.Xpoints,this.Ypoints, this.d2pts);
    }
    else {
        jg.drawLinePts(this.draw_x[this.cnv_clicks],this.draw_y[this.cnv_clicks],this.draw_x[this.cnv_clicks - 1],this.draw_y[this.cnv_clicks - 1],this.d2pts);
    }
	if (this.currentTool == 'measure') { //Calculate the distance and display it
	  if (this.cnv_clicks > 1 && !this.keyEscape) {
        // distance calculation
        this.dist_x = (this.draw_x[this.cnv_clicks] - this.draw_x[this.cnv_clicks - 1]) * this.pixel_size;
        this.dist_y = (this.draw_y[this.cnv_clicks] - this.draw_y[this.cnv_clicks - 1]) * this.pixel_size;
        this.measure += Math.sqrt(this.dist_x * this.dist_x + this.dist_y * this.dist_y);
      }
      if (this.dist_unit == ' m.') this.measure = Math.round(this.measure);
      else if (this.dist_unit == ' km.') this.measure = Math.round(this.measure*100)/100;
      this.displayMeasure.innerHTML = this.dist_msg + this.measure.toString() + this.dist_unit;
	} else if (this.currentTool == 'line' && !this.isActive) { // submit the form
		var coords = new String();
		for (i = 0; i < this.Xpoints.length; i++) {
			coords += this.Xpoints[i] +"," + this.Ypoints[i] + ",";
		}
		myform.selection_type.value = 'line';
		myform.selection_coords.value = coords.substring(0,coords.length - 1);
		myform.submit();
	}
    jg.paint();
  }
  else if (this.currentTool == 'surface' || this.currentTool == 'polygon') {
    if (!this.keyEscape) { // Escape key is pressed
      ++this.cnv_clicks;
      this.draw_x[this.cnv_clicks] = this.x2;
      this.draw_y[this.cnv_clicks] = this.y2;
    }
    this.Xpoints[this.cnv_clicks - 1] = this.draw_x[this.cnv_clicks];
    this.Ypoints[this.cnv_clicks - 1] = this.draw_y[this.cnv_clicks];
    if (xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) { // IE/Mac specificity
      if (!this.isActive) { // close the polygon
        this.Xpoints[this.cnv_clicks] = this.draw_x[1];
        this.Ypoints[this.cnv_clicks] = this.draw_y[1];
      }
      jg.clear();
      jg.drawPolylinePts(this.Xpoints,this.Ypoints, this.d2pts);
    }
    else {
      jg.drawLinePts(this.draw_x[this.cnv_clicks],this.draw_y[this.cnv_clicks],this.draw_x[this.cnv_clicks - 1],this.draw_y[this.cnv_clicks - 1],this.d2pts);
      if (!this.isActive) { // close the polygon
        jg.drawLinePts(this.draw_x[this.cnv_clicks],this.draw_y[this.cnv_clicks],this.draw_x[1],this.draw_y[1],this.d2pts);		
      }
    }

	if (this.currentTool == 'surface') { // calculate the surface and display it
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
      if (this.surf_unit == ' m².') this.measure = Math.round(this.measure);
      else if (this.surf_unit == ' km².') this.measure = Math.round(this.measure*10000)/10000;
      this.displayMeasure.innerHTML = this.surf_msg+ this.measure +this.surf_unit;
    } else if (this.currentTool == 'polygon' && !this.isActive) { // draw the closed polygon and submit form
		jg.paint();
		var coords = new String();
		for (i = 0; i < this.Xpoints.length; i++) {
			coords += this.Xpoints[i] +"," + this.Ypoints[i] + ",";
		}
		myform.selection_type.value = 'polygon';
		myform.selection_coords.value = coords.substring(0,coords.length - 1);
		myform.submit();
	}
	jg.paint();
  }
}


function dhtmlBox_lastLinePaint() {

  var x2 = this.x2;
  var y2 = this.y2;
  var x = this.draw_x[this.cnv_clicks];
  var y = this.draw_y[this.cnv_clicks];
  var x0 = this.draw_x[1];
  var y0 = this.draw_y[1];

  if (xIE || xUA.indexOf('mac')!=-1) { //use the drawing API
    jg2.clear();
    jg2.drawLinePts(x2,y2,x,y,this.d2pts); //draw the last vertex
    if (this.currentTool == "surface" || this.currentTool == "polygon") {
      jg2.drawLinePts(x2,y2,x0,y0,this.d2pts); // also draw the line to close the polygon
    }
    jg2.paint();
  }
  else { //doesn't use the drawing API
    if (this.canvas2.innerHTML == '') { // create the DIVs if doesn't exist
      for (var i=0; i< this.nbPts * 2; i++) {
        this["vertexPt"+i] = xCreateElement('div');
        xAppendChild(this.canvas2,this["vertexPt"+i],200);
        this["vertexPt"+i].className = "point";
        xHide(this["vertexPt"+i]);
        if (this["vertexPt"+i].style) this["vertexPt"+i].style.position = "absolute";
      }
    }
    var dx = x2 - x;
    var dy = y2 - y;
    for (var i=0;i<this.nbPts;i++) {
      var a = this["vertexPt"+i];
      xMoveTo(a,x + dx *i/ this.nbPts,y + dy *i/ this.nbPts);
      xShow(a);
    }
    if (this.currentTool == "surface" || this.currentTool == "polygon") {
      var dx0 = x2 - x0;
      var dy0 = y2 - y0;
      for (var i=this.nbPts;i<this.nbPts * 2;i++) {
        var a = this["vertexPt"+i];
        xMoveTo(a,x0 + dx0 * (i-this.nbPts)/this.nbPts,y0 + dy0 * (i-this.nbPts) / this.nbPts);
        xShow(a);
      }
    }
  }
}



new dhtmlBox(0);

dhtmlBox.prototype.initialize = dhtmlBox_initialize; // create instance method
dhtmlBox.prototype.domousedown = dhtmlBox_mousedown;
dhtmlBox.prototype.domousemove = dhtmlBox_mousemove;
dhtmlBox.prototype.domouseup = dhtmlBox_mouseup;
dhtmlBox.prototype.domouseout = dhtmlBox_mouseout;
dhtmlBox.prototype.dodblclick = dhtmlBox_dblclick;
dhtmlBox.prototype.dokeydown = dhtmlBox_keydown;
dhtmlBox.prototype.paint = dhtmlBox_paint;
dhtmlBox.prototype.lastLinePaint = dhtmlBox_lastLinePaint; // draw the last straight vertex for the measure tool
dhtmlBox.prototype.changeTool = dhtmlBox_changetool;
dhtmlBox.prototype.removeEventsWait = dhtmlBox_removeEventsWait;

/*
function changeTool(dhtmlBox,tool) {
  dhtmlBox.changetool(tool);
}
*/

function dhtmlBox_removeEventsWait() {
   xRemoveEventListener(this.target, 'mousemove', this.domousemove);
   if (this.target.style) this.target.style.cursor = "wait";
}
