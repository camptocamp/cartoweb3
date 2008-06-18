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
  
  // initial selected tool
  if (typeof cw3_initial_selected_tool != "undefined") {
    // prevent interface failure if last selected tool was pdfrotate, set it to default zoomin
    // FIXME replace this hack by a check in ClientExportPdf.php, maybe in Initialize() or so
    cw3_initial_selected_tool = cw3_initial_selected_tool.replace(/pdfrotate/g, "zoomin");
    eval (cw3_initial_selected_tool);
  }
  
  xHide(xGetElementById('loadbarDiv'));
};

/**
 * Store the values (coords and type) in the form
 * Used for the navigation tools (zoomin, zoomout, etc ...)
 * @param aFeature
 */
fillForm = function(aFeature) {
  if (typeof(aFeature) == 'undefined') {
      return; // prevents from an error when pressing enter in the label input
  }

  // TODO let the possibility to send more than one feature
  var coords = new String();
  for (var i=0;i<aFeature.vertices.length;i++) {
    coords += aFeature.vertices[i].x + "," + aFeature.vertices[i].y + ";";
  }
  coords = coords.substring(0, coords.length -1);
  switch (aFeature.type) {
    case "point" :
      var shapeType = "point";
      break;
    case "polyline" :
      var shapeType = "polyline";
      break;
    case "polygon" :
      var shapeType = "polygon";
      break;
    case "circle" :
      var shapeType = "circle";
      coords += ";" + aFeature.radius;
      break;
  }
  myform.selection_coords.value = coords;
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
    if (typeof(mainmap.editAttributeNames) != 'undefined') {
      for (var j = 0; j < mainmap.editAttributeNames.length; j++) {
        if (mainmap.editAttributeTypes[j] == "")
            continue;
        var input = eval("myform['edit_feature_" + aFeature.id + "[" + mainmap.editAttributeNames[j] + "]']");
        if (!validateFormInput(mainmap.editAttributeTypes[j], input.value)) {
          return false;
        }
      } 
    }
    if (aFeature.operation != 'undefined') {
      // store geometry
      createInput(myform, "edit_feature_" + aFeature.id + "[WKTString]", aFeature.getWKT(), 'hidden');
      // store operation
      createInput(myform, "edit_feature_" + aFeature.id + "[operation]", aFeature.operation, 'hidden');
    }
  }
  return true;
};

/**
 * Store the feature operation in the form
 */
setFeatureOperation = function(aFeature, operation) {
  aFeature.operation = operation;
  mainmap.displayFeaturesCount();
};

/**
 * Creates an form input
 * @param form form name
 * @param name name of the input
 * @param value value of the input
 */
createInput = function(elt, name, value, type) {

  if (type == 'textarea') {
    var input = createTextarea(name, value);
  } else {
    if (document.all) {
      var str = '<input type="' + type + '" name="' + name + '" value="' + value + '" />';
      var input = xCreateElement(str);
    }
    else {
      var input = xCreateElement("input");
      input.type = type;
      input.name = name;
      input.value = value;
    }
  }
  xAppendChild(elt, input);
  return input;
}

/**
 * Creates an form input
 * @param form form name
 * @param name name of the input
 * @param value value of the input
 */
