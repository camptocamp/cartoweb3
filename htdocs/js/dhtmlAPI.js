/* Copyright 2005 Camptocamp SA.
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

//----------------------------------------------------------------------------//

/**
 * CartoWeb object for AJAX actions triggering
 */
CartoWeb = {
    /**
     * Triggers the given ajaxAction if AJAX is enabled, else executes
     * the given nonAjaxInstruction.
     * @param string AJAX Action to trigger
     * @param string Non AJAX instruction(s) to execute if not in AJAX mode
     * @param object Object containing custom information for action processing
     */
    trigger: function (ajaxAction, nonAjaxInstruction, ajaxArgObject) {
        ajaxArgObject = typeof(ajaxArgObject) == 'object' ? ajaxArgObject : {};
        nonAjaxInstruction = nonAjaxInstruction || '';
        if (this.isAjaxMode()) {
            AjaxHandler.doAction(ajaxAction, ajaxArgObject);
            return false;
        } else {
            eval(nonAjaxInstruction);
        }
    },

    disableAjax: function() {
        if (typeof(AjaxHandler) != 'undefined') {
            AjaxHandler.processActions = false;
        }
    },
    enableAjax: function() {
        if (typeof(AjaxHandler) != 'undefined') {
            AjaxHandler.processActions = true;
        }
    },

    /**
     * Returns wether AJAX mode is enabled or not
     * @return bool True if AJAX mode is enabled, false otherwise.
     */
    isAjaxMode: function() {
        return typeof(AjaxHandler) != 'undefined' && AjaxHandler.processActions;
    }
}

//----------------------------------------------------------------------------//

 /**
  * dhtmlAPI Core
  *
  * Tested browsers
  * - Macintosh
  *   - SAFARI
  *   - Firefox: 1.7.3
  *   - Netscape: 7.2
  * - Windows
  *   - Netscape: 7.2
  *   - Mozilla:1.7.3
  *   - Firefox: 1.0.2
  *   - IE: 5.0.1, 5.5, 6.0.23, 6.0.28, 6.0.29
  */

// Drawing objects indexes
var pi = 0;    // point id
var li = 0;    // line id
var pfi = 0; // polygon fill

var undefined;

/**
 * Creates a map object
 * @param className class name of the divs to use for displays
 * @return mapObj object created
 */
function Map(className) {
  if (!className) className = "map"; // default value if not set

  this.id = className;

  // layers array
  this.layers = new Array();

  // displays array
  this.displays = new Array();

  this.currentEditFeature = null;

  // get all elements with className = "map", create display object with each
  this.displays = xGetElementsByClassName(className, null, "div", null);
  for (var i = 0; i<this.displays.length;i++) {
    this.displays[i] = new Display(this.displays[i]);
    this.displays[i]._map = this; // reference to the parent object (map)
  }
};

/**
 * Sets a map extent
 * @param xmin
 * @param ymin
 * @param xmax
 * @param ymax
 */
Map.prototype.setExtent = function(xmin, ymin, xmax, ymax) {
  this.extent = new Rectangle2D(xmin, ymin, xmax, ymax);
};

/**
 * Get a map display (to call displays methods or properties)
 * @param name name of a display
 * @return the display
 */
Map.prototype.getDisplay = function(name) {
  for (var i = 0; i<this.displays.length; i++){
    if (this.displays[i].name == name)
      return this.displays[i];
  }
};

/**
 * Adds a new Lyr (layer object) to current map display,
 * @param obj object to add a layer to
 * @return the layer object created, including a div element
 */
Map.prototype.addLayer = function(obj, aLayer) {

  if (typeof obj.layers[aLayer] != 'undefined')
    return aLayer;

  //var aLayer= new layerObj(name);
  aLayer._map = obj; //reference to the parent

  // add the layer to the layers array of the parent element (map or layer)
  obj.layers.push(aLayer);

  for (var i = 0; i<this.displays.length; i++) {
    // add a div layer to the displays
    var dLayer = this.displays[i].addLayer(obj, aLayer.name);
    dLayer.name = name;
    // draw the features
    for (var j = 0; j < aLayer.features.length; j++) {
      this.displays[i].drawFeature(dLayer, aLayer.features[j]);
    }
  }
  return aLayer;
};

/**
 * Updates the map features count
 */
Map.prototype.updateFeaturesCount = function() {
  this.featuresNum = 0;
  this.insertedNum = 0;
  this.updatedNum = 0;
  this.deletedNum = 0;
  for (var j = 0;j < this.layers.length; j++) {
    for (var i = 0;i < this.layers[j].features.length; i++) {
      if (this.layers[j].features[i].getObjectClass() == 'Raster') continue;
      this.featuresNum++;
      var feature = this.layers[j].features[i];
      switch (feature.operation) {
      case "insert":
        this.insertedNum++;
        break;
      case "update":
        this.updatedNum++;
        break;
      case "delete":
        this.deletedNum++;
        break;
      }
    }
  }
};

/**
* Sets the given aLayerId as the current layer, creating it if needed
* @param aLayerId Id of the layer to be set as the current layer
* @return Div object modified in DOM
*/
Map.prototype.setCurrentLayer = function(aLayerId) {
  layer = null;
  for (i=0 ; i < this.layers.length ; i++) {
    if (this.layers[i].id == aLayerId) {
      layer = this.layers[i];
      break;
    }
  }

  if (layer == null) {
    layer = new Layer(aLayerId);
    this.addLayer(this, layer);
  }
  this.currentLayer = layer;
};

/**
 * Creates a layer object
 * @param name layer tag name and id
 */
function Layer(name) {
  this.id = name;
  this.name = name;
  // layers array
  this.layers = new Array();
  // features array
  this.features = new Array();

  this.addFeature = function(aFeature) {
    this.features.push(aFeature);
  };
  // replace an existing feature from the features list
  this.updateFeature = function(aFeature, feature_id) {
    if (!feature_id || feature_id == ''){
        // FIXME this will need to be translated somehow once a system for js internationalisation is ready
        alert('missing id when trying to remove a fature. The id is mandatory. The feature was NOT updated.');
        return false;
    }
    for (var i = 0; i < this.features.length; i++) {
      if (this.features[i].id && this.features[i].id == feature_id) {
        this.features[i] = aFeature;
      }
    }
  };
  // remove a feature from the features list
  this.delFeature = function(aFeature, feature_id) {
    if (!feature_id || feature_id == ''){
        // FIXME this will need to be translated somehow once a system for js internationalisation is ready
        alert('missing id when trying to remove a fature. The id is mandatory. The feature was NOT deleted.');
        return false;
    }
    var tmpArray = new Array();
    for (var i = 0; i < this.features.length; i++) {
      if (this.features[i].id && this.features[i].id == feature_id) {
        continue;
      }
      tmpArray.push(this.features[i])
    }
    this.features = tmpArray;
  };
};

/**
 * Creates a display for a mapObj object
 * @param obj object to use for the display
 * @return display object created
 */
function Display(docObj) {
  // TODO change cursor to wait for some operation
  this._posx = xPageX(docObj);
  this._posy = xPageY(docObj);

  this._width = xWidth(docObj);
  this._height = xHeight(docObj);

  this.id = this.name = docObj.id;

  docObj._display = this;

  docObj.innerHTML = "";

  this.docObj = docObj;

  this.features = new Array();

  // draw a root layer (container)
  this.rootDisplayLayer = this.addLayer(docObj, "rootLayer", layerCN);
  xMoveTo(this.rootDisplayLayer, this._posx, this._posy);

  // draw a specific event pad layer needed for some tools
  this.eventPad = this.addLayer(this.rootDisplayLayer, "eventPad", layerCN);
  // hack for IE windows : allow to click and move over an image
  if (typeof(this.eventPad.style.filter) != 'undefined'
      && navigator.appVersion.toLowerCase().indexOf('windows') != -1) {
    this.eventPad.style.backgroundColor = "blue";
    this.eventPad.style.filter = "alpha(opacity=0)";
  }
  xResizeTo(this.eventPad, this._width, this._height);
  xHide(this.eventPad);
  this.eventPad.style.zIndex = 1;

  // ***************************
  // map displays event handlers
  EventManager.Add(this.rootDisplayLayer, 'mouseover', display_onmouseover, false);
  EventManager.Add(this.rootDisplayLayer, 'mousedown', display_onmousedown, false);
  EventManager.Add(this.rootDisplayLayer, 'mousemove', display_onmousemove, false);
  EventManager.Add(this.rootDisplayLayer, 'mouseup', display_onmouseup, false);
  EventManager.Add(this.rootDisplayLayer, 'mouseout', display_onmouseout, false);
  EventManager.Add(document, 'keydown', display_onkeydown, false);

  return this;
};

/**
 * Set the current tool for a display
 */
