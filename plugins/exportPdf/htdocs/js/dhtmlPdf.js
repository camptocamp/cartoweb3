/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function hidePdfFeature(event) {
  mainmap.removePdfFeature('map');
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
  var aLayer = xGetElementById('map_drawing');
  aLayer.innerHTML = '';
/*
  var aDisplay = this.getDisplay(aDisplayName);

  var feature = this.getPdfFeature(aDisplay);
  if (aDisplay.getDisplayFeature(feature) != null) {
    aDisplay.currentLayer.removeChild(aDisplay.getDisplayFeature(feature));
  }
*/
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

Map.prototype.pdfrotate = function(aDisplay) {
  this.resetMapEventHandlers();
    
  this.getDisplay(aDisplay).mouseAction = new RotateFeatureTool(this.getDisplay(aDisplay));

  this.setCurrentLayer('drawing');
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