createTextarea = function(name, value) {
  if (document.all) {
    var str = '<textarea id="' + name + '" name="' + name + '">';
    var input = xCreateElement(str);
  }
  else {
    var input = xCreateElement("textarea");
    input.name = name;
  }
  input.innerHTML = value;
  return input;
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
  if (this.onToolUnset != undefined) {
    this.onToolUnset();
  }
  this.onToolUnset = undefined;
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
Map.prototype.selectionBox = function(aDisplay, ajaxAction) {
  this.resetMapEventHandlers();

  this.setCurrentLayer('drawing');
  this.getDisplay(aDisplay).setTool('sel.box');
  this.onSelBox = function(x1, y1, x2, y2) {
    myform.selection_coords.value = x1 + "," + y1 + ";" + x2 + "," + y2;
    myform.selection_type.value = "rectangle";
    storeFeatures();
    CartoWeb.trigger(ajaxAction, "doSubmit()");
  }
};

Map.prototype.selectionPoint = function(aDisplay, ajaxAction) {
  this.resetMapEventHandlers();
  
  this.setCurrentLayer('drawing');
  this.getDisplay(aDisplay).setTool('sel.point');
  this.onSelPoint = function(x, y) {
    myform.selection_coords.value = x + "," + y;
    myform.selection_type.value = "point";
    storeFeatures();
    CartoWeb.trigger(ajaxAction, "doSubmit()");
  }
}

/***** LOCATION ****/
Map.prototype.zoomout = function(aDisplay) {
  this.selectionPoint(aDisplay, 'Location.Zoom');
};

Map.prototype.zoomin = function(aDisplay) {
  this.selectionBox(aDisplay, 'Location.Zoom');
};

Map.prototype.fullextent = function(aDisplay) {
  doSubmit();
}

Map.prototype.pan = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('pan');
  mainmap.onPan = function(x, y) {
    myform.selection_coords.value = x + "," + y;
    myform.selection_type.value = "point";
    storeFeatures();
    CartoWeb.trigger('Location.Pan', "doSubmit()", {source: 'map'});
  }
};

Map.prototype.query_by_point = function(aDisplay) {
  this.selectionPoint(aDisplay, 'Query.Perform');
  this.getDisplay(aDisplay).docObj.style.cursor = "help";
};

Map.prototype.query_by_bbox = function(aDisplay) {
  this.selectionBox(aDisplay, 'Query.Perform');
  this.getDisplay(aDisplay).docObj.style.cursor = "help";
};
  
Map.prototype.query_by_polygon = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('drawing');
  this.getDisplay(aDisplay).setTool('draw.poly');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    CartoWeb.trigger('Query.Perform', "doSubmit()");
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('drawing');
    this.onCancel();
  };
  this.onCancel = function() {
    emptyForm();
  };
};

Map.prototype.query_by_circle = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('drawing');
  this.getDisplay(aDisplay).setTool('draw.circle');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    CartoWeb.trigger('Query.Perform', "doSubmit()");
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('drawing');
    this.onCancel();
  };
  this.onCancel = function() {
    emptyForm();
  };
};

/***** STATICTOOLS ****/
Map.prototype.distance = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('distance');
  this.getDisplay(aDisplay).setTool('draw.line');
  this.getDisplay(aDisplay).useSnapping = false;
  this.onClic = function(aFeature) {
    var distance = aFeature.getLength();
    distance = (factor == 1000) ? Math.round(distance /1000 * 100) / 100 : Math.round(distance);
    this.distanceTag.innerHTML = sprintf(this.distanceUnits, distance);
    this.distanceTag.style.display = "block";
    if (this.distanceTag.style.position == "absolute")
      xMoveTo(this.distanceTag, mouse_x, mouse_y);
  }
  this.onNewFeature = function(aFeature) {
    this.onToolUnset();
  };
  this.onFeatureInput = function(aFeature) {
    aFeature.operation = "";
  };
  this.onToolUnset = function() {
    //clear the distance's display layer
    this.getDisplay(aDisplay).clearLayer('distance');
    this.onCancel();
  };
  this.onCancel = function(aFeature) {
    this.distanceTag.style.display = "none";
  };
};
 
Map.prototype.surface = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('surface');  
  this.getDisplay(aDisplay).setTool('draw.poly');
  this.getDisplay(aDisplay).useSnapping = false;
  this.onClic = function(aFeature) {
    if (typeof aFeature == 'undefined') {
        return;
    }
    var surface = aFeature.getArea();
    surface = (factor == 1000) ? Math.round(surface / 1000000 * 10000) / 10000 : Math.round(surface);
    this.surfaceTag.innerHTML = sprintf(this.surfaceUnits, surface);
    this.surfaceTag.style.display = "block";
    if (this.surfaceTag.style.position == "absolute")
      xMoveTo(this.surfaceTag, mouse_x, mouse_y);
  }
  this.onNewFeature = function(aFeature) {
    this.onToolUnset();
  };
  this.onFeatureInput = function(aFeature) {
    aFeature.operation = "";
  };
  this.onToolUnset = function() {
    //clear the surface's display layer
    this.getDisplay(aDisplay).clearLayer('surface');
    this.onCancel();
  };
  this.onCancel = function(aFeature) {
    this.surfaceTag.style.display = "none";
  };
};
/***** OUTLINE ****/
Map.prototype.outline_circle = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('outline_poly');
  this.getDisplay(aDisplay).setTool('draw.circle');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    if (typeof addLabel == 'undefined')
      doSubmit();
    else
      addLabel(circleDefaultLabel, mouse_x, mouse_y);
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('outline_poly');
    this.onCancel();
  };
  this.onCancel = function() {
    if (typeof hideLabel != 'undefined')
      hideLabel();
    emptyForm();
  };
};