Display.prototype.setTool = function(tool) {
  this.mouseAction = tool;
  // current drawing layer
  this.currentLayer = xGetElementById(this.id + "_" + this._map.currentLayer.id);

  this.dShape = undefined;

  switch(tool) {
  case "pan":
    this.mouseAction = new PanTool(this);
    break;
  case "draw.point":
    this.mouseAction = new DrawPointTool(this);
    break;
  case "draw.line":
    this.mouseAction = new DrawLineTool(this);
    break;
  case "draw.box":
    this.mouseAction = new DrawBoxTool(this);
    break;
  case "draw.poly":
    this.mouseAction = new DrawPolygonTool(this);
    break;
  case "draw.circle":
    this.mouseAction = new DrawCircleTool(this);
    break;
  case "sel.point":
    this.mouseAction = new SelPointTool(this);
    break;
  case "sel.box":
    this.mouseAction = new SelBoxTool(this);
    break;
  case "move":
    this.mouseAction = new MoveTool(this);
    break;
  case "delete.vertex":
    this.mouseAction = new DeleteVertexTool(this);
    break;
  case "add.vertex":
    this.mouseAction = new AddVertexTool(this);
    break;
  case "delete.feature":
    this.mouseAction = new DeleteFeatureTool(this);
    break;
  default:
    this.docObj.style.cursor = "auto";
    break;
  }
};

