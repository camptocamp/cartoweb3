/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function hidePdfFeature(event) {
  mainmap.removePdfFeature(mainmapid);
}
function addToolPdfListeners() { 

  for (var i = 0; i < cw3_tools.length; i++ )    {
    if (cw3_tools[i] != 'pdfrotate') {
      elt = xGetElementById(cw3_tools[i]);
      xAddEventListener(elt, 'click', hidePdfFeature, false);
      elt = xGetElementById(cw3_tools[i] + "_icon");
      xAddEventListener(elt, 'click', hidePdfFeature, false);
    }
  }
}
EventManager.Add(window, 'load', addToolPdfListeners, false);



/***** PDF tools ****/

Map.prototype.form2PdfFeature = function(feature, aDisplay) {
  
  var marginx = myform.pdfMarginX.value;
  var marginy = myform.pdfMarginY.value;
  
  var paperx = xGetElementById('pdf' + myform.pdfFormat.value + 'x').value;
  var papery = xGetElementById('pdf' + myform.pdfFormat.value + 'y').value;
  
  if (document.getElementById('lsp').checked == true) {
    var papertmp = paperx;
    paperx = papery;
    papery = papertmp;
  }
  
  var scale = myform.pdfScale.value;
  var sizex = (paperx - marginx) / 1000 * scale / 2;
  var sizey = (papery - marginy) / 1000 * scale / 2;
  
  feature.vertices = Array();
  var vertex;
  vertex = new Vertex(-sizex, -sizey);
  vertex.index = 0;
  feature.vertices.push(vertex);
  vertex = new Vertex(-sizex, sizey);
  vertex.index = 1;
  feature.vertices.push(vertex);
  vertex = new Vertex(sizex, sizey);
  vertex.index = 2;
  feature.vertices.push(vertex);
  vertex = new Vertex(sizex, -sizey);
  vertex.index = 3;
  feature.vertices.push(vertex);
  vertex = new Vertex(-sizex, -sizey);
  vertex.index = 4;
  feature.vertices.push(vertex);
  
  aDisplay.angle = parseFloat(myform.pdfMapAngle.value);
  if (isNaN(aDisplay.angle)) {
    aDisplay.angle = 0;
  }
  var cx = parseFloat(myform.pdfMapCenterX.value);  
  var cy = parseFloat(myform.pdfMapCenterY.value);
  if (isNaN(cx) || isNaN(cy)) {
    cx = (this.extent.xmax + this.extent.xmin) / 2;
    cy = (this.extent.ymax + this.extent.ymin) / 2;
  }
  for (var i = 0; i < feature.vertices.length; i++) {
    feature.vertices[i].x = feature.vertices[i].x + cx;
    feature.vertices[i].y = feature.vertices[i].y + cy;
  }  
  feature.rotate(aDisplay.angle);
}

Map.prototype.getPdfFeature = function(aDisplay) {
  var feature = null;
  for (var i=0; i < this.currentLayer.features.length; i++) { 
    if (this.currentLayer.features[i].id == "pdf_overview") {
      feature = this.currentLayer.features[i];
    }
  }
  
  if (feature == null) {
    var feature = new Feature("POLYGON((0 0 1 1))");
    this.form2PdfFeature(feature, aDisplay);

    feature.id = "pdf_overview";
    this.currentLayer.addFeature(feature);
  }  
  return feature;
}

Map.prototype.hidePdfFeature = function(aDisplayName) {

/*
  var aLayer = xGetElementById('map_drawing');
  aLayer.innerHTML = '';
*/
  var aDisplay = this.getDisplay(aDisplayName);

  var feature = this.getPdfFeature(aDisplay);
  if (aDisplay.getDisplayFeature(feature) != null) {
    var pdfLayer = xGetElementById(aDisplay.id + "_drawing"); // see Map.prototype.pdfrotate
    var removedNode = pdfLayer.removeChild(aDisplay.getDisplayFeature(feature));
    delete removedNode;
  }
}

Map.prototype.showPdfFeature = function(aDisplayName) {
  var aDisplay = this.getDisplay(aDisplayName);

  var feature = this.getPdfFeature(aDisplay);
  if (aDisplay.getDisplayFeature(feature) == null) {
    aDisplay.drawFeature(aDisplay.currentLayer, feature, _OFF, false);     
  }
}

Map.prototype.updatePdfFeature = function(aDisplayName) {
  this.hidePdfFeature(aDisplayName);
  var aDisplay = this.getDisplay(aDisplayName);
  var feature = this.getPdfFeature(aDisplay);
  this.form2PdfFeature(feature, aDisplay);
  this.showPdfFeature(aDisplayName);
}