Map.prototype.outline_poly = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('outline_poly');
  this.getDisplay(aDisplay).setTool('draw.poly');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    if (typeof addLabel == 'undefined')
      doSubmit();
    else
      addLabel(polyDefaultLabel, mouse_x, mouse_y);
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('outline_poly');
    this.onCancel();
  };
  this.onCancel = function() {
    if (typeof hideLabel != 'undefined')
      hideLabel();
    emptyForm();
  };
};

Map.prototype.outline_line = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('outline_line');
  this.getDisplay(aDisplay).setTool('draw.line');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    if (typeof addLabel == 'undefined')
      doSubmit();
    else
      addLabel(lineDefaultLabel, mouse_x, mouse_y);
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('outline_line');
    this.onCancel();
  };
  this.onCancel = function() {
    if (typeof hideLabel != 'undefined')
      hideLabel();
    emptyForm();
  };
};

Map.prototype.outline_rectangle = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('outline_rectangle');
  this.getDisplay(aDisplay).setTool('draw.box');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    if (typeof addLabel == 'undefined')
      doSubmit();
    else
      addLabel(rectangleDefaultLabel, mouse_x, mouse_y);
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('outline_rectangle');
    this.onCancel();
  };
  this.onCancel = function() {
    if (typeof hideLabel != 'undefined')
      hideLabel();
    emptyForm();
  };
};
  
Map.prototype.outline_point = function(aDisplay) {
  this.resetMapEventHandlers();
  this.setCurrentLayer('outline_point');
  this.getDisplay(aDisplay).setTool('draw.point');

  this.onNewFeature = function(aFeature) {
      this.onToolUnset();
  };
  this.onFeatureInput = this.onFeatureChange = function(aFeature) {
    fillForm(aFeature);
    if (typeof addLabel == 'undefined')
      doSubmit();
    else
      addLabel(pointDefaultLabel, mouse_x, mouse_y);
  };
  this.onToolUnset = function() {
    //clear the outline_poly's display layer
    this.getDisplay(aDisplay).clearLayer('outline_point');
    this.onCancel();
  };
  this.onCancel = function() {
    if (typeof hideLabel != 'undefined')
      hideLabel();
    emptyForm();
  };
};

/**** adjustMapsize ****/

frameWidth = function() {

    if (window.innerWidth)
        return window.innerWidth;
    else if (document.body && document.body.offsetWidth)
        return document.body.offsetWidth;
    else
        return 0;
};

frameHeight = function() {

    if (window.innerHeight) 
        return window.innerHeight;
    else if (document.body && document.body.offsetHeight)
        return document.body.offsetHeight;
    else
        return 0;
};

Map.prototype.adjust_mapsize = function(aDisplay) {
    
    /**** header (200) - layertree (300) ****/

    var h = frameHeight() - 200;
    var w = frameWidth() - 330;

    cartoForm = document.forms['carto_form'];

    cartoForm.customMapsize.value = w + "x" + h;

    doSubmit();
}

Map.prototype.linkit = function(aDisplay) {
    var linkbox = xGetElementById('linkItBox');
    if (linkbox.style.display == 'none') {
        // warning: requires prototypejs lib
        new Ajax.Request(linkItRequestUrl, {
          method: 'get',
          onSuccess: function(transport) {
            linkbox.innerHTML = transport.responseText;
            xGetElementById('linkItUrl').select();
          }
        });
        
        linkbox.style.display = 'block';
    } else {
        linkItClose();
    }
}