/**
 * Creates a pan tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function PanTool(aDisplay) {
  aDisplay.docObj.style.cursor = "move";
  xHide(aDisplay.eventPad);
  xEnableDrag(aDisplay.rootDisplayLayer, aDisplay.dragStart, aDisplay.drag, aDisplay.dragEnd);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
PanTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  var layer = xGetElementById(aDisplay.currentLayer.id);
  xHide(layer);
};
PanTool.prototype.onDrag = function(elt,x,y) {
  var aDisplay = elt._display;
  // move and clip the root layer (and its children)
  xMoveTo(elt, xLeft(elt) + x, xTop(elt) + y);
  xClip(elt, aDisplay._posy - xTop(elt), aDisplay._posx - xLeft(elt) + aDisplay._width,
  aDisplay._posy - xTop(elt) + aDisplay._height, aDisplay._posx - xLeft(elt));

  xMoveTo(aDisplay.eventPad, aDisplay._posx - xLeft(elt), aDisplay._posy - xTop(elt));
};
PanTool.prototype.onDragEnd = function(elt,x,y) {
  var aDisplay = elt._display;
  var dx = x - elt.startX;
  var dy = y - elt.startY;
  // get the pixel coordinates of the new center
  if (dx == 0 && dy == 0) { // TODO use jitter
    var pixCenterX = x - xLeft(elt);
    var pixCenterY = y - xTop(elt);
  } else {
    var pixCenterX = (aDisplay._width / 2) - dx;
    var pixCenterY = (aDisplay._height / 2) - dy;
  }

  var centerX = pix2Geo(pixCenterX, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax);
  var centerY = pix2Geo(pixCenterY, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin);
  if (aDisplay._map.onPan) aDisplay._map.onPan(centerX, centerY);
  
  var layer = xGetElementById(aDisplay.currentLayer.id);
  xShow(layer);
};

/**
 * Creates a draw point tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DrawPointTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
DrawPointTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  var feature = new Point();

  if (aDisplay._map.onNewFeature)
    aDisplay._map.onNewFeature(feature);

  var dShape = aDisplay.addDiv(aDisplay.currentLayer, 0, 0, null, null);
  dShape.id = dShape.title = aDisplay.id + "_" + feature.id;
  dShape.X = new Array();
  dShape.Y = new Array();
  aDisplay.feature = feature;
  aDisplay.dShape = dShape;

  aDisplay._map.updateFeaturesCount();

  // store the new point coordinates
  var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = aDisplay.feature.vertices.length;
  aDisplay.dShape.X.push(ex);
  aDisplay.dShape.Y.push(ey);

  var dp = aDisplay.drawPoint(aDisplay.dShape, ex, ey, null, null, _OFF);
  dp.index = aDisplay.feature.vertices.length;
  aDisplay.feature.vertices.push(vertex);
};
DrawPointTool.prototype.onMouseUp = function(aDisplay, ex, ey) {
  if (aDisplay._map.onFeatureInput) {
    aDisplay._map.onFeatureInput(aDisplay.feature);
  }
  aDisplay.feature = undefined;
};
DrawPointTool.prototype.onKeyEscape = function(aDisplay) {
  aDisplay.currentLayer.removeChild(aDisplay.dShape);
  aDisplay.feature = undefined;
  if (aDisplay._map.onCancel) {
    aDisplay._map.onCancel();
  }
};

/**
 * Creates a draw line tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DrawLineTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
DrawLineTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  aDisplay.isDrawing = 'line';
  if (typeof(dblclick) != "undefined") return; // double click


  if (typeof aDisplay.snapToX != "undefined" && typeof aDisplay.snapToY != "undefined") {
    ex = aDisplay.snapToX;
    ey = aDisplay.snapToY;
  }

  if (!aDisplay.tmpFeature) { // new polyline
    var feature = new Polyline();
    if (aDisplay._map.onNewFeature)
      aDisplay._map.onNewFeature(feature)

  // moving line
    aDisplay.dml = aDisplay.drawLine(aDisplay.currentLayer, 0, 0, 0, 0);

    var dShape = aDisplay.addDiv(aDisplay.currentLayer, 0, 0, null, null);
    aDisplay.features.push(dShape);
    dShape.id = dShape.title = aDisplay.id + "_" + feature.id;
    dShape.X = new Array();
    dShape.Y = new Array();
    aDisplay.tmpFeature = feature;
    aDisplay.dShape = dShape; // assign the object to the map display
  } else {
    var vl = aDisplay.tmpFeature.vertices.length; // number of vertex
    if (vl > 0) {
      var dln = aDisplay.drawLine(aDisplay.dShape, ex, ey,
      aDisplay.dShape.X[vl - 1], aDisplay.dShape.Y[vl - 1],
      _OFF);
    }
  }

  aDisplay._map.updateFeaturesCount();
  // store the new point coordinates
  var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = aDisplay.tmpFeature.vertices.length;
  aDisplay.dShape.X.push(ex);
  aDisplay.dShape.Y.push(ey);

  var dp = aDisplay.drawPoint(aDisplay.dShape, ex, ey, null, null, _OFF);
  dp.index = aDisplay.tmpFeature.vertices.length;
  aDisplay.tmpFeature.vertices.push(vertex);
};
DrawLineTool.prototype.onMouseMove = function(aDisplay, ex, ey) {
  if (aDisplay.isDrawing == "line") {
    if (distance2Pts(this.oldmovedX, this.oldmovedY, ex, ey) > vertexDistance) {
      var vl = aDisplay.dShape.X.length;
      var dln = aDisplay.drawLine(aDisplay.dShape, ex, ey,
          aDisplay.dShape.X[vl - 1], aDisplay.dShape.Y[vl - 1],
          _OFF);
      var dp = aDisplay.drawPoint(aDisplay.dShape, ex, ey, null, null, _OFF);
      aDisplay.dShape.X.push(ex);
      aDisplay.dShape.Y.push(ey);
      var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
        pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
      vertex.index = aDisplay.tmpFeature.vertices.length;
      dp.index = aDisplay.tmpFeature.vertices.length;
      aDisplay.tmpFeature.vertices.push(vertex);
      this.oldmovedX = ex;
      this.oldmovedY = ey;
    } else if (!this.oldmovedX) {
      this.oldmovedX = ex;
      this.oldmovedY = ey;
    }
  } else {
    if (aDisplay.useSnapping) {
      if (typeof aDisplay.snapToVertex != "undefined") {
        aDisplay.snapToVertex.className = vertexCN + _OFF;
        aDisplay.snapToVertex = undefined;
        aDisplay.snapToX = undefined;
        aDisplay.snapToY = undefined;
      }
      var shortestDistance = snappingDistance;
      for (var i = 0; i < aDisplay.features.length - 1; i++) {
        var vertices = xGetElementsByClassName(vertexCN + _OFF, aDisplay.features[i], 'div');
        for (var j = 0; j < aDisplay.features[i].X.length; j++) {
          if (distance2Pts(aDisplay.features[i].X[j],aDisplay.features[i].Y[j],ex,ey) < shortestDistance) {
            shortestDistance = distance2Pts(aDisplay.features[i].X[j],aDisplay.features[i].Y[j],ex,ey);
            aDisplay.snapToVertex = vertices[j];
            aDisplay.snapToX = aDisplay.features[i].X[j];
            aDisplay.snapToY = aDisplay.features[i].Y[j];
          }
        }
      }
      if (typeof aDisplay.snapToVertex != "undefined") {
        aDisplay.snapToVertex.className = vertexCN + _SEL;
      }
    }
    if (aDisplay.tmpFeature) {
      // moving line
      var vl = aDisplay.dShape.X.length;
      if (typeof aDisplay.snapToX != "undefined" && typeof aDisplay.snapToY != "undefined") {
        aDisplay.drawLinePts(aDisplay.dml, aDisplay.snapToX, aDisplay.snapToY,
           aDisplay.dShape.X[vl - 1], aDisplay.dShape.Y[vl - 1],
           _OFF);
      } else {
        aDisplay.drawLinePts(aDisplay.dml, ex, ey,
           aDisplay.dShape.X[vl - 1], aDisplay.dShape.Y[vl - 1],
           _OFF);
      }
    }
  }
};
DrawLineTool.prototype.onDblClick = function(aDisplay, ex, ey) {
  aDisplay.dml.innerHTML = "";
  if (aDisplay._map.onFeatureInput) {
    aDisplay._map.onFeatureInput(aDisplay.tmpFeature);
  }
  aDisplay.tmpFeature = undefined;
};
DrawLineTool.prototype.onKeyEnter = function(aDisplay) {
  this.onDblClick(aDisplay);
};
DrawLineTool.prototype.onKeyEscape = function(aDisplay) {
  aDisplay.dml.innerHTML = "";
  aDisplay.currentLayer.removeChild(aDisplay.dShape);
  aDisplay.tmpFeature = undefined;
  if (aDisplay._map.onCancel) {
    aDisplay._map.onCancel();
  }
};

/**
 * Creates a draw box tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DrawBoxTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
DrawBoxTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  aDisplay.isDrawing = 'box';

  if (aDisplay._map.onNewFeature)
    aDisplay._map.onNewFeature(feature);

  if (!aDisplay.feature) {
    var feature = new Polygon();
    feature.operation = 'insert';
    var dShape = aDisplay.addDiv(aDisplay.currentLayer, ex, ey, null, null, boxCN + _OFF);
    aDisplay.addDiv(dShape, 0, 0, null, null, boxfillCN + _OFF);
    aDisplay.feature = feature;
    aDisplay.dShape = dShape;
  }
  aDisplay.downx = ex;
  aDisplay.downy = ey;
};
DrawBoxTool.prototype.onMouseMove = function(aDisplay, ex, ey) {
  var dx = ex - aDisplay.downx;
  var dy = ey - aDisplay.downy;
  xResizeTo(aDisplay.dShape, Math.abs(dx), Math.abs(dy));
  if (dx < 0 && dy < 0) xMoveTo(aDisplay.dShape, ex, ey);
  else if (dx < 0) xMoveTo(aDisplay.dShape, ex, aDisplay.downy);
  else if (dy < 0) xMoveTo(aDisplay.dShape, aDisplay.downx, ey);
  else xMoveTo(aDisplay.dShape, aDisplay.downx, aDisplay.downy);
}
DrawBoxTool.prototype.onMouseUp = function(aDisplay, ex, ey) {
  // fill the coordinates arrays as it was a polygon
  var vertex = new Vertex(pix2Geo(aDisplay.downx, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(aDisplay.downy, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = 0;
  aDisplay.feature.vertices.push(vertex);
  var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(aDisplay.downy, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = 1;
  aDisplay.feature.vertices.push(vertex);
  var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = 2;
  aDisplay.feature.vertices.push(vertex);
  var vertex = new Vertex(pix2Geo(aDisplay.downx, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = 3;
  aDisplay.feature.vertices.push(vertex);
  var vertex = new Vertex(pix2Geo(aDisplay.downx, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(aDisplay.downy, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  vertex.index = 4;
  aDisplay.feature.vertices.push(vertex);

  aDisplay.downx = aDisplay.downy = aDisplay.upx = aDisplay.upy = undefined;
  // fire a map event, box is drawn
  if (aDisplay._map.onFeatureInput) {
    aDisplay._map.onFeatureInput(aDisplay.feature);
  }
  aDisplay.feature = undefined;
};
DrawBoxTool.prototype.onKeyEscape = function(aDisplay) {
  aDisplay.currentLayer.removeChild(aDisplay.dShape);
  aDisplay.feature = undefined;
  if (aDisplay._map.onCancel) {
    aDisplay._map.onCancel();
  }
};

/**
 * Creates a draw polygon tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DrawPolygonTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
DrawPolygonTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  aDisplay.isDrawing = 'line';
  if (typeof(dblclick) != 'undefined') return; // double click

  if (typeof aDisplay.snapToX != "undefined" && typeof aDisplay.snapToY != "undefined") {
    ex = aDisplay.snapToX;
    ey = aDisplay.snapToY;
  }
  // new polygon
  if (!aDisplay.tmpFeature) {
    var feature = new Polygon();

    if (aDisplay._map.onNewFeature)
      aDisplay._map.onNewFeature(feature)

    // moving line
    aDisplay.dml = aDisplay.drawLine(aDisplay.currentLayer, 0, 0, 0, 0);
    // moving line
    aDisplay.dml2 = aDisplay.drawLine(aDisplay.currentLayer, 0, 0, 0, 0);

    var dShape = aDisplay.addDiv(aDisplay.currentLayer, 0, 0, null, null);
    aDisplay.features.push(dShape);
    dShape.id = dShape.title = aDisplay.id + "_" + feature.id;
    dShape.X = new Array();
    dShape.Y = new Array();

    aDisplay.tmpFeature = feature;
    aDisplay.dShape = dShape; // assign the object to the map display

    aDisplay._map.updateFeaturesCount();
  } else if (aDisplay.tmpFeature.closing) {
    // close the polygon
    this.onDblClick(aDisplay, aDisplay.dShape.X[0], aDisplay.dShape.Y[0]);
    return;
  } else {
    var vl = aDisplay.tmpFeature.vertices.length; // number of vertex;
    if (vl > 0) {
      // check for intersection
      for ( var i = 0; i< aDisplay.dShape.X.length - 1; i++ ) {
        var v1 = new Vertex(ex, ey);
        var v2 = new Vertex(aDisplay.dShape.X[vl - 1], aDisplay.dShape.Y[vl - 1]);
        var line1 = new Line(v1, v2);
        var v1 = new Vertex(aDisplay.dShape.X[i], aDisplay.dShape.Y[i]);
        var v2 = new Vertex(aDisplay.dShape.X[i+1],  aDisplay.dShape.Y[i+1]);
        var line2 = new Line(v1, v2);
        if (line1.intersectsWith(line2)) {
          var overlapping = true;
          continue;
        }
      }
      if (overlapping) {
        aDisplay.isDrawing = false;
        if (confirm(_m_overlap)) return;
      }
      var dln = aDisplay.drawLine(aDisplay.dShape, ex, ey, aDisplay.dShape.X[vl - 1], aDisplay.dShape.Y[vl - 1], _OFF);
    }
  }
  // store the new point coordinates
  if (typeof aDisplay.snapToVertex != "undefined") {
    var index = xParent(aDisplay.snapToVertex,true).index;
    var snapToFeature = aDisplay.getMapFeature(xParent(xParent(aDisplay.snapToVertex, true), true));
    var vertex = new Vertex(snapToFeature.vertices[index].x, snapToFeature.vertices[index].y);
  } else {
    var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
    pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
  }
  vertex.index = aDisplay.tmpFeature.vertices.length;
  aDisplay.dShape.X.push(ex);
  aDisplay.dShape.Y.push(ey);

  var dp = aDisplay.drawPoint(aDisplay.dShape, ex, ey, null, null, _OFF);
  dp.index = aDisplay.tmpFeature.vertices.length;
  aDisplay.tmpFeature.vertices.push(vertex);
};
DrawPolygonTool.prototype.onMouseMove = function(aDisplay, ex, ey) {
  if (aDisplay.isDrawing != "line" && aDisplay.tmpFeature) {
    // moving line
    var vl = aDisplay.dShape.X.length;
    aDisplay.drawLinePts(aDisplay.dml2, ex, ey,
         aDisplay.dShape.X[0], aDisplay.dShape.Y[0],
         _OFF);
    if (typeof aDisplay.snapToVertex != "undefined") {
      aDisplay.snapToVertex.className = vertexCN + _OFF;
      aDisplay.snapToVertex = undefined;
      aDisplay.snapToX = undefined;
      aDisplay.snapToY = undefined;
      aDisplay.tmpFeature.closing = false;
    }
  }
  if (typeof this.lineDraw == 'undefined') {
    this.lineDraw = new DrawLineTool(aDisplay);
  }
  this.lineDraw.onMouseMove(aDisplay, ex, ey);

  if (aDisplay.isDrawing != "line" && aDisplay.tmpFeature &&
      aDisplay.dShape.X.length > 1 &&
      distance2Pts(aDisplay.dShape.X[0],aDisplay.dShape.Y[0],ex,ey) < snappingDistance) {
    var vertices = xGetElementsByClassName(vertexCN + _OFF, aDisplay.dShape, 'div');
    aDisplay.snapToVertex = vertices[0];
    aDisplay.snapToVertex.className = vertexCN + _SEL;
    aDisplay.snapToX = aDisplay.dShape.X[0];
    aDisplay.snapToY = aDisplay.dShape.Y[0];
    aDisplay.tmpFeature.closing = true;
  }
};
DrawPolygonTool.prototype.onDblClick = function(aDisplay, ex, ey) {
  aDisplay.dml.innerHTML = "";
  aDisplay.dml2.innerHTML = "";
  // close the polygon
  var vl = aDisplay.tmpFeature.vertices.length; // number of vertex
  var dln = aDisplay.drawLine(aDisplay.dShape, aDisplay.dShape.X[0], aDisplay.dShape.Y[0],
      aDisplay.dShape.X[vl-1], aDisplay.dShape.Y[vl-1], _OFF);
  // store the first point coordinates
  var vertex = new Vertex(aDisplay.tmpFeature.vertices[0].x, aDisplay.tmpFeature.vertices[0].y);
  aDisplay.tmpFeature.vertices.push(vertex);
  aDisplay.dShape.X.push(aDisplay.dShape.X[0]);
  aDisplay.dShape.Y.push(aDisplay.dShape.Y[0]);
  aDisplay.fillPolygon(aDisplay.dShape, _OFF);
  if (aDisplay._map.onFeatureInput) {
    aDisplay._map.onFeatureInput(aDisplay.tmpFeature);
  }

  if (typeof aDisplay.snapToVertex != "undefined") {
    aDisplay.snapToVertex.className = vertexCN + _OFF;
    aDisplay.snapToVertex = undefined;
    aDisplay.snapToX = undefined;
    aDisplay.snapToY = undefined;
    aDisplay.tmpFeature.closing = false;
  }
  aDisplay.tmpFeature = undefined;
};
DrawPolygonTool.prototype.onKeyEnter = function(aDisplay) {
  this.onDblClick(aDisplay);
};
DrawPolygonTool.prototype.onKeyEscape = function(aDisplay) {
  aDisplay.dml.innerHTML = "";
  aDisplay.dml2.innerHTML = "";
  aDisplay.currentLayer.removeChild(aDisplay.dShape);
  aDisplay.tmpFeature = undefined;
  if (aDisplay._map.onCancel) {
    aDisplay._map.onCancel();
  }
};

/**
 * Creates a draw circle tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DrawCircleTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
DrawCircleTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  aDisplay.isDrawing = 'circle';

  if (aDisplay._map.onNewFeature)
    aDisplay._map.onNewFeature(feature);

  // new circle
  if (!aDisplay.tmpFeature) {
    var feature = new Circle();
    feature.operation = 'insert';
    var dShape = aDisplay.addDiv(aDisplay.currentLayer, 0, 0, null, null);
    aDisplay.tmpFeature = feature;
    dShape.id = dShape.title = aDisplay.id + "_" + feature.id;
    dShape.X = new Array();
    dShape.Y = new Array();
    aDisplay.dShape = dShape;
  }
  aDisplay.downx = ex;
  aDisplay.downy = ey;
};
DrawCircleTool.prototype.onMouseMove = function(aDisplay, ex, ey) {

  if (aDisplay.tmpFeature) {
    aDisplay.dShape.innerHTML = '';
    var dx = ex - aDisplay.downx;
    var dy = ey - aDisplay.downy;
    var radius = Math.sqrt(dx * dx + dy * dy);

    aDisplay.drawEllipse(aDisplay.dShape, aDisplay.downx - radius, aDisplay.downy - radius,
      radius * 2, radius * 2, _OFF);
  }
};
DrawCircleTool.prototype.onMouseUp = function(aDisplay, ex, ey) {

  // fire a map event, box is drawn
  if (aDisplay.tmpFeature) {

    // fill the circle

    var dx = ex - aDisplay.downx;
    var dy = ey - aDisplay.downy;
    var radius = Math.sqrt(dx * dx + dy * dy);

    aDisplay.fillEllipse(aDisplay.dShape, aDisplay.downx - radius, aDisplay.downy - radius,
      radius * 2, radius * 2, _OFF);

    var vertex = new Vertex(pix2Geo(aDisplay.downx, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
        pix2Geo(aDisplay.downy, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
    aDisplay.tmpFeature.vertices.push(vertex);

    // TODO manage non orthonormed reference systems
    aDisplay.tmpFeature.radius = Math.abs(pix2Geo(radius, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax) - aDisplay._map.extent.xmin);

    if (aDisplay._map.onFeatureInput) {
      aDisplay._map.onFeatureInput(aDisplay.tmpFeature);
    }
  }

  aDisplay.downx = aDisplay.downy = undefined;
  aDisplay.tmpFeature = undefined;
}
DrawCircleTool.prototype.onKeyEscape = function(aDisplay) {
  aDisplay.currentLayer.removeChild(aDisplay.dShape);
  aDisplay.tmpFeature = undefined;
  if (aDisplay._map.onCancel) {
    aDisplay._map.onCancel();
  }
};

/**
 * Creates a move tool (see Display.mouseAction)
 * Allows features and vertices moves
 * @param aDisplay display object
 */
