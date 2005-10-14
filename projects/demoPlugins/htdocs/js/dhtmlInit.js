/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/**
 * Double clic parameters
 * (DblClick emulation is required for some browsers)
 */
dbl_click_delay = 200; // ms
dbl_click_tol = 3; // pixels
/**
 * Min distance betwwen vertex when drawing lines
 * on mouse move event
 */
vertexDistance = 10;

/**
 *
 */
snappingDistance = 10;

/**
 * class names parameters (in concordance with css styles)
 */
// user defined class names for the drawing elements
layerCN = "layer";
vertexCN = "vertex";
linepointCN = "linepoint";
boxCN = "box";
boxfillCN = "boxfill";
polygonfillCN = "polygonfill";

// user defined status for the drawing elements
_OFF = "_off";
_SEL = "_selected";

/**
 * HTML ids in html page
 */
div_geo_id = "floatGeo"; // Geo coordinates
div_distance_id = "floatDistance"; // distance measure
div_surface_id = "floatSurface"; // surface measure
div_features_num_id = "features_num"; // total number of features
div_inserted_num_id = "inserted_features_num"; // number of inserted features
div_updated_num_id = "modified_features_num"; // number of updated features
div_deleted_num_id = "deleted_features_num"; // number of deleted features

/**
 * User defined functionnalities
 * Function called on window onload event
 */
createMap = function() {
  myform = document.forms['carto_form'];
  
  // create a new map object with a className as argument
  mainmap = new Map("map");
  
  mainmap.geoTag = xGetElementById(div_geo_id);
  if (mainmap.geoTag != null) {
    mainmap.geoUnits = mainmap.geoTag.innerHTML;
    mainmap.geoTag.innerHTML = sprintf(mainmap.geoUnits, "_", "_");
    xShow(mainmap.geoTag);
  }
  mainmap.distanceTag = xGetElementById(div_distance_id);
  if (mainmap.distanceTag != null) {
    mainmap.distanceUnits = mainmap.distanceTag.innerHTML;
  }
  mainmap.surfaceTag = xGetElementById(div_surface_id);
  if (mainmap.surfaceTag != null) {
    mainmap.surfaceUnits = mainmap.surfaceTag.innerHTML;
  }

  initMap();

  mainmap.displayFeaturesCount();
  mainmap.snap("map");
  
  // get the checked tool and its values
  for (var i =0; i < myform.tool.length ; i++) {
    if (myform.tool[i].checked) {
      var func = myform.tool[i].onclick;
      var start = func.toString().indexOf('{');
      var end = func.toString().indexOf('}');
      eval (func.toString().substring(start + 1, end));
    }
  }
  xHide(xGetElementById('loadbarDiv'));
};

/**
 * Store the values (coords and type) in the form
 * Used for the navigation tools (zoomin, zoomout, etc ...)
 * @param aFeature
 */
fillForm = function(aFeature) {
  // TODO let the possibility to send more than one feature
  var coords = new String();
  for (var i=0;i<aFeature.vertices.length;i++) {
    coords += aFeature.vertices[i].x + "," + aFeature.vertices[i].y + ";";
  }
  coords = coords.substring(0, coords.length -1);
  myform.selection_coords.value = coords;
  switch (aFeature.type) {
    case "point" :
      var shapeType = "point";
      break;
    case "polyline" :
      var shapeType = "line";
      break;
    case "polygon" :
      var shapeType = "polygon";
      break;
  }
  myform.selection_type.value = shapeType;
};

/**
 * Empty the inputs (coords and type) in the form
 */
emptyForm = function() {
  myform.selection_coords.value = "";
  myform.selection_type.value = "";
};

/**
 * Fills the feature form input with the edited features of the current layer
 */
storeFeatures = function() {
  for (var i=0;i < mainmap.currentLayer.features.length; i++) {
    var aFeature = mainmap.currentLayer.features[i];
    for (var j=0; j < mainmap.editAttributeNames.length; j++) {
      if (mainmap.editAttributeTypes[j] == "")
      	continue;
      var input = eval("myform['edit_feature_" + aFeature.id + "[" + mainmap.editAttributeNames[j] + "]']");
      if (!validateFormInput(mainmap.editAttributeTypes[j], input.value)) {
        return false;
      }
    }
    if (aFeature.operation != 'undefined') {
      // store geometry
      createInput(myform, "edit_feature_" + aFeature.id + "[WKTString]", aFeature.getWKT(), true);
      // store operation
      createInput(myform, "edit_feature_" + aFeature.id + "[operation]", aFeature.operation, true);
    }
  }
  return true;
};

/**
 * Store the feature operation in the form
 */