Map.prototype.removePdfFeature = function(aDisplayName) {
  this.hidePdfFeature(aDisplayName);
  var aDisplay = this.getDisplay(aDisplayName);
  var feature = this.getPdfFeature(aDisplay);
  this.currentLayer.features.pop(feature);  
}

/**
* remove feature and reset angle and position, redisplay new feature centered with angle = 0
*/
Map.prototype.resetPdfFeature = function(aDisplayName) {
  this.removePdfFeature(aDisplayName);
  // reset angle and center
  myform.pdfMapAngle.value = null;
  myform.pdfMapCenterX.value = null;
  myform.pdfMapCenterY.value = null;
  updatePdfAngleInterface(0);
  this.showPdfFeature(aDisplayName);
}

Map.prototype.pdfrotate = function(aDisplay) {
  this.resetMapEventHandlers();
    
  this.setCurrentLayer('drawing');
  this.getDisplay(aDisplay).currentLayer = xGetElementById(this.getDisplay(aDisplay).id + "_" + this.getDisplay(aDisplay)._map.currentLayer.id);
  this.getDisplay(aDisplay).mouseAction = new RotateFeatureTool(this.getDisplay(aDisplay));

  // reset current display dmpts (solve bug of feature going crazy after changing scale (bug id 1685))
  delete this.getDisplay(aDisplay).dmpts;
  
  this.showPdfFeature(aDisplay);

  this.onFeatureSelected = function(aFeature) {
    // hide pdf_overview
    emptyForm();    
  }
  this.onFeatureChange = function(aFeature) {
    // send center coords (Feature.getCentroid), angle to the form
    var center = aFeature.getCentroid();
    myform.pdfMapCenterX.value = center.vertices[0].x;
    myform.pdfMapCenterY.value = center.vertices[0].y;
    myform.pdfMapAngle.value = this.getDisplay(aDisplay).angle;
  }
}

/*
 * Rotate pdf feature by x degree
 * @param float angledegree
 * @param bool absolute, ignore feature current angle
 */
Map.prototype.rotatePdfFeature = function(angledegree, absolute) {

// check if we are inside the range 0/360 ° 
  if ( isNaN(angledegree) || Math.abs(parseFloat(angledegree)) > 360 || Math.abs(parseFloat(angledegree)) < 0 ){
	  if ( isNaN(angledegree) ){
		  alert('Numerics value between -359 to 359 only !');
	  }
	  angledegree = 0.0;
  }
  // convert degree to radian
  var anglerad = angledegree * Math.PI / 180;

  // update current angle
  if (!absolute) {
    var currentangle = this.getDisplay(mainmapid).angle;
    var nangle = currentangle + anglerad;
  } else {
    var nangle = anglerad;
  }

  // update stored angle
  myform.pdfMapAngle.value = nangle;

  // update interface
  updatePdfAngleInterface(nangle);

  // update feature
  this.updatePdfFeature(mainmapid);
}

/**
 * update pdf angle interface
 * @param float angle (rad)
 */
function updatePdfAngleInterface(angle) {
  var elm = xGetElementById('pdfrotate_angledegree');
  if (elm){
    angledegree = angle * 180 / Math.PI;
    if (angledegree < 0) {
      angledegree = 360 + angledegree;
    }
    elm.innerHTML = Math.round(angledegree);
  }
}

/*
 * Recenter on pdf feature
 */
Map.prototype.pdfRecenter = function() {
    Logger.trace('pdfRecenter');
  // get current pdf caneva largest diagonal dimension
  var aDisplay = this.getDisplay(mainmapid);
  var feature = this.getPdfFeature(aDisplay);
  var center = feature.getCentroid();
  var cx = center.vertices[0].x;
  var cy = center.vertices[0].y;
  var cxl = Array();
  var cyl = Array();

  for (var i = 0; i < feature.vertices.length; i++){
    cxl[i] = feature.vertices[i].x;
    cyl[i] = feature.vertices[i].y;
  }
  cxl.sort();
  cyl.sort();

  cxmin = cxl[0];
  cxmax = cxl[cxl.length-1];
  cymin = cyl[0];
  cymax = cyl[cyl.length-1];

  var cxm = feature.vertices[0].x - cx;
  var cym = feature.vertices[0].y - cy;
  var dl = Math.sqrt((cxm * cxm) + (cym * cym));

  // add small buffer around enclosing bbox
  dl = dl * 1.5;

  // generate a recentering bbox
  var nbbox_minx = cx - dl;
  var nbbox_maxx = cx + dl;
  var nbbox_miny = cy - dl;
  var nbbox_maxy = cy + dl;
  var nbbox = nbbox_minx+','+nbbox_miny+','+nbbox_maxx+','+nbbox_maxy;
  
  // set in dom the recenter_bbox input+value
  var rbbox = document.carto_form['recenter_bbox'];
  if (typeof(rbbox) == 'undefined') {
    // create an input
    var rbbox = document.createElement("input");
    rbbox.setAttribute("type", "hidden");
    rbbox.setAttribute("name", "recenter_bbox");
    rbbox.setAttribute("id", "recenter_bbox");
    rbbox.setAttribute("value", nbbox);
    document.carto_form.appendChild(rbbox);
  } else {
    rbbox.value = nbbox;
  }
  // call recentering
  CartoWeb.trigger('Location.Recenter', 'FormItemSelected()');

}