function MoveTool(aDisplay) {
  xHide(aDisplay.eventPad);
  aDisplay.docObj.style.cursor = "auto";
  xDisableDrag(aDisplay.rootDisplayLayer);
};
MoveTool.prototype.onMouseOver = function(aDisplay, ex, ey) {
  // over map
  if (umo.className.indexOf(layerCN) == -1) {
    var cn = umo.className;
    if (cn.indexOf(vertexCN) != -1) { // on a vertex
      if (xParent(umo, true).className.indexOf(layerCN) != -1)
        // dShape is point
        var dShape = umo;
      else
        // dShape is polyline or polygon
        var dShape = xParent(umo, true);
    }
    else var dShape = xParent(xParent(umo, true), true); // clicked on a line of a polyline or polygon
    if (dShape.className.indexOf(_SEL) == -1) { // geometry not selected yet, enable drag on all
        // hilight : change the style of all elements
    } else { // geometry selected
      if (umo.className.indexOf(vertexCN) != -1) {// enable vertex drag if on vertex
        // TODO specific functions for dragging a vertex or dShape or pan
        xEnableDrag(umo, aDisplay.dragStart, aDisplay.drag, aDisplay.dragEnd);
      }
      else // enable drag for all the geometry
        xEnableDrag(dShape, aDisplay.dragStart, null, aDisplay.dragEnd);
    }
  }
};
MoveTool.prototype.onMouseOut = function(aDisplay, ex, ey) {
  if (!umo) {
    return;
  }
  // over map
  if (umo.className.indexOf(layerCN) == -1) {
    var cn = umo.className;
    if (cn.indexOf(vertexCN) != -1) { // clicked on a vertex
      if (xParent(umo, true).className.indexOf(layerCN) != -1)
        // dShape is point
        var dShape = umo;
      else
        // dShape is polyline or polygon
        var dShape = xParent(umo, true);
    }
    // clicked on a line of a polyline or polygon
    else var dShape = xParent(xParent(umo, true), true);
    // poly obj not selected yet, disable drag for all the geometry
    if (dShape.className.indexOf(_SEL) == -1) {
      // change the style of all elements
      xDisableDrag(dShape);
    } else {
      if (umo.className.indexOf(vertexCN) != -1) // on vertex
        xDisableDrag(umo);
      else xDisableDrag(dShape);
    }
  }
};
MoveTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  if (!umo) return;
  // clicked on map

  if (umo.className.indexOf(layerCN) != -1) {
    if (aDisplay._map.onUnselectFeatures) aDisplay._map.onUnselectFeatures();
    changeStatus(aDisplay.currentLayer, _OFF, true, true);
  } else {
    var cn = umo.className;
    var dShape = xParent(xParent(umo, true), true);
    if (cn.indexOf(_SEL) == -1) changeStatus(aDisplay.currentLayer, _OFF, true, true);
    changeStatus(dShape, _SEL, true, true);

    var feature = aDisplay.getMapFeature(dShape);
    if (feature.operation != 'insert') feature.operation = 'update';
    aDisplay._map.updateFeaturesCount();
    if (aDisplay._map.onFeatureSelected)
      aDisplay._map.onFeatureSelected(feature);
  }
};
MoveTool.prototype.onDrag = function(elt, x, y) {
  var aDisplay = elt._display;

  var cn = elt.className;
  var currentLayer = aDisplay._map.currentLayer;
  var xmin = aDisplay._map.extent.xmin;
  var xmax = aDisplay._map.extent.xmax;
  var ymin = aDisplay._map.extent.ymin;
  var ymax = aDisplay._map.extent.ymax;

  if (cn.indexOf(vertexCN) != -1) { // clicked on a vertex
    // TODO use snapping when moving vertices
    xMoveTo(elt,xLeft(elt) + x, xTop(elt) + y);
  }
};
MoveTool.prototype.onDragEnd = function(elt, x, y) {
  var aDisplay = elt._display;

  var cn = elt.className;
  var currentLayer = aDisplay._map.currentLayer;
  var xmin = aDisplay._map.extent.xmin;
  var xmax = aDisplay._map.extent.xmax;
  var ymin = aDisplay._map.extent.ymin;
  var ymax = aDisplay._map.extent.ymax;

  if (cn.indexOf(vertexCN) != -1) { // clicked on a vertex
    var dShape = xParent(xParent(elt, true), true);

    var feature = aDisplay.getMapFeature(dShape);
    if (feature.operation != 'insert') feature.operation = 'update';

    // get the index of the moved vertex
    var currentVertexIndex = xParent(elt, true).index;
    if (typeof currentVertexIndex == "undefined") {
      return;
    }

    if (typeof aDisplay.snapToX != "undefined" && typeof aDisplay.snapToY != "undefined") {
      var newX = aDisplay.snapToX;
      var newY = aDisplay.snapToY;
      xMoveTo(elt, newX - pointSize / 2, newY - pointSize / 2);
    } else {
      var newX = xOffsetLeft(elt) + pointSize / 2;
      var newY = xOffsetTop(elt) + pointSize / 2;
    }
    // change the coordinates in the map object
    var newGeoX = pix2Geo(newX + xOffsetLeft(dShape), 0, aDisplay._width, xmin, xmax);
    var newGeoY = pix2Geo(newY + xOffsetTop(dShape), 0, aDisplay._height, ymax, ymin);

    //get the next and previous lines, and next and previous vertex
    var pl, nl, pv, nv;
    // closed polygon case
    if (isClosedPolygon(feature) && currentVertexIndex == 0) {
      // also change the last point coordinates
      feature.vertices[feature.vertices.length - 1].x = feature.vertices[0].x = newGeoX;
      feature.vertices[feature.vertices.length - 1].y = feature.vertices[0].y = newGeoY;
      dShape.X[dShape.X.length-1] = dShape.X[0] = newX;
      dShape.Y[dShape.Y.length-1] = dShape.Y[0] = newY;

      // previous line is the last element of the dShape
      // TODO error with polygon fill
      var nextElt = xFirstChild(dShape);
      while (null != xNextSib(nextElt)) {
        nextElt = xNextSib(nextElt);
        if (nextElt.id.indexOf('li') != -1)
                  pl = nextElt;
      }
      nl = xNextSib(xParent(elt, true));
      pv = feature.vertices.length - 2;
      nv = 1;
    } else {
      pl = xPrevSib(xParent(elt, true));
      nl = xNextSib(xParent(elt, true));
      pv = currentVertexIndex - 1;
      nv = currentVertexIndex + 1;
      feature.vertices[currentVertexIndex].x = newGeoX;
      feature.vertices[currentVertexIndex].y = newGeoY;
      dShape.X[currentVertexIndex] = newX;
      dShape.Y[currentVertexIndex] = newY;
    }

    if (!feature.clipped) {
      // redraw the previous and next lines
      if (pl) {
        aDisplay.drawLine(pl, dShape.X[pv],
        dShape.Y[pv], newX,
        newY, _SEL);
      }
      if (nl) {
        aDisplay.drawLine(nl, dShape.X[nv],
        dShape.Y[nv], newX,
        newY, _SEL);
      }

      // refill the closed polygons
      if (isClosedPolygon(feature)){
        aDisplay.fillPolygon(dShape, _SEL);
      }
    } else {
      var prevFeature = xGetElementById(aDisplay.id + "_" + feature.id);
      aDisplay.currentLayer.removeChild(prevFeature);
      aDisplay.drawFeature(aDisplay.currentLayer, feature, _SEL);
    }

  } else {// complete feature moved
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

    // redraw the polygon if clipped
    if (feature.clipped) {
      var prevFeature = xGetElementById(aDisplay.id + "_" + feature.id);
      aDisplay.currentLayer.removeChild(prevFeature);
      aDisplay.drawFeature(aDisplay.currentLayer, feature, _SEL);
    }
  }
  aDisplay.dragon = false;
  if (aDisplay._map.onFeatureChange) aDisplay._map.onFeatureChange(feature);
};