setFeatureOperation = function(id, operation) {
  for (var i = 0 ; i < mainmap.currentLayer.features.length ; i++) {
    if (mainmap.currentLayer.features[i].id == id) {
      mainmap.currentLayer.features[i].operation = operation;
      continue;
    }
  }
  mainmap.displayFeaturesCount();
  createInput(myform, "edit_feature_" + id + "[operation]", operation, true);
};

/**
 * Creates an form input
 * @param form form name
 * @param name name of the input
 * @param value value of the input
 */
createInput = function(elt, name, value, hidden) {
  var myInput = xCreateElement('input');
  myInput.type = (hidden) ? "hidden" : "text";
  myInput.name = name;
  xAppendChild(elt, myInput);
  myInput.value = value;
}

/**
 * Submits the form
 */
doSubmit = function() {
  xShow(xGetElementById('loadbarDiv'));
  myform = document.forms['carto_form'];
  myform.submit();
};

EventManager.Add(window, 'load', createMap, false);

Map.prototype.snap = function(aDisplay) {
  this.getDisplay(aDisplay).useSnapping =
    (typeof myform['snapping'] != "undefined" && myform['snapping'].checked)? true : false;
};

Map.prototype.resetMapEventHandlers = function() {
  this.onFeatureInput = undefined;
  this.onClic = undefined;
  this.onSelPoint = undefined;
  this.onFeatureChange = undefined;
  this.onNewFeature = undefined;
  this.onCancel = function() {
    createMap();
  }
  this.onMove = function(geoX, geoY) {
    // display geo coordinates
    if (this.geoTag)
      this.geoTag.innerHTML = sprintf(this.geoUnits, Math.round(geoX), Math.round(geoY));
  }
};
  
Map.prototype.displayFeaturesCount = function() {
  var div_features_num = xGetElementById(div_features_num_id);
  var div_inserted_num = xGetElementById(div_inserted_num_id);
  var div_updated_num = xGetElementById(div_updated_num_id);
  var div_deleted_num = xGetElementById(div_deleted_num_id);
  this.updateFeaturesCount();
  if (div_features_num != null)
    div_features_num.innerHTML = this.featuresNum;
  if (div_inserted_num != null)
    div_inserted_num.innerHTML = this.insertedNum;
  if (div_updated_num != null)
    div_updated_num.innerHTML = this.updatedNum;
  if (div_deleted_num != null)
    div_deleted_num.innerHTML = this.deletedNum;
};

/**
 * Tools specific functionnalities
 */
/***** LOCATION ****/
Map.prototype.zoomout = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('sel.point');
  this.onSelPoint = function(x, y) {
    myform.selection_coords.value = x + "," + y;
    myform.selection_type.value = "point";
    storeFeatures();
    doSubmit();
  }
};
  
Map.prototype.zoomin = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('sel.box');
  this.onSelBox = function(x1, y1, x2, y2) {
    myform.selection_coords.value = x1 + "," + y1 + ";" + x2 + "," + y2;
    myform.selection_type.value = "rectangle";
    storeFeatures();
    doSubmit();
  }
};

Map.prototype.query = function(aDisplay) {
  this.zoomin(aDisplay);
  this.getDisplay(aDisplay).docObj.style.cursor = "help";
};

Map.prototype.rollover = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('rollover');
  this.onImagemapOver = function(id) {
    id = id.split("_");
    var layer = eval("layer_" + id[0]);
    var featureId = id[1];
    
    if (typeof layer != "undefined") {
      var attributesTitle = layer.attributes.split(",");
      for(var i = 0; i < layer.features.length; i++) {
        if (layer.features[i].id == featureId) {
          var feature = layer.features[i];
          continue;
        }
      }
      if (typeof feature == 'undefined') return;
      imagemapToolTip.innerHTML = "";
      for (var j = 0; j < attributesTitle.length; j++) {
        imagemapToolTip.innerHTML += attributesTitle[j] + ' : ';
        imagemapToolTip.innerHTML += feature.values[j];
        if (j != attributesTitle.length)
          imagemapToolTip.innerHTML += '<br />';
      }
    }
    xMoveTo(imagemapToolTip, mouse_x + 10, mouse_y + 10);
    imagemapToolTip.style.display = 'block';
  }
  this.onImagemapOut = function() {
    imagemapToolTip.style.display = 'none';
  }
};
  
Map.prototype.pan = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('pan');
  mainmap.onPan = function(x, y) {
    myform.selection_coords.value = x + "," + y;
    myform.selection_type.value = "point";
    storeFeatures();
    doSubmit();
  }
};