/*
 * Rotate pdf feature vertices by angle (rad)
 * @param float angle (rad)
 */
Feature.prototype.rotate = function(angle) {
  if (angle != null && angle != 0) {
            
    // Center
    var center = this.getCentroid();
    var cx = center.vertices[0].x;
    var cy = center.vertices[0].y;

    for (var i = 0; i < this.vertices.length; i++) {
      var vertx = this.vertices[i].x;
      var verty = this.vertices[i].y;
      
      // move to origin
      var x = vertx - cx;
      var y = verty - cy;
      
      // rotate
      var xp = x * Math.cos(angle) - y * Math.sin(angle);
      var yp = x * Math.sin(angle) + y * Math.cos(angle);
      
      var newx = this.vertices[i].x - x + xp;
      var newy = this.vertices[i].y - y + yp;
      this.vertices[i].x = newx;
      this.vertices[i].y = newy;
    }
  }
}


/*************** Display Tools **********************/

/**
 * Creates a rotate feature tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function RotateFeatureTool(aDisplay) {
  xHide(aDisplay.eventPad);
  aDisplay.docObj.style.cursor = "move";
  xDisableDrag(aDisplay.rootDisplayLayer);
};
RotateFeatureTool.prototype.onMouseOver = function(aDisplay, ex, ey) {
  // over map
  if (umo.className.indexOf(layerCN) == -1) {
    var cn = umo.className;
    if (cn.indexOf(vertexCN) != -1) { // on a vertex
        var dShape = umo;
        aDisplay.mode = 'rotate';
    } else {
        var dShape = xParent(xParent(umo, true), true); // clicked on a line of a polyline or polygon
        xEnableDrag(dShape, aDisplay.dragStart, null, aDisplay.dragEnd);
        aDisplay.mode = 'drag';
    }
  }
};
RotateFeatureTool.prototype.onMouseOut = function(aDisplay, ex, ey) {
  if (!umo) {
    return;
  }
  // over map
  if (umo.className.indexOf(layerCN) == -1) {
    var cn = umo.className;
    if (cn.indexOf(vertexCN) != -1) { // clicked on a vertex
        var dShape = umo;
    } else {
        // clicked on a line of a polyline or polygon
        var dShape = xParent(xParent(umo, true), true);
        xDisableDrag(dShape);
    }
  }
};
RotateFeatureTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  if (aDisplay.mode == 'drag') {
      return;
  }
  if (!umo) return;
  // clicked on map

  if (umo.className.indexOf(layerCN) != -1) {
    aDisplay._map.selectedFeature = undefined;
    if (aDisplay._map.onUnselectFeatures) aDisplay._map.onUnselectFeatures();
    changeStatus(aDisplay.currentLayer, _OFF, true, true);
  } else {
    var cn = umo.className;
    var dShape = xParent(xParent(umo, true), true);
//    if (cn.indexOf(_SEL) == -1) changeStatus(aDisplay.currentLayer, _OFF, true, true);
//    changeStatus(dShape, _SEL, true, true);
  
    var feature = aDisplay.getMapFeature(dShape);
    if (feature.operation != 'insert') feature.operation = 'update';
    aDisplay._map.updateFeaturesCount();
    aDisplay._map.selectedFeature = feature;
    aDisplay.prevx = ex;
    aDisplay.prevy = ey;
    aDisplay.dShape = dShape;
    if (aDisplay._map.onFeatureSelected)
      aDisplay._map.onFeatureSelected(feature);
      
    // moving points
    if (!aDisplay.dmpts)
      aDisplay.dmpts = aDisplay.drawLine(aDisplay.currentLayer, 0, 0, 0, 0); // container
  }
  
  // TODO select a feature if no one selected
    
    // TODO if feature selected, get the clicked point and
    // rotate on mouse move vertically or horizontally
};
RotateFeatureTool.prototype.onMouseUp = function(aDisplay, ex, ey) {
  if (aDisplay.mode == 'drag') {
      return;
  }
  var feature = aDisplay.getMapFeature(aDisplay.dShape);
  aDisplay.currentLayer.removeChild(aDisplay.dShape);
  aDisplay.drawFeature(aDisplay.currentLayer, feature, _OFF, false);
  
  aDisplay.prevx = undefined;
  aDisplay.prevy = undefined;

  aDisplay.dmpts.innerHTML = "";
  
  if (aDisplay._map.onFeatureChange) aDisplay._map.onFeatureChange(feature);
  
  // update angle displayed in interface
  updatePdfAngleInterface(aDisplay.angle);
  
};
RotateFeatureTool.prototype.onMouseMove = function(aDisplay, ex, ey) {
  if (aDisplay.mode == 'drag') {
      return;
  }
  if (typeof aDisplay.prevx != "undefined" &&
      typeof aDisplay.prevy != "undefined") {
    
    aDisplay.dmpts.innerHTML = "";
    var xmin = aDisplay._map.extent.xmin;
    var ymin = aDisplay._map.extent.ymin;
    var xmax = aDisplay._map.extent.xmax;
    var ymax = aDisplay._map.extent.ymax;
    
    var feature = aDisplay.getMapFeature(aDisplay.dShape);
  
    var center = feature.getCentroid();
    var cx = geo2Pix(center.vertices[0].x, xmin, xmax, 0, aDisplay._width);
    var cy = geo2Pix(center.vertices[0].y, ymax, ymin, 0, aDisplay._height);
  
    // move to origin
    var x1 = aDisplay.prevx - cx;
    var y1 = aDisplay.prevy - cy;
    var x2 = ex - cx;
    var y2 = ey - cy;

    // calculate angle between 2 points  
    var angle1 = getAngle(x1, y1);
    var angle2 = getAngle(x2, y2);
    var angle = angle2 - angle1;
    
    feature.rotate(angle);
    for (var i = 0; i < feature.vertices.length; i++) {
      aDisplay.drawPoint(aDisplay.dmpts,
        geo2Pix(feature.vertices[i].x, xmin, xmax, 0, aDisplay._width),
        geo2Pix(feature.vertices[i].y, ymax, ymin, 0, aDisplay._height),
        null, null, _OFF);
    }
    
    aDisplay.angle = aDisplay.angle + angle;    
    aDisplay.prevx = ex;
    aDisplay.prevy = ey;
  }
  
  // update angle displayed in interface
  updatePdfAngleInterface(aDisplay.angle);
}

RotateFeatureTool.prototype.onDrag = function(elt, x, y) {

};
RotateFeatureTool.prototype.onDragEnd = function(elt, x, y) {
  var aDisplay = elt._display;
  if (aDisplay.mode == 'rotate') {
      return;
  }
  
  var cn = elt.className;
  var currentLayer = aDisplay._map.currentLayer;
  var xmin = aDisplay._map.extent.xmin;
  var xmax = aDisplay._map.extent.xmax;
  var ymin = aDisplay._map.extent.ymin;
  var ymax = aDisplay._map.extent.ymax;
  
//  if (cn.indexOf(vertexCN) != -1) { // clicked on a vertex
//  } else {// complete feature moved
    // get the corresponding map feature
    for (i = 0; i < currentLayer.features.length;i++) {
      if (aDisplay.id + "_" + currentLayer.features[i].id == elt.id) {
        var feature = currentLayer.features[i];
        if (feature.operation != 'insert') feature.operation = 'update';
        continue;
      }
    }
        
    // change all the coordinates 
    if (!feature) {
      alert ("drag on feature which not defined");
      return;
    }
    for (i = 0; i< feature.vertices.length;i++) {
      feature.vertices[i].x += (x - elt.startX) * (xmax - xmin) / aDisplay._width;
      feature.vertices[i].y += (y - elt.startY) * (ymin - ymax) / aDisplay._height;
    }
//  }
  aDisplay.dragon = false;
  if (aDisplay._map.onFeatureChange) aDisplay._map.onFeatureChange(feature);
};

/************* Generic fonction ***********/

function getAngle(x, y) {
  var angle;
  if (x != 0)
    angle = Math.atan(Math.abs(y / x));
  else
    angle = Math.PI / 2;
    
  if (x < 0 && y < 0)
    angle = Math.PI - angle; //Math.PI + angle;
  else if (x < 0)
    angle = Math.PI + angle;//Math.PI - angle;
  else if (y < 0)
    angle = angle; //Math.PI / 2 + angle;//2 * Math.PI - angle;
  else
    angle = 2 * Math.PI - angle; //angle;
    
  return angle;
}