/**
 * Creates a select by point tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function SelPointTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
SelPointTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  aDisplay.addDiv(aDisplay.currentLayer, ex - 4, ey, 10, 2, linepointCN + _OFF);
  aDisplay.addDiv(aDisplay.currentLayer, ex, ey - 4, 2, 10, linepointCN + _OFF);
  var x = pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax);
  var y = pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin);
  // TODO rewrite this
  if (aDisplay._map.onSelPoint) aDisplay._map.onSelPoint(x, y);
};

/**
 * Creates a select by rectangle tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function SelBoxTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "crosshair";
  xDisableDrag(aDisplay.rootDisplayLayer);
  // deselect all previously selected features
  changeStatus(aDisplay.rootDisplayLayer, _OFF, true, true);
};
SelBoxTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  xShow(aDisplay.eventPad);
  if (!aDisplay.dShape) {
    var dShape = aDisplay.addDiv(aDisplay.currentLayer, ex, ey, null, null, boxCN + _OFF);
    aDisplay.addDiv(dShape, 0, 0, null, null, boxfillCN + _OFF);
    aDisplay.dShape = dShape;
  }
  aDisplay.downx = ex;
  aDisplay.downy = ey;
};
SelBoxTool.prototype.onMouseMove = function(aDisplay, ex, ey) {
  var dx = ex - aDisplay.downx;
  var dy = ey - aDisplay.downy;
  xResizeTo(aDisplay.dShape, Math.abs(dx), Math.abs(dy));
  if (dx < 0 && dy < 0) xMoveTo(aDisplay.dShape, ex, ey);
  else if (dx < 0) xMoveTo(aDisplay.dShape, ex, aDisplay.downy);
  else if (dy < 0) xMoveTo(aDisplay.dShape, aDisplay.downx, ey);
  else xMoveTo(aDisplay.dShape, aDisplay.downx, aDisplay.downy);
};
SelBoxTool.prototype.onMouseUp = function(aDisplay, ex, ey) {
  aDisplay.upx = ex;
  aDisplay.upy = ey;

  var xmin = pix2Geo(aDisplay.downx, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax);
  var xmax = pix2Geo(aDisplay.upx, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax);
  var ymin = pix2Geo(aDisplay.downy, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin);
  var ymax = pix2Geo(aDisplay.upy, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin);

  if (aDisplay._map.onSelBox) aDisplay._map.onSelBox(xmin, ymin, xmax, ymax);
  aDisplay.downx = aDisplay.downy = aDisplay.upx = aDisplay.upy = undefined;
  aDisplay.dShape = undefined;
};

/**
 * Creates a delete vertex tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DeleteVertexTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "auto";
  xDisableDrag(aDisplay.rootDisplayLayer);
};
DeleteVertexTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  if (!umo) return;
  // clicked on map
  if (umo.className.indexOf(layerCN) != -1) {
    if (aDisplay._map.onUnselectFeatures) aDisplay._map.onUnselectFeatures();
    changeStatus(aDisplay.currentLayer, _OFF, true, true);
  } else {
    var cn = umo.className;
    var dShape = xParent(xParent(umo, true), true); // clicked on a line of a polyline or polygon

    if (cn.indexOf(_SEL) == -1) {
      // unselect all the others features
      changeStatus(aDisplay.currentLayer, _OFF, true, true);
      changeStatus(dShape, _SEL, true, true);
    } else if (cn.indexOf(vertexCN) != -1) {
      var feature = aDisplay.getMapFeature(dShape);

      if (feature.operation != 'insert') feature.operation = 'update';
      aDisplay._map.updateFeaturesCount();


      var currentVertexIndex = xParent(umo, true).index;

      // remove the vertex in the feature
      var newGeo = new Array();
      var newX = new Array();
      var newY = new Array();
      for (var i = 0; i < feature.vertices.length; i++) {
        if (i == currentVertexIndex) continue;
        var vertex = new Vertex(feature.vertices[i].x,
          feature.vertices[i].y);
        vertex.index = newGeo.length;
        newGeo.push(vertex);
        newX.push(dShape.X[i]);
        newY.push(dShape.Y[i]);
      }

      // case of closed polygon first vertex
      if (currentVertexIndex == 0 && isClosedPolygon(feature)) {
        var vertex = new Vertex(newGeo[0].x, newGeo[0].y);
        newGeo.index = newGeo.length;
        newGeo[newGeo.length - 1] = vertex;
        newX[newX.length - 1] = newX[0];
        newY[newX.length - 1] = newY[0];
      }

      // replace with the new coordinates
      feature.vertices = newGeo;
      dShape.X = newX;
      dShape.Y = newY;

      aDisplay.currentLayer.removeChild(dShape);
      aDisplay.drawFeature(aDisplay.currentLayer, feature, _SEL);

      //aDisplay.fillPolygon(dShape, _SEL);
      if (aDisplay._map.onFeatureChange) aDisplay._map.onFeatureChange(feature);
    }
  }
};

/**
 * Creates a add vertex tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function AddVertexTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "auto";
  xDisableDrag(aDisplay.rootDisplayLayer);
};
AddVertexTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  if (!umo) return;
    // clicked on map

  if (umo.className.indexOf(layerCN) != -1) {
    if (aDisplay._map.onUnselectFeatures) aDisplay._map.onUnselectFeatures();
    changeStatus(aDisplay.currentLayer, _OFF, true, true);
  } else {
    var cn = umo.className;
    var dShape = xParent(xParent(umo, true), true); // clicked on a line of a polyline or polygon

    if (cn.indexOf(_SEL) == -1) {
      // unselect all the others features
      changeStatus(aDisplay.currentLayer, _OFF, true, true);
      changeStatus(dShape, _SEL, true, true);
      return;
    } else {

      if (cn.indexOf(linepointCN) == -1) // clicked on a line of a polyline or polygon
        return;
      var feature = aDisplay.getMapFeature(dShape);

      var line = xParent(umo, true);
      //get the next and previous lines, and next and previous vertex
      var pv = xPrevSib(line);
      var nv = xNextSib(line);

      // get the index of the previous vertex
      var currentVertexIndex = pv.index;

      // add the vertex in the feature
      var newGeo = new Array();
      var newX = new Array();
      var newY = new Array();
      for (var i = 0;i < feature.vertices.length;i++) {
        var vertex = new Vertex();
        vertex = feature.vertices[i];
        vertex.index = newGeo.length;
        newGeo.push(vertex);
        newX.push(dShape.X[i]);
        newY.push(dShape.Y[i]);
        // add the new point
        if (i == currentVertexIndex) {
          var vertex = new Vertex(pix2Geo(ex, 0, aDisplay._width, aDisplay._map.extent.xmin, aDisplay._map.extent.xmax),
            pix2Geo(ey, 0, aDisplay._height, aDisplay._map.extent.ymax, aDisplay._map.extent.ymin));
          vertex.index = newGeo.length;
          newGeo.push(vertex);
          newX.push(ex - xOffsetLeft(dShape));
          newY.push(ey - xOffsetTop(dShape));
        }
      }
      // replace with the new coordinates
      feature.vertices = newGeo;
      dShape.X = newX;
      dShape.Y = newY;
      if (aDisplay._map.onFeatureChange) aDisplay._map.onFeatureChange(feature);

      aDisplay.currentLayer.removeChild(dShape);
      aDisplay.drawFeature(aDisplay.currentLayer, feature, _SEL);
    }
  }
};

/**
 * Creates a delete feature tool (see Display.mouseAction)
 * @param aDisplay display object
 */