/***** STATICTOOLS ****/
Map.prototype.distance = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.line');
  this.getDisplay(aDisplay).useSnapping = false;
  this.onClic = function(aFeature) {
    var distance = aFeature.getLength();
    distance = (factor == 1000) ? Math.round(distance /1000 * 100) / 100 : Math.round(distance);
    this.distanceTag.innerHTML = sprintf(this.distanceUnits, distance);
    this.distanceTag.style.display = "block";
  }
  this.onNewFeature = function(aFeature) {
    for (var i = 0; i < this.currentLayer.features.length; i++) {
      if (this.currentLayer.features[i].operation == "") {
        dShape = this.getDisplay(aDisplay).getDisplayFeature(this.currentLayer.features[i]);
        dShape.innerHTML = "";
      }
    }
  }
  this.onFeatureInput = function(aFeature) {
    aFeature.operation = "";
  }
};
 
Map.prototype.surface = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.poly');
  this.getDisplay(aDisplay).useSnapping = false;
  this.onClic = function(aFeature) {
    var surface = aFeature.getArea();
    surface = (factor == 1000) ? Math.round(surface / 1000000 * 10000) / 10000 : Math.round(surface);
    this.surfaceTag.innerHTML = sprintf(this.surfaceUnits, surface);
    this.surfaceTag.style.display = "block";
  }
  this.onNewFeature = function(aFeature) {
    for (var i = 0; i < this.currentLayer.features.length; i++) {
      if (this.currentLayer.features[i].operation == "") {
        dShape = this.getDisplay(aDisplay).getDisplayFeature(this.currentLayer.features[i]);
        dShape.innerHTML = "";
      }
    }
  }
  this.onFeatureInput = function(aFeature) {
    aFeature.operation = "";
  }
};
/***** OUTLINE ****/
Map.prototype.outline_poly = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.poly');
  this.onFeatureInput = function(aFeature) {
    addLabel(polyDefaultLabel, mouse_x, mouse_y);
    fillForm(aFeature);
  };
  this.onCancel = function() {
    hideLabel();
    emptyForm();
  };
};

Map.prototype.outline_line = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.line');
  this.onFeatureInput = function(aFeature) {
    addLabel(lineDefaultLabel, mouse_x, mouse_y);
    fillForm(aFeature);
  };
  this.onCancel = function() {
    hideLabel();
    emptyForm();
  };
};

Map.prototype.outline_rectangle = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.box');
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    addLabel(rectangleDefaultLabel, mouse_x, mouse_y);
    fillForm(aFeature);
  };
  this.onCancel = function() {
    hideLabel();
    emptyForm();
  };
};
  
Map.prototype.outline_point = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.point');
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    addLabel(pointDefaultLabel, mouse_x, mouse_y);
    fillForm(aFeature);
  };
  this.onCancel = function() {
    hideLabel();
    emptyForm();
  };
};

/***** EDIT ****/
Map.prototype.edit_point = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.point');
  this.onFeatureInput = function(aFeature) {
    this.displayFeaturesCount();
    var editLayer = myform['edit_layer'].value;
    var editTable = xGetElementById(editLayer + "_table");
    editTableAddRow(editTable, aFeature);
  }
};

Map.prototype.edit_poly = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.poly');
  this.onFeatureInput = function(aFeature) {
    this.displayFeaturesCount();
    var editLayer = myform['edit_layer'].value;
    var editTable = xGetElementById(editLayer + "_table");
    editTableAddRow(editTable, aFeature);
  }
};

Map.prototype.edit_line = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.line');
  this.onFeatureInput = function(aFeature) {
    this.displayFeaturesCount();
    var editLayer = myform['edit_layer'].value;
    var editTable = xGetElementById(editLayer + "_table");
    editTableAddRow(editTable, aFeature);
  }
};

Map.prototype.edit_box = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('draw.box');
  this.onFeatureInput = function(aFeature) {
    this.displayFeaturesCount();
  }
};
 
Map.prototype.edit_move = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('move');
  this.onFeatureChange = function(aFeature) {
    this.displayFeaturesCount();
  }
  this.onFeatureSelected = function(aFeature) {
    for (i = 0; i < myform.edit_feature_selected.length; i++) {
    if (myform.edit_feature_selected[i].value == aFeature.id)
      myform.edit_feature_selected[i].checked = "checked";
    }
  }
};
  
Map.prototype.edit_sel = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('sel.point');
  this.onSelPoint = function(x, y) {
    myform.selection_coords.value = x + "," + y;
    myform.selection_type.value = "point";
    storeFeatures();
    doSubmit();
  }

};
  
Map.prototype.edit_del_vertex = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('delete.vertex');
  this.onFeatureChange = function(aFeature) {
    this.displayFeaturesCount();
  }
};
  
