// Global variables

// dBox constructor
function dBox(anchor_layer, target_layer, image_layer, canvas, canvas2, displayContainer_layer, display_position, displayCoords_layer, displayMeasure_layer, color, thickness, cursorsize, jitter,pointSize, d2pts,nbPts) {
  this.anchor = xGetElementById(anchor_layer);
  this.target = xGetElementById(target_layer);
  this.image = xGetElementById(image_layer);
  this.canvas = xGetElementById(canvas);
  this.canvas2 = xGetElementById(canvas2);
  this.displayContainer = xGetElementById(displayContainer_layer);
  this.displayCoords = xGetElementById(displayCoords_layer);
  this.displayMeasure = xGetElementById(displayMeasure_layer);

  this.dispPos = display_position;
  this.color = color;
  this.thickness = thickness;
  this.cursorsize = cursorsize;
  this.jitter = jitter; //minimum size of a box dimension
  this.d2pts = d2pts; // the distance between to points (measure tools);
  this.nbPts = nbPts; // number of points for the last vertex

  this.pixelSize = 1; // will be used to convert the pixel coordinates in geo

  this.x1 = this.y1 = this.x2 = this.y2 = -1;
  this.drag = false;
  this.isActive = false;
  this.keyEscape = false;
}

// method prototypes
function dBox_initialize() {
  jg = new jsGraphics(this.canvas.id); // a drawing canvas for the lines and points
  jg2 = new jsGraphics(this.canvas2.id); // a drawing canvas for the last moving vertex

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


function dBox_changetool(tool) {// the mouse events are managed in this function according to the mapping tool selected
  myform.TOOLBAR_CMD.value = tool;
  this.currentTool = tool.toLowerCase();

  // clear the drawing canvas
  jg.clear();

  xAddEventListener(this.target,'mousedown',this.domousedown);
  xAddEventListener(this.target,'mouseup',this.domouseup);
  xAddEventListener(this.target,'mousemove',this.domousemove);
  xAddEventListener(this.target,'mouseout',this.domouseout);

  xAddEventListener(document,'keydown',this.dokeydown);

  if (this.currentTool == 'measure' || this.currentTool == 'surface') {
    jg.clear();
    this.isActive = false;
    this.measure = 0;
    this.displayCoords.innerHTML = '';
    this.displayMeasure.innerHTML = '';
    xAddEventListener(this.target,'dblclick', this.dodblclick);
  } else {
    xRemoveEventListener(this.target, 'dblclick', this.dodblclick);
  }

  // cursor style
  if (this.currentTool == 'zoom_in' || this.currentTool == 'zoom_out' || this.currentTool == 'measure' || this.currentTool == 'surface') {
    if (this.target.style) this.target.style.cursor = "crosshair";
  } else if (this.currentTool == 'pan') {
    if (this.target.style) this.target.style.cursor = "move";
  } else if (this.currentTool == 'query') {
    if (this.target.style) this.target.style.cursor = "help";
  }
  jg.paint();
}

function dBox_mousedown(evt) {
  var e = new xEvent(evt);
  var name = e.target.id;
  var dBox = name.substring(0,name.length - 3); // the name of the layer without the 'DIV'
  dBox = eval(dBox);

  if (evt.button == 2) { //right clic
    dBox.rightclic = true;
  } else {
    dBox.rightclic = false;
    dBox.drag = true; //the mouse is down

    dBox.x1 = dBox.x2 = e.offsetX;
    dBox.y1 = dBox.y2 = e.offsetY;

    if (dBox.currentTool == "zoom_out") {
      jg.clear();  // if page not reloaded automaticaly, previous crosses are deleted
    }

    if (!dBox.isActive && (dBox.currentTool == 'measure' || dBox.currentTool == 'surface')) { //init
      jg.clear();
      //jg2.clear();
      dBox.cnv_clicks = 0;
      dBox.draw_x = new Array();
      dBox.draw_y = new Array();
      dBox.Xpoints = new Array();
      dBox.Ypoints = new Array();
      dBox.measure = 0;
      dBox.isActive = true;
      dBox.keyEscape = false;
    }
  }
}

function dBox_mousemove(evt) {
  var e = new xEvent(evt);
  var name = e.target.id;
  var dBox = name.substring(0,name.length - 3); // the name of the layer without the 'DIV'
  dBox = eval(dBox);

  //show the coords display
  xShow(dBox.displayContainer);


  if(dBox.drag) { //the mouse is down
    dBox.x2 = e.offsetX;
    dBox.y2 = e.offsetY;
    if(dBox.currentTool == 'zoom_out') { // only one click events
      jg.clear();
      dBox.x1 = dBox.x2;
      dBox.y1 = dBox.y2;
    }
    dBox.paint();
  }
  else if ((dBox.currentTool == 'measure' || dBox.currentTool == 'surface') && dBox.isActive == true) {
      dBox.x2 = e.offsetX;
      dBox.y2 = e.offsetY;
      dBox.lastLinePaint(); // the last line is drawn while moving
  }
    // display the coordinates
    dBox.displayCoords.innerHTML = Dhtml_coord_msg + Math.round((e.offsetX * pixelx) + boxx)  +" / "+ Math.round(((mapy - e.offsetY) * pixely) + boxy);

  /*
  if (myform.pancoords_x && myform.pancoords_y) {
       myform.pancoords_x.value = Math.round((e.offsetX * pixelx) + boxx);
       myform.pancoords_y.value = Math.round(((mapy - e.offsetY) * pixely) + boxy);
  } else {
       window.status = Math.round((e.offsetX * pixelx) + boxx)  +"/"+ Math.round(((mapy - e.offsetY) * pixely) + boxy);
  }
  */
}

function dBox_mouseup(evt) {
  var e = new xEvent(evt);
  var name = e.target.id;
  var dBox = name.substring(0,name.length - 3); // the name of the layer without the 'DIV'
  dBox = eval(dBox);

  if (dBox.rightclic == true) {
    jg2.clear();
  } else {

    dBox.drag = false; //the mouse is now up

    dBox.x2 = e.offsetX;
    dBox.y2 = e.offsetY;

    //the box is too small
    if(((Math.abs(dBox.x1-dBox.x2) <= dBox.jitter) || (Math.abs(dBox.y1-dBox.y2) <= dBox.jitter)) && dBox.currentTool == 'zoom_in') {
      dBox.x2 = dBox.x1 = Math.abs(dBox.x1-dBox.x2) /2 + Math.min(dBox.x1, dBox.x2); //zoom to center of the box
      dBox.y2 = dBox.y1 = Math.abs(dBox.y1-dBox.y2) /2 + Math.min(dBox.y1, dBox.y2);;
    }


    // submit the form with the values

    if (dBox.currentTool == 'zoom_in' || dBox.currentTool == 'zoom_out' || dBox.currentTool == 'query') {
      dBox.removeEventsWait();
      myform.INPUT_TYPE.value = "auto_rect";
      myform.INPUT_COORD.value = dBox.x1+","+dBox.y1+","+dBox.x2+","+dBox.y2
      myform.submit();
    }
    else if (dBox.currentTool == 'pan') {
      myform.INPUT_TYPE.value = "auto_point";
      // pan or simple pan click
      if (dBox.x2 == dBox.x1 && dBox.y2 == dBox.y1) { //simple click
        var x = dBox.x2;
        var y = dBox.y2
        myform.INPUT_COORD.value = x+","+y+";"+x+","+y;
      } else {// pan
        //new center coordinates
        var x = dBox.width/2 - (dBox.x2 - dBox.x1);
        var y = dBox.height/2 - (dBox.y2 - dBox.y1);
        myform.INPUT_COORD.value = x+","+y+";"+x+","+y;
      }
      myform.submit();
    }
    dBox.paint();
  }
}

function dBox_mouseout(evt) {
  var e = new xEvent(evt);
var name = e.target.id;
  var dBox = name.substring(0,name.length - 3); // the name of the layer without the 'DIV'
  dBox = eval(dBox);

  xHide(dBox.displayContainer);
  jg2.clear();
}

function dBox_dblclick(evt) {
  var e = new xEvent(evt);
  var name = e.target.id;
  var dBox = name.substring(0,name.length - 3); // the name of the layer without the 'DIV'
  dBox = eval(dBox);

  dBox.isActive = false;

  jg2.clear();
  if (dBox.currentTool == 'surface') {
    dBox.paint(); // close the polygon
  }
}

function dBox_keydown(evt) { // ne peut etre utilisé que pour la dBox "main"
  evt = (evt) ? evt : ((event) ? event : null);
  dBox = mainDHTML;
  if (evt.keyCode == '27' && (dBox.currentTool == 'measure' || dBox.currentTool == 'surface')) {
    dBox.keyEscape = true;
    dBox.isActive = false;
    jg2.clear();
    dBox.paint();
  }
}

function dBox_paint() { // draws alternatively boxes, lines, polylines, crosses, or pan the map
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

  else if (this.currentTool == 'measure') {
    if (!this.keyEscape) { // Escape key is pressed
      ++this.cnv_clicks;
      this.draw_x[this.cnv_clicks] = this.x2;
      this.draw_y[this.cnv_clicks] = this.y2;
    }

    if (xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) { // IE/Mac specificity
      this.Xpoints[this.cnv_clicks - 1] = this.draw_x[this.cnv_clicks];
      this.Ypoints[this.cnv_clicks - 1] = this.draw_y[this.cnv_clicks];
      jg.clear();
      jg.drawPolylinePts(this.Xpoints,this.Ypoints, this.d2pts);
    }
    else {
        jg.drawLinePts(this.draw_x[this.cnv_clicks],this.draw_y[this.cnv_clicks],this.draw_x[this.cnv_clicks - 1],this.draw_y[this.cnv_clicks - 1],this.d2pts);
    }
    if (this.cnv_clicks > 1 && !this.keyEscape) {
      // distance calculation
      this.dist_x = (this.draw_x[this.cnv_clicks] - this.draw_x[this.cnv_clicks - 1]) * Dhtml_pixel_size;
      this.dist_y = (this.draw_y[this.cnv_clicks] - this.draw_y[this.cnv_clicks - 1]) * Dhtml_pixel_size;
      this.measure += Math.sqrt(this.dist_x * this.dist_x + this.dist_y * this.dist_y);
      jg.paint();
    }

    if (Dhtml_dist_unit == ' m.') this.measure = Math.round(this.measure);
    else if (Dhtml_dist_unit == ' km.') this.measure = Math.round(this.measure*100)/100;
    this.displayMeasure.innerHTML = Dhtml_dist_msg + this.measure.toString() + Dhtml_dist_unit;
//    myform.Measure.value = Dhtml_dist_msg + this.measure.toString() + Dhtml_dist_unit;

    //window.status = Dhtml_dist_msg+ this.measure.toString()+ Dhtml_dist_unit;
    //myform.Measure.value = this.measure.toString();
  }
  else if (this.currentTool == 'surface') {
    if (!this.keyEscape) { // Escape key is pressed
      ++this.cnv_clicks;
      this.draw_x[this.cnv_clicks] = this.x2;
      this.draw_y[this.cnv_clicks] = this.y2;
    }

    if (xUA.indexOf('mac_')!=-1 && xUA.indexOf('msie')!=-1) { // IE/Mac specificity
      this.Xpoints[this.cnv_clicks - 1] = this.draw_x[this.cnv_clicks];
      this.Ypoints[this.cnv_clicks - 1] = this.draw_y[this.cnv_clicks];
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
    this.Xpoints[this.cnv_clicks - 1] = this.draw_x[this.cnv_clicks];
    this.Ypoints[this.cnv_clicks - 1] = this.draw_y[this.cnv_clicks];

    if (this.cnv_clicks > 1  && !this.keyEscape) {
      //surface calculation
      var i = 0;
      this.measure = 0;
      while (i < this.cnv_clicks - 1) {
        this.measure += this.Xpoints[i] * this.Ypoints[i+1] - this.Xpoints[i+1] * this.Ypoints[i];
        ++i;
      }

      this.measure += this.Xpoints[this.cnv_clicks -1] * this.Ypoints[0] - this.Xpoints[0] * this.Ypoints[this.cnv_clicks -1];
      var pix_surf = Dhtml_pixel_size * Dhtml_pixel_size;
      this.measure = Math.abs(this.measure.toString()) / 2 * pix_surf;

    }
    if (Dhtml_surf_unit == ' m².') this.measure = Math.round(this.measure);
    else if (Dhtml_surf_unit == ' km².') this.measure = Math.round(this.measure*10000)/10000;
    this.displayMeasure.innerHTML = Dhtml_surf_msg+ this.measure +Dhtml_surf_unit;
    jg.paint();
  }
}


function dBox_lastLinePaint() {

  var x2 = this.x2;
  var y2 = this.y2;
  var x = this.draw_x[this.cnv_clicks];
  var y = this.draw_y[this.cnv_clicks];
  var x0 = this.draw_x[1];
  var y0 = this.draw_y[1];

  if (xIE || xUA.indexOf('mac')!=-1) { //use the drawing API
    jg2.clear();
    jg2.drawLinePts(x2,y2,x,y,this.d2pts); //draw the last vertex
    if (this.currentTool == "surface") {
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
    if (this.currentTool == "surface") {
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



new dBox(0);

dBox.prototype.initialize = dBox_initialize; // create instance method
dBox.prototype.domousedown = dBox_mousedown;
dBox.prototype.domousemove = dBox_mousemove;
dBox.prototype.domouseup = dBox_mouseup;
dBox.prototype.domouseout = dBox_mouseout;
dBox.prototype.dodblclick = dBox_dblclick;
dBox.prototype.dokeydown = dBox_keydown;
dBox.prototype.paint = dBox_paint;
dBox.prototype.lastLinePaint = dBox_lastLinePaint; // draw the last straight vertex for the measure tool
dBox.prototype.changetool = dBox_changetool;
dBox.prototype.removeEventsWait = dBox_removeEventsWait;

function changeTool(dBox,tool) {
  eval(dBox+'.changetool(tool)');
}

function dBox_removeEventsWait() {
   xRemoveEventListener(this.target, 'mousemove', this.domousemove);
   if (this.target.style) this.target.style.cursor = "wait";
}