function DeleteFeatureTool(aDisplay) {
  if (document.getElementById('map_imagemap')) {
    xHide(aDisplay.eventPad);
  } else {
    xShow(aDisplay.eventPad);
  }
  aDisplay.docObj.style.cursor = "auto";
  xDisableDrag(aDisplay.rootDisplayLayer);
};
DeleteFeatureTool.prototype.onMouseDown = function(aDisplay, ex, ey) {
  if (!umo) return;
  // clicked on map

  if (umo.className.indexOf(layerCN) != -1) {
    if (aDisplay._map.onUnselectFeatures) aDisplay._map.onUnselectFeatures();
    changeStatus(aDisplay.currentLayer, _OFF, true, true);
  } else {
    var cn = umo.className;
    var dShape = xParent(xParent(umo, true), true); // clicked on a line of a polyline or polygon

    if (cn.indexOf(_SEL) == -1) {
      // unselect all the others features
      changeStatus(aDisplay.currentLayer, _OFF, true, true);
      changeStatus(dShape, _SEL, true, true);
    } else {

      if (! confirm(_m_delete_feature + "\n" + dShape.id)) return;
      xHide(dShape);

      var feature = aDisplay.getMapFeature(dShape);
      if (feature.operation != 'insert') feature.operation = 'delete';
      aDisplay._map.updateFeaturesCount();
      if (aDisplay._map.onFeatureChange) aDisplay._map.onFeatureChange(feature);
    }
  }
};

/**
 * Start a drag event
 */
Display.prototype.dragStart = function(elt, x, y) {
  elt._display.dragon = true;
  elt.startX = x;
  elt.startY = y;
  if (elt._display.mouseAction && elt._display.mouseAction.onDragStart)
    elt._display.mouseAction.onDragStart(elt,x,y);
};

/**
 * During a drag event
 */
Display.prototype.drag = function(elt, x, y) {
  if (elt._display.mouseAction && elt._display.mouseAction.onDrag)
    elt._display.mouseAction.onDrag(elt,x,y);
};

/**
 * End drag event
 */
Display.prototype.dragEnd = function(elt, x, y) {
  if (elt._display.mouseAction && elt._display.mouseAction.onDragEnd)
    elt._display.mouseAction.onDragEnd(elt,x,y);
};

/**
 * Creates a layer
 * @param obj object to add a layer to (parent)
 * @return layer object created in DOM
 */
Display.prototype.addLayer = function(obj, name) {

  var aLayer = xCreateElement('div');

  var layer;

  if (typeof(obj.className) == "undefined") // obj name given
    layer = xGetElementById(this.id + "_" + obj.id)
  else
    layer = obj;
  if (layer == null)
    layer = this.rootDisplayLayer;

  aLayer.className = layerCN;
  aLayer.id = this.id + "_" + name;
  aLayer.X = new Array();
  aLayer.Y = new Array();

  xAppendChild(layer, aLayer);
  aLayer.style.position = "absolute";
  xResizeTo(aLayer, this._width, this._height);

  xClip(aLayer, 0, this._width, this._height, 0);

  aLayer._display = this;

  return aLayer;
};

/**
 * Draws a div
 * @param obj object to add a div to
 * @param x x (pixels)
 * @param y y (pixels)
 * @param w width (pixels)
 * @param h height (pixels)
 * @param cls className
 * @return Div object created in DOM
 */
Display.prototype.addDiv = function(obj, x, y, w,h, cls) {
  var aDiv = xCreateElement('div');
  aDiv.className = (cls) ? cls : "";
  aDiv.style.position = "absolute";
  xAppendChild(obj, aDiv);
  if (w != null && h != null)
    xResizeTo(aDiv, w, h);
  xMoveTo(aDiv, x , y );
  // reference to the parent (display)
  aDiv._display = this;
  return aDiv;
};

/**
* Clears a layer, removes all its content
* @param aLayerId Id of the layer to be cleared
* @return Div object modified in DOM
*/
Display.prototype.clearLayer = function(aLayerId) {
  var aLayer = xGetElementById(this.id + "_" + aLayerId);
  if (aLayer != null) {
	  Logger.send('Display.clearLayer : ' + aLayer.id);
	  aLayer.innerHTML = '';
	  this.tmpFeature = undefined;
	  return aLayer;
  }
};

/**
 * Draws a point
 * @param obj object to add a line to, or object line that already exists
 * @param x1 start x (pixels)
 * @param y1 start y (pixels)
 * @param x2 start x (pixels)
 * @param y2 start y (pixels)
 * @param status status for classname
 * @return Line object created in DOM
 */
Display.prototype.drawPoint = function(obj, x1, y1, x2, y2, status) {
  aPoint = this.addDiv(obj, 0, 0, null, null);
  aPoint.id = "pt" + pi++;

  aPoint.style.position = "absolute";
  aPoint.style.zIndex = 2;

  var jg = new jsGraphics(aPoint.id);

  if (typeof pointSize == "undefined")
    pointSize = this.getPointSize();

  jg.fillRect(x1 - pointSize/2,y1 - pointSize/2, pointSize, pointSize, vertexCN + status);
  jg.paint();

  xWalkTree(aPoint, function(elt) {elt._display = obj._display});

  return aPoint;
};

/**
 * Draws a line
 * @param obj object to add a line to, or object line that already exists
 * @param x1 start x (pixels)
 * @param y1 start y (pixels)
 * @param x2 start x (pixels)
 * @param y2 start y (pixels)
 * @param status status for classname
 * @return Line object created in DOM
 */
Display.prototype.drawLine = function(obj, x1, y1, x2, y2, status) {
  this.dSpacing = 10;

  var status = (typeof(status) != 'undefined' && status != "") ? status : _OFF;

  // already existing object
  if ((typeof(obj.id) != 'undefined' && obj.id != "") && (obj.id.substr(0,2) == 'li')) {
    var aLine = obj;
    aLine.innerHTML = "";
  } else { // new object
    var aLine = xCreateElement('div');
    xAppendChild(obj, aLine);
    aLine.style.position = "absolute";
    aLine.style.zIndex = 1;
    aLine._display = obj._display;
  }

  aLine.id = "li" + li++;

  var jg = new jsGraphics(aLine.id);
  jg.setStroke(2);
  jg.drawLine(x1, y1, x2, y2, linepointCN + status);
  jg.paint();

  xWalkTree(aLine, function(elt) {elt._display = obj._display});

  return aLine;
};

/**
 * Draws a line with points
 * @param obj object to add a line to, or object line that already exists
 * @param x1 start x (pixels)
 * @param y1 start y (pixels)
 * @param x2 start x (pixels)
 * @param y2 start y (pixels)
 * @param d2pts distance between 2 points
 * @param status status for classname
 * @return Line object created in DOM
 */
Display.prototype.drawLinePts = function(obj, x1, y1, x2, y2, status) {
  // TODO generic variable
  this.d2pts = 10;

  var status = (typeof(status) != 'undefined') ? status : _OFF;

  // already existing object
  if ((typeof(obj.id) != 'undefined') && (obj.id.substr(0,2) == 'li')) {
    var aLine = obj;
    aLine.innerHTML = "";
  } else { // new object
    var aLine = xCreateElement('div');
    aLine.className = obj._display.id + "_" + linepointCN + status;
    xAppendChild(obj, aLine)
    aLine._display = obj._display;
  }

  aLine.id = "li" + li++;

  var jg = new jsGraphics(aLine.id);
  jg.setStroke(2);
  jg.drawLinePts(x1, y1, x2, y2, this.d2pts, linepointCN + status);
  jg.paint();

  xWalkTree(aLine, function(elt) {elt._display = obj._display});

  return aLine;
};

/**
 * Fills a polygon
 * @param aPolygon drawn polygon to fill
 * @param cls className for the filling divs
 */
Display.prototype.fillPolygon = function(aPolygon, status) {
  pg = aPolygon;

  if (!pg.pf) {
    pg.pf = xCreateElement('div');
    pfc = true;
    pg.pf.id = 'pf' + pfi++;
    xAppendChild(pg, pg.pf);
    pg.style.position = "absolute";
    pg.style.zIndex = 1;
  } else {
    pfc = false;
    pg.pf.innerHTML = '';
  }

  var jg = new jsGraphics(pg.pf.id);
  jg.fillPolygon(aPolygon.X, aPolygon.Y, polygonfillCN + status);
  jg.paint();

  xWalkTree(aPolygon, function(elt) {elt._display = pg._display});
};

/**
 * Draws an ellipse
 * @param aCircle circle to draw
 * @param cls className for the filling divs
 */
Display.prototype.fillEllipse = function(aEllipse, x, y ,w , h, status) {
  var pg = aEllipse;

  if (!pg.pf) {
    pg.pf = xCreateElement('div');
    pfc = true;
    pg.pf.id = 'pf' + pfi++;
    xAppendChild(pg, pg.pf);
    pg.style.position = "absolute";
    pg.style.zIndex = 1;
  } else {
    pfc = false;
    pg.pf.innerHTML = '';
  }
  var jg = new jsGraphics(pg.pf.id);
  jg.fillEllipse(x, y, w, h, polygonfillCN + status);
  jg.paint();


  xWalkTree(aEllipse, function(elt) {elt._display = pg._display});
};