Map.prototype.edit_add_vertex = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('add.vertex');
  this.onFeatureChange = function(aFeature) {
    this.displayFeaturesCount();
  }
};
  
Map.prototype.edit_del_feature = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('delete.feature');
  this.onFeatureChange = function(aFeature) {
    this.displayFeaturesCount();
  }
};

Map.prototype.onUnselectFeatures = function() {
  uncheckFeaturesRadios();
}

Map.prototype.handleEditTable = function() {
  for (var i = 0; i < this.currentLayer.features.length; i++) {
    var id = this.currentLayer.features[i].id;
    var tableRow = xGetElementById("tr_" + id);
    tableRow.onmouseover = function() {
      this.style.backgroundColor = 'red';
      var prefix = "tr_";
      var id = this.id.substr(prefix.length);
      hilightFeature(id);
    }
    tableRow.onmouseout = function() {
      this.style.backgroundColor = '';
      hilightFeature(false);
    }
    tableRow.onclick = function() {
      var prefix = "tr_";
      var id = this.id.substr(prefix.length);
      selectEditFeature(id);
    }
  }
};


function Table(obj) {
  return obj;
}

/******************************************/
/* generic functions                      */
/******************************************/

function hilightFeature(id, hide) {
  window.status = id;
  if (id) {
    for (var i = 0 ; i < mainmap.currentLayer.features.length; i++) {
      if (mainmap.currentLayer.features[i].id == id) {
        var feature = mainmap.currentLayer.features[i];
        continue;
      }
    }
    dShape = mainmap.getDisplay("map").getDisplayFeature(feature);
    changeStatus(dShape, _SEL, true, true);
  } else if (typeof dShape != "undefined" && dShape != null) {
    if ( !(mainmap.currentEditFeature != null
        && dShape.id.substr((mainmap.id + "_").length) == mainmap.currentEditFeature.id)) {
      changeStatus(dShape, _OFF, true, true);
    }
  }
}

function darkFeature(id) {
  for (var i = 0 ; i < mainmap.currentLayer.features.length; i++) {
    if (mainmap.currentLayer.features[i].id == id) {
      var feature = mainmap.currentLayer.features[i];
      continue;
    }
  }
  dShape = mainmap.getDisplay("map").getDisplayFeature(feature);
  changeStatus(dShape, _OFF, true, true);
}

function selectEditFeature(id) {
  if (id) {
    var radArray = myform.edit_feature_selected;
    if (typeof radArray.length == "undefined") {// only one radio
      radArray.checked = "checked";
    }
    else {
      for (i = 0; i < radArray.length; i++) {
        if (radArray[i].value == id)
          radArray[i].checked = "checked";
      }
    }
    if (mainmap.currentEditFeature != null)
      // dark previous selected feature
        darkFeature(mainmap.currentEditFeature.id);
    for (var i = 0 ; i < mainmap.currentLayer.features.length; i++) {
      var feature = mainmap.currentLayer.features[i];
      // hiligth feature
      if (mainmap.currentLayer.features[i].id == id) {
        mainmap.currentEditFeature = feature;
        hilightFeature(id);
      }
    }
  }
}



function uncheckFeaturesRadios() {
    radArray = myform.edit_feature_selected;
    if (typeof radArray.length == "undefined") // only one radio
      radArray.checked = false;
    for (i = 0; i < radArray.length; i++) {
        radArray[i].checked = false;
    }
}

/**
 * Adds a row to the edit features table
 */
function editTableAddRow(table, aFeature) {
  var tbody = xGetElementsByTagName('tbody', table)[0];
  var row = xCreateElement("tr");
  // hilight table row and display feature
  row.onmouseover = function() {
    this.style.backgroundColor = "red";
    hilightFeature(aFeature.id);
  }
  row.onmouseout = function() {
    this.style.backgroundColor = "";
    hilightFeature(false);
  }
  // fill the table row with cells and corresponding input forms
  // one cell for the id
  var td = xCreateElement("td");
  xAppendChild(row, td);
  for (var i = 0; i < mainmap.editAttributeNames.length; i++) {
    var td = xCreateElement("td");
    if (mainmap.editAttributeTypes[i] == 'string' || mainmap.editAttributeTypes[i] == 'integer') {
      var input = xCreateElement("input");
      xAppendChild(td, input);
      input.name = "edit_feature_" + aFeature.id + "[" + mainmap.editAttributeNames[i] + "]";
    } else {
      td.innerHTML = "";
    }
    xAppendChild(row, td);
  }
  xAppendChild(tbody, row);
}

function validateFormInput(type, value) {
  if (type == "integer") {
    if (isNaN(value)) {
      alert ("attention !\n '" + value + "' is not an integer");
      return false;
    }
      
  }
  return true;
}