/**
 * Fills an ellipse
 * @param aPolygon drawn circle to fill
 * @param cls className for the filling divs
 */
Display.prototype.drawEllipse = function(obj, x, y ,w , h, status) {

  var jg = new jsGraphics(obj.id);
  jg.setStroke(2);
  jg.drawEllipse(x, y, w, h, linepointCN + status);
  jg.paint();
};

/**
 * Draws a feature
 * @param obj object to add a point to
 * @param feature
 * @param status
 * @param clip boolean allow clipping by rectangle
 * @return object created in DOM
 */
Display.prototype.drawFeature = function(obj, feature, status, allowClipping) {
  var xmin = this._map.extent.xmin;
  var ymin = this._map.extent.ymin;
  var xmax = this._map.extent.xmax;
  var ymax = this._map.extent.ymax;
  if (typeof status == "undefined")
    status = _OFF;
  if (typeof allowClipping == "undefined")
    allowClipping = true;

  switch (feature.getObjectClass()) {
    case "Raster":
      // add a div for the raster image
      var dr = this.addDiv(obj, 0, 0, null, null);

      img = xCreateElement('img');
      if (typeof(feature.id != 'undefined')) {
        img.id = feature.id;
      }
      img.style.position = "absolute";
      img.className = layerCN;
      dr.style.position = "absolute";
      img.src = feature.img;
      img._width = this._width;
      img._height = this._height;
      img._display = this;
      xAppendChild(dr, img);
      break;
    case "Feature":
      // TODO change extent use in mapObj( rectangle2D);
      var buffer = ((xmax - xmin) * 5 / 100 + (ymax - ymin) * 5 / 100 ) / 2;
	  var clippingExtent = new Rectangle2D(xmin - buffer , ymin - buffer , xmax + buffer , ymax + buffer);
	  if (allowClipping && feature.vertices.length > 0 && !feature.isWithinRectangle2D(clippingExtent)
        && feature.type != 'point') {
	    clippedFeature = feature.clipByRectangle2D(clippingExtent);
	    clippedFeature.id = feature.id;
	    feature.clipped = true;
	    featureToDraw = clippedFeature;
	  } else {
        feature.clipped = false;
        featureToDraw = feature;
	  }

	  switch (feature.type) {
	    case "point":
	      var dShape = this.addDiv(obj, 0, 0, null, null, status);
	      dShape.id = dShape.title = this.id + "_" + feature.id;
	      dShape.X = new Array();
	      dShape.Y = new Array();
          var vl = featureToDraw.vertices.length;
          for (i=0;i<vl;i++) {
            dShape.X.push(geo2Pix(feature.vertices[0].x, xmin, xmax, 0, this._width));
	        dShape.Y.push(geo2Pix(feature.vertices[0].y, ymax, ymin, 0, this._height));
	        var dp = this.drawPoint(dShape, dShape.X[0], dShape.Y[0], null, null, status);
	        dp.index = 0;
            dp.title = this.id + "_" + feature.id;
          }
	      break;
	    case "polyline":
	      // add a div for the polyline
	      var dShape = this.addDiv(obj, 0, 0, null, null, status);
	      obj._display.features.push(dShape);
	      dShape.id = dShape.title = this.id + "_" + feature.id;
	      dShape.X = new Array();
	      dShape.Y = new Array();
	      var vl = featureToDraw.vertices.length;
	      for (i=0;i<vl;i++) {
	        dShape.X.push(Math.round(geo2Pix(featureToDraw.vertices[i].x, xmin, xmax, 0, this._width)));
	        dShape.Y.push(Math.round(geo2Pix(featureToDraw.vertices[i].y, ymax, ymin, 0, this._height)));
	        if (i>0)
	          var dln = this.drawLine(dShape, dShape.X[i], dShape.Y[i], dShape.X[i-1], dShape.Y[i-1], status);
	        var dp = this.drawPoint(dShape, dShape.X[i], dShape.Y[i], null,  null, status);
	        dp.index = featureToDraw.vertices[i].index;
	        dp.title = this.id + "_" + feature.id;
	      }
	      break;
	    case "polygon":
	      // add a div for the polygon
	      var dShape = this.addDiv(obj, 0, 0, null, null, status);
	      obj._display.features.push(dShape);
	      dShape.id = dShape.title = this.id + "_" + feature.id;
	      dShape.X = new Array();
	      dShape.Y = new Array();
	      var vl = featureToDraw.vertices.length;
	      for (i = 0; i < vl; i++) {
	        dShape.X.push(Math.round(geo2Pix(featureToDraw.vertices[i].x, xmin, xmax, 0, this._width)));
	        dShape.Y.push(Math.round(geo2Pix(featureToDraw.vertices[i].y, ymax, ymin, 0, this._height)));
	        if (i>0)
	          var dln = this.drawLine(dShape, dShape.X[i], dShape.Y[i], dShape.X[i-1], dShape.Y[i-1], status);
	        // polygon complete, don't draw the closing point
	        if (i == vl - 1 && featureToDraw.vertices[0].x == featureToDraw.vertices[vl - 1].x && featureToDraw.vertices[0].y == featureToDraw.vertices[vl - 1].y) break;
	        var dp = this.drawPoint(dShape, dShape.X[i], dShape.Y[i], null,  null, status);
	        dp.index = featureToDraw.vertices[i].index;
	        dp.title = this.id + "_" + feature.id;
	      }
	      this.fillPolygon(dShape, status);
	      break;
	  }

    default :
      break;
  }
  return this.docObj;
};

/**
 * Gets the map feature corresponding to a dom object
 * @param dShape in the DOM
 * @return feature map corresponding feature
 */
Display.prototype.getMapFeature = function(dFeature) {
  for (var i = 0; i < this._map.currentLayer.features.length; i++) {
    if (this.id + "_" + this._map.currentLayer.features[i].id == dFeature.id) {
      return this._map.currentLayer.features[i];
    }
  }
};

/**
 * Gets the DOM object corresponding to a map object
 * @param feature in the DOM
 * @return feature map corresponding feature
 */
Display.prototype.getDisplayFeature = function(feature) {
  return xGetElementById(this.id + "_" + feature.id);
};

/**
 * Gets the pointSize from css
 */
Display.prototype.getPointSize = function() {
  if (document.styleSheets) {
    for (var i = 0; i<document.styleSheets.length; i++) {
      if (document.styleSheets.item(i).href.indexOf("dhtml.css") != -1) {
        dhtmlStyleSheet = document.styleSheets[i];
        if (dhtmlStyleSheet.cssRules)
          var rules = dhtmlStyleSheet.cssRules;
        else if (dhtmlStyleSheet.rules)
          var rules = dhtmlStyleSheet.rules;
        for (var j = 0; j < rules.length; j++) {
          if (rules[j].selectorText.indexOf(this._map.id) != -1 &&
            rules[j].selectorText.indexOf(vertexCN + _OFF) != -1) {
            pointSize = rules[j].style.width;
            break;
          }
        }
      }
    }
  }
  return pointSize.substr(0, pointSize.length - 2);
};

/**
 * Handles Display onmouseover events
 * @param evt event
 */
function display_onmouseover(evt) {
  var e = new xEvent(evt);

  var _display = e.target._display; // reference to the display

  var ex = e.pageX - _display._posx;
  var ey = e.pageY - _display._posy;

  if (_display.dragon) return;

  // umo is "Under Mouse Object"
  umo = e.target;

  if (_display.mouseAction && _display.mouseAction.onMouseOver) {
    _display.mouseAction.onMouseOver(_display, ex, ey);
  }
};

/**
 * Handles Display onmousedown events
 * @param evt event
 */
function display_onmousedown(evt) {
  // If left click
  if (((evt.which) && (evt.which == 1)) ||
      ((evt.button) && (evt.button == 1))) {
    display_onmousedown_left(evt);
  } else {
    display_onmousedown_right(evt);
  }
};

/**
 * Handles Display onmousedown events, when left button is clicked
 * @param evt event
 */
function display_onmousedown_left(evt) {
  
  if (document.getElementById('map_imagemap')) {
    document.getElementById('map_rootLayer').removeChild(document.getElementById('map_imagemap'));
  }

  var e = new xEvent(evt);

  var _display = e.target._display; // reference to the display

  var ex = e.pageX - _display._posx;
  var ey = e.pageY - _display._posy;

  window.status = e.target.id

  if (_display.mouseAction && _display.mouseAction.onMouseDown) {
    _display.mouseAction.onMouseDown(_display, ex, ey);
  }
};

/**
 * Handles Display onmousedown events, when right button is clicked
 * @param evt event
 */
function display_onmousedown_right(evt) {
  // TODO: contextual menu?
}

/**
 * Handles Display onmousemove events
 * @param evt event
 */
function display_onmousemove(evt) {
  var e = new xEvent(evt);

  mouse_x = e.pageX;
  mouse_y = e.pageY;

  var _display = e.target._display;// reference to the display


  currentDisplay = _display; // global variable for key events

  var ex = e.pageX - _display._posx;
  var ey = e.pageY - _display._posy;

  geoX = pix2Geo(ex, 0, _display._width, _display._map.extent.xmin, _display._map.extent.xmax);
  geoY = pix2Geo(ey, 0, _display._height, _display._map.extent.ymax, _display._map.extent.ymin);

  var e = new xEvent(evt);

  if (_display.mouseAction && _display.mouseAction.onMouseMove) {
    _display.mouseAction.onMouseMove(_display, ex, ey);
  }

  if (_display._map.onMove) {
    _display._map.onMove(geoX, geoY);
  }
  if (_display._map.onMovePix) {
    _display._map.onMove(ex, ey);
  }
};

/**
 * Handles Display onmousemup events
 * @param evt event
 */
function display_onmouseup(evt) {
  // If left click
  if (((evt.which) && (evt.which == 1)) ||
      ((evt.button) && (evt.button == 1))) {
    display_onmouseup_left(evt);
  } else {
    display_onmouseup_right(evt);
  }
}

/**
 * Handles Display onmousemup events, when left button is cliked
 * @param evt event
 */
function display_onmouseup_left(evt) {
  var e = new xEvent(evt);

  var _display = e.target._display;// reference to the display

  _display.isDrawing = false;

  var ex = e.pageX - _display._posx;
  var ey = e.pageY - _display._posy;

  if (_display.mouseAction && _display.mouseAction.onMouseUp) {
    _display.mouseAction.onMouseUp(_display, ex, ey);
  }

  if (_display._map.onClic) {
    _display._map.onClic(_display.tmpFeature);
  }
  // Emulate dblclick for some browsers
  _display.lastX = ex;
  _display.lastY = ey;
  if ((typeof(dblclick) != "undefined")) {
    if (((Math.abs(_display.oldX - _display.lastX) + Math.abs(_display.oldY - _display.lastY) ) < dbl_click_tol )) {
      display_ondblclick(e, _display);
      if (_display.onDblClic) _display.onDblClic(umo);
    }
  }
  _display.oldX = _display.lastX;
  _display.oldY = _display.lastY;
  dblclick = true;
  window.setTimeout('dblclick=undefined', dbl_click_delay);
};

/**
 * Handles Display onmousemup events, when right button is cliked
 * @param evt event
 */
function display_onmouseup_right(evt) {
}

/**
 * Handles Display ondblclick events
 * Please note that double clicks are emulated
 * @param evt event
 */
function display_ondblclick(evt) {
  var e = new xEvent(evt);

  var _display = e.target._display;// reference to the display

  if (_display.mouseAction && _display.mouseAction.onDblClick) {
    _display.mouseAction.onDblClick(_display);
  }
};

/**
 * Handles Display onmouseout events
 * @param evt event
 */
function display_onmouseout(evt) {
  var e = new xEvent(evt);

  var _display = e.target._display;// reference to the display

  var ex = e.pageX - _display._posx;
  var ey = e.pageY - _display._posy;

  if (_display.dragon) return;

  // umo is "Under Mouse Object"
  umo = e.target;

  if (_display.mouseAction && _display.mouseAction.onMouseOut) {
    _display.mouseAction.onMouseOut(_display, ex, ey);
  }
};

/**
 * Handles Display onkeydown events
 * @param evt event
 */
function display_onkeydown(evt) {

  e = new xEvent(evt);

  // no display selected (mouse moved on)
  if (typeof currentDisplay == 'undefined')
    return;

  var _display = currentDisplay;// reference to the display currently in use

/*
13: enter
27: escape
*/
  // key enter is pressed
  if (e.keyCode == 13) {
    if (_display.mouseAction && _display.mouseAction.onKeyEnter) {
      _display.mouseAction.onKeyEnter(_display);
    }
  } else if (e.keyCode == 27) {
    if (_display._map.onCancel)
      _display._map.onCancel();
    if (_display.mouseAction && _display.mouseAction.onKeyEscape) {
      _display.mouseAction.onKeyEscape(_display);
    }
  }
};

/**
 * Return true if polygon is closed
 * @param aPolygon
 */
function isClosedPolygon(aPolygon) {
  return (aPolygon.getObjectClass() == 'Feature' &&
      aPolygon.type == 'polygon' &&
      aPolygon.vertices[0].x == aPolygon.vertices[aPolygon.vertices.length - 1].x &&
      aPolygon.vertices[0].y == aPolygon.vertices[aPolygon.vertices.length - 1].y)
};

/**
 * Change the status of a node (and its children)
 * @param node object
 * @param new status string value
 * @param boolean walk tree to change children status
 * @param boolean if true, change status even if current status  = "selected"
 */
function changeStatus(node, status, recursive, force) {
  if (node == null)
    return;
  if (recursive)
    xWalkTree(node, function(children) {setNodeStatus(children, status, force);});
  else
    this.setNodeStatus(node, status, force);
};

/**
 * Set the status of a node (and its children)
 * @param node object
 * @param new status string value
 * @param boolean if true, change status even if current status  = "selected"
 */
function setNodeStatus(node, status, force) {
  var oldStatus = "";
  var cn = node.className;
  if (cn.indexOf("_over") != -1) oldStatus = "_over";
  else if (cn.indexOf(_SEL) != -1) oldStatus = _SEL;
  else if (cn.indexOf(_OFF) != -1) oldStatus = _OFF;
  if (node.className.indexOf(layerCN) != -1) return;
  if (cn.indexOf(_SEL) == -1 || force)
    node.className = cn.substr(0, cn.indexOf(oldStatus)) + status;
};


/******************************************/
/* generic functions                      */
/******************************************/
/**
 * emulate the Array push method if not implemented
 */
if(typeof Array.prototype.push == 'undefined')
  Array.prototype.push = function(){
    var i = 0;
    b = this.length, a = arguments;
    for(i;i<a.length;i++)this[b+i] = a[i];
    return this.length
  };

/**
 * Returns the class of the argument or undefined if it's not a valid JavaScript
 * object.
 */
Object.prototype.getObjectClass = function() {
  if (this && this.constructor && this.constructor.toString) {
    var arr = this.constructor.toString().match(/function\s*(\w+)/);
    return arr && arr.length == 2 ? arr[1] : undefined;
  } else {
    return undefined;
  }
};

/**
 * converts pixel coordinates to geo
 * @param pixPos coordinate in pixel
 * @param pixMin minimum pix coordinate
 * @param pixMax maximum pix coordinate
 * @param geoMin minimum geo coordinate
 * @param geoMax maximum geo coordinate
 */
function pix2Geo(pixPos, pixMin, pixMax, geoMin, geoMax) {
  return geoMin + (pixPos - pixMin) * (geoMax - geoMin) / (pixMax - pixMin);
};

/**
 * converts geo coordinates to pix
 * @param geoPos coordinate in geo
 * @param pixMin minimum pix coordinate
 * @param pixMax maximum pix coordinate
 * @param geoMin minimum geo coordinate
 * @param geoMax maximum geo coordinate
 */
function geo2Pix(geoPos, geoMin, geoMax, pixMin, pixMax) {
  return pixMin + (geoPos - geoMin) * (pixMax - pixMin) / (geoMax - geoMin);
};

function integer_compare(x, y) {
    return (x < y) ? -1 : ((x > y)*1);
};

/**
 * implementation of php sprintf like function
 * http://jan.moesen.nu/
 */
function sprintf() {
  if (!arguments || arguments.length < 1 || !RegExp) {
    return;
  }
  var str = arguments[0];
  var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
  var a = b = [], numSubstitutions = 0, numMatches = 0;
  while (a = re.exec(str)) {
    var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
    var pPrecision = a[5], pType = a[6], rightPart = a[7];

    //alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

    numMatches++;
    if (pType == '%')
      subst = '%';
    else {
      numSubstitutions++;
      if (numSubstitutions >= arguments.length) {
        alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
      }
      var param = arguments[numSubstitutions];
      var pad = '';
             if (pPad && pPad.substr(0, 1) == "'") pad = leftpart.substr(1, 1);
        else if (pPad) pad = pPad;
      var justifyRight = true;
             if (pJustify && pJustify === "-") justifyRight = false;
      var minLength = -1;
             if (pMinLength) minLength = parseInt(pMinLength);
      var precision = -1;
             if (pPrecision && pType == 'f') precision = parseInt(pPrecision.substring(1));
      var subst = param;
             if (pType == 'b') subst = parseInt(param).toString(2);
        else if (pType == 'c') subst = String.fromCharCode(parseInt(param));
        else if (pType == 'd') subst = parseInt(param) ? parseInt(param) : 0;
        else if (pType == 'u') subst = Math.abs(param);
        else if (pType == 'f') subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision): parseFloat(param);
        else if (pType == 'o') subst = parseInt(param).toString(8);
        else if (pType == 's') subst = param;
        else if (pType == 'x') subst = ('' + parseInt(param).toString(16)).toLowerCase();
        else if (pType == 'X') subst = ('' + parseInt(param).toString(16)).toUpperCase();
    }
    str = leftpart + subst + rightPart;
  }
  return str;
};

function insertAdjacentElement(node, newNode, where) {
    if(where == "before")
        node.parentNode.insertBefore(newNode, node);
    else if(node.nextSibling)
        node.parentNode.insertBefore(newNode, node.nextSibling);
    else
        node.parentNode.appendChild(newNode);
};

function distance2Pts(x1,y1,x2,y2) {
  return (Math.sqrt(Math.pow(x1 - x2, 2) + Math.pow(y1 - y2, 2)));
};

function nullifyProperties(object) {
  for(var prop in object) {
    if(typeof prop == "object")
      nullifyProperties(prop);
    try {
      delete object[prop]
      object[prop] = null;
    }
    catch (e) { }
  }
  try {
    delete object;
    object = null;
  }
  catch (e) { }
};
