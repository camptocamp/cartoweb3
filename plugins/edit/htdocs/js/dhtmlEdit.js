/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/***** EDIT ****/

Map.prototype.edit_point = function(aDisplay) {
  this.resetMapEventHandlers();
  
  // new feature not allowed
  if (typeof insert_feature_max_num != 'undefined'
      && this.insertedNum >= insert_feature_max_num) {
    var notAllowed = true;
  }
  switch (notAllowed) {
    case true:
      if (this.currentEditFeature == null
          || this.currentEditFeature.vertices != 0) {
        var button = xGetElementById('edit_point');
        if (button != null) {
          xGetElementById('edit_point').disabled = true;
        }
        this.getDisplay(aDisplay).setTool('move');
    
        var button = xGetElementById('edit_move');
        if (button != null) {
          button.checked = true;
        } else {
          setActiveToolButton('edit_move');
        }
        return false;
        break;
      }
    case false:
      var button = xGetElementById('edit_point');
      if (button != null) {
        button.disabled = false;
        button.checked = true;
      } else {
        setActiveToolButton('edit_point');
        xGetElementById('tool').value = 'edit_point';
      }
      break;
  }
  
  this.getDisplay(aDisplay).setTool('draw.point');
  this.onNewFeature = function(aFeature) {
    aFeature.operation = 'insert';
    if (this.currentEditFeature != null &&
      this.currentEditFeature.vertices == 0) {
      aFeature.id = this.currentEditFeature.id;
      setFeatureOperation(aFeature,"update");
    } else {
      this.currentEditFeature = null;
    }
  }
  this.onFeatureInput = function(aFeature) {
    if (this.currentEditFeature != null &&
      this.currentEditFeature.vertices == 0) {
      // feature with attributes but no geometry, update geometry with geometry of newly drawn object
      for (var i = 0; i < this.currentLayer.features.length; i ++) {
        if (this.currentLayer.features[i].id == this.currentEditFeature.id) {
          this.currentLayer.features[i].vertices = aFeature.vertices;
          this.currentLayer.features[i].operation = aFeature.operation;
        }
      }
      this.updateFeaturesCount();
      this.displayFeaturesCount();
      this.currentEditFeature = null;
    } else {
      // new feature
      this.currentLayer.features.push(aFeature);
      this.updateFeaturesCount();
      this.displayFeaturesCount();
      var editLayer = myform['edit_layer'].value;
      var editTable = xGetElementById("edit_table");
      this.editTableAddRow(editTable, aFeature);
      var editDiv = xGetElementById("edit_div");
      editDiv.style.display = "block";
    }
    // reset the tool
    mainmap.edit_point('map');
//    uncheckFeaturesRadios();
  }
};

Map.prototype.edit_polygon = function(aDisplay) {
  this.resetMapEventHandlers();
  
  // new feature not allowed
  if (typeof insert_feature_max_num != 'undefined'
      && this.insertedNum >= insert_feature_max_num) {
    var notAllowed = true;
  }
  switch (notAllowed) {
    case true:
      if (this.currentEditFeature == null
          || this.currentEditFeature.vertices != 0) {
        var button = xGetElementById('edit_polygon');
        if (button != null) {
          xGetElementById('edit_polygon').disabled = true;
        }
        this.getDisplay(aDisplay).setTool('move');
    
        var button = xGetElementById('edit_move');
        if (button != null) {
          button.checked = true;
        } else {
          setActiveToolButton('edit_move');
        }
        return false;
        break;
      }
    case false:
      var button = xGetElementById('edit_polygon');
      if (button != null) {
        button.disabled = false;
        button.checked = true;
      } else {
        setActiveToolButton('edit_polygon');
        xGetElementById('tool').value = 'edit_polygon';
      }
      break;
  }
  
  this.getDisplay(aDisplay).setTool('draw.poly');
  this.onNewFeature = function(aFeature) {
    aFeature.operation = 'insert';
    if (this.currentEditFeature == null
        && typeof insert_feature_max_num != 'undefined'
        && this.insertedNum >= insert_feature_max_num) {
      alert ("insert new feature not allowed");
      return false;
    }
    if (this.currentEditFeature != null &&
      this.currentEditFeature.vertices == 0) {
      aFeature.id = this.currentEditFeature.id;
      aFeature.operation = "update";
    } else {
      this.currentEditFeature = null;
    }
  }
  this.onFeatureInput = function(aFeature) {
    if (this.currentEditFeature != null &&
      this.currentEditFeature.vertices == 0) {
      // feature with attributes but no geometry, update geometry with geometry of newly drawn object
      for (var i = 0; i < this.currentLayer.features.length; i ++) {
        if (this.currentLayer.features[i].id == this.currentEditFeature.id) {
          this.currentLayer.features[i].vertices = aFeature.vertices;
          this.currentLayer.features[i].operation = aFeature.operation;
        }
      }
      this.updateFeaturesCount();
      this.displayFeaturesCount();
      this.currentEditFeature = null;
    } else {
      // new feature
      this.currentLayer.features.push(aFeature);
      this.updateFeaturesCount();
      this.displayFeaturesCount();
      var editLayer = myform['edit_layer'].value;
      var editTable = xGetElementById("edit_table");
      this.editTableAddRow(editTable, aFeature);
      var editDiv = xGetElementById("edit_div");
      editDiv.style.display = "block";
    }
    // reset the tool
    mainmap.edit_polygon('map');
//    uncheckFeaturesRadios();
  }
};

Map.prototype.edit_line = function(aDisplay) {
  this.resetMapEventHandlers();
  
  // new feature not allowed
  if (typeof insert_feature_max_num != 'undefined'
      && this.insertedNum >= insert_feature_max_num) {
    var notAllowed = true;
  }
  switch (notAllowed) {
    case true:
      if (this.currentEditFeature == null
          || this.currentEditFeature.vertices != 0) {
        var button = xGetElementById('edit_line');
        if (button != null) {
          xGetElementById('edit_line').disabled = true;
        }
        this.getDisplay(aDisplay).setTool('move');
    
        var button = xGetElementById('edit_move');
        if (button != null) {
          button.checked = true;
        } else {
          setActiveToolButton('edit_move');
        }
        return false;
        break;
      }
    case false:
      var button = xGetElementById('edit_line');
      if (button != null) {
        button.disabled = false;
        button.checked = true;
      } else {
        setActiveToolButton('edit_line');
        xGetElementById('tool').value = 'edit_line';
      }
      break;
  }
  
  this.getDisplay(aDisplay).setTool('draw.line');
  this.onNewFeature = function(aFeature) {
    aFeature.operation = 'insert';
    if (this.currentEditFeature != null &&
      this.currentEditFeature.vertices == 0) {
      aFeature.id = this.currentEditFeature.id;
      aFeature.operation = "update";
    } else {
      this.currentEditFeature = null;
    }
  }
  this.onFeatureInput = function(aFeature) {
    if (this.currentEditFeature != null &&
      this.currentEditFeature.vertices == 0) {
      // feature with attributes but no geometry, update geometry with geometry of newly drawn object
      for (var i = 0; i < this.currentLayer.features.length; i ++) {
        if (this.currentLayer.features[i].id == this.currentEditFeature.id) {
          this.currentLayer.features[i].vertices = aFeature.vertices;
          this.currentLayer.features[i].operation = aFeature.operation;
        }
      }
      this.updateFeaturesCount();
      this.displayFeaturesCount();
      this.currentEditFeature = null;
    } else {
      // new feature
      this.currentLayer.features.push(aFeature);
      this.updateFeaturesCount();
      this.displayFeaturesCount();
      var editLayer = myform['edit_layer'].value;
      var editTable = xGetElementById("edit_table");
      this.editTableAddRow(editTable, aFeature);
      var editDiv = xGetElementById("edit_div");
      editDiv.style.display = "block";
    }
    // reset the tool
    mainmap.edit_line('map');
//    uncheckFeaturesRadios();
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
    selectEditFeature(aFeature.id);
  }
};
  
Map.prototype.edit_sel = function(aDisplay) {
  this.resetMapEventHandlers();
  this.getDisplay(aDisplay).setTool('sel.box');
  this.onSelBox = function(x1, y1, x2, y2) {
    myform.selection_coords.value = x1 + "," + y1 + ";" + x2 + "," + y2;
    myform.selection_type.value = "rectangle";
    storeFeatures();
    doSubmit();
  }
};
/*
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
*/
  
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
    var editTable = xGetElementById("edit_table");
    
    // unset the feature in the features' array
    if (aFeature.operation == 'insert') {
      this.editTableRemoveRow(editTable, aFeature);
      var l = this.currentLayer.features.length;
      for (var i = 0; i < l; i++) {
        if (this.currentLayer.features[i].id == aFeature.id) {
          alert (i);
          var index = i;
          continue;
        }
      }
      this.currentLayer.features = array_unset(this.currentLayer.features, index);
    } else {
      this.editTableDeleteRow(editTable, aFeature);
    }
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

Map.prototype.drawEditAttributesTable = function() {
  var editDiv = xGetElementById("edit_div");
  
  var table = xCreateElement('TABLE');
  table.id = "edit_table";
  table.className = "edit";
  var tbody = xCreateElement('TBODY');
  
  if (editResultNbCol == 0) {
    var row = xCreateElement ('tr');

    for (var i = 0; i < this.editAttributeNamesI18n.length; i++) {
      if (this.editAttributeRendering[i] != 'hidden') {
        var cell = xCreateElement('th');
        cell.innerHTML = this.editAttributeNamesI18n[i];
        xAppendChild(row, cell);
      }
    }

    // add column for radio button
    var cell = xCreateElement('th');
    xAppendChild(row, cell);
    // add column for recenter
    var cell = xCreateElement('th');
    xAppendChild(row, cell);
    xAppendChild(tbody, row);
  }
  xAppendChild(table, tbody);

  xAppendChild(editDiv, table); 

  if (editDisplayAction != 'folder') {  
    // add validate and cancel buttons
    var validate_all = myform['validate_all'].cloneNode(true);
    validate_all.id = "validate2";
    xAppendChild(editDiv, validate_all);
    var cancel = myform['edit_cancel'].cloneNode(true);
    cancel.id = "cancel2";
    xAppendChild(editDiv, cancel);
  }
  
  for (var i = 0; i < this.currentLayer.features.length; i++) {
    editDiv.style.display = "block";
    this.editTableAddRow(table, this.currentLayer.features[i]);
  }
}

/******************************************/
/* generic functions                      */
/******************************************/

function hilightFeature(id) {
  window.status = id;
  if (id) {
    for (var i = 0 ; i < mainmap.currentLayer.features.length; i++) {
      if (mainmap.currentLayer.features[i].id == id) {
        var feature = mainmap.currentLayer.features[i];
        continue;
      }
    }
    dShape = mainmap.getDisplay("map").getDisplayFeature(feature);
    if (dShape != null && typeof dShape.innerHTML != "undefined") {
      changeStatus(dShape, _SEL, true, true);
    }
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
    var radArray = myform['edit_selected'];
    if (typeof radArray.length == "undefined") {// only one radio
      radArray.checked = "checked";
    } else {
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
    var radArray = myform['edit_selected'];
    if (radArray == null) {
      return;
    }
    if (typeof radArray.length == "undefined") { // only one radio
      radArray.checked = false;
    }
    for (i = 0; i < radArray.length; i++) {
        radArray[i].checked = false;
    }
}

/**
 * Adds a row to the edit features table
 */
Map.prototype.editTableAddRow = function(table, aFeature) {
  var tbody = xGetElementsByTagName('tbody', table)[0];
  var row = xCreateElement("tr");
  row.id = 'edit_row_' + aFeature.id;
  row.className = 'edit_row';
  // hilight table row and display feature
  row.onmouseover = function() {
    this.style.backgroundColor = "#F4F5F7";
    hilightFeature(aFeature.id);
  }
  row.onmouseout = function() {
    this.style.backgroundColor = "";
    hilightFeature(false);
  }
  row.onclick = function() {
    selectEditFeature(aFeature.id);
  }

  // editResultNbCol is a global variable, see template
  if (editResultNbCol > 0) {
    var outertd = xCreateElement("td");
    var innertable = xCreateElement("table");
    var innerbody = xCreateElement("tbody");
    var innerrow = xCreateElement("tr");
  }
  // fill the table row with cells and corresponding input forms
  for (var i = 0; i < this.editAttributeNames.length; i++) {
   
    if (this.editAttributeRendering[i] != 'hidden') {
      var td = xCreateElement("td");
    }

    // add cell title if multiline, if: nb col is specified and element rendering is not hidden
    if (editResultNbCol > 0 && this.editAttributeRendering[i] != 'hidden') {
      var inputTitle = document.createTextNode(this.editAttributeNamesI18n[i]);
      var inputBr = xCreateElement("br");
      td.appendChild(inputTitle);
      td.appendChild(inputBr);
    }
    // editable field
    if (this.editAttributeTypes[i] == 'string' || 
      this.editAttributeTypes[i] == 'integer') {
      if (typeof aFeature.attributes != "undefined") {
        var value = aFeature.attributes[i];
      } else { 
        var value = '';
      }
      // get input type from list, if set, default to 'text'
      var inputtype = this.editAttributeRendering[i] == '' ? 'text' : 
                      this.editAttributeRendering[i];
      
      var input = createInput(td,
        "edit_feature_" + aFeature.id + "[" + this.editAttributeNames[i] + "]", 
        value, inputtype)
      
      if (this.editAttributeRendering[i] != 'hidden') {
        input.onkeypress = function() {
          if (!this.changed) {
            if (aFeature.operation != 'insert')
              setFeatureOperation(aFeature, "update");
            var validate = xGetElementById('validate_all');
            validate.className = "form_button_hilight";
            if (editDisplayAction != 'folder') {
              var validate = xGetElementById('validate2');
              validate.className = "form_button_hilight";
            }
          }
          this.changed = true;
        }
      }
    }
    // not editable field
    else {
      if (typeof aFeature.attributes != "undefined") {
        td.innerHTML = aFeature.attributes[i];
      } else {
        td.innerHTML = "";
      }
    }

    if (editResultNbCol > 0) {
      xAppendChild(innerrow, td);
    } else {
      xAppendChild(row, td);
    }

    if (editResultNbCol > 0) {
      // if the elements is the xth, happend existing row and create a new row
      if ((i+1) % editResultNbCol == 0 && 
        this.editAttributeRendering[i] != 'hidden') {
        if (this.editAttributeRendering[i] != 'hidden') {
          xAppendChild(innerbody, innerrow);
        }
        if (this.editAttributeRendering[i+1] != 'hidden') {
          innerrow = xCreateElement("tr");
        }
      }
    }
  }

  if (editResultNbCol > 0) {
    xAppendChild(innerbody, innerrow);
    xAppendChild(innertable, innerbody);
    xAppendChild(outertd, innertable);
    xAppendChild(row, outertd);
  } else {
    xAppendChild(row, td);
  }

  var td = xCreateElement("td");
  var input = createInput(td, 'edit_selected', aFeature.id, 'radio');
  input.onclick = function() {
    selectEditFeature(aFeature.id);
    eval ("mainmap.edit_" + aFeature.type + "('map');");
  }
  xAppendChild(row, td);
  
  var td = xCreateElement("td");
  
  // rencenter on feature
  if (aFeature.operation != 'insert') {
    var image = createInput(td, 'edit_recenter', '', 'image');
    image.src = xGetElementById("edit_recenter").src;
    image.border = "0";
    image.href = "#";
    image.onclick = function() {
      var id_recenter_ids = xGetElementById('id_recenter_ids');
      if (id_recenter_ids == null) {
        var input = createInput(myform, 'id_recenter_ids', aFeature.id, 
                    'hidden');
      } else {
        id_recenter_ids.value = aFeature.id;
      }
      
      var id_recenter_layer = xGetElementById('id_recenter_layer');
      if (id_recenter_layer == null) {
        var input = createInput(myform, 'id_recenter_layer', 
                    myform.edit_layer.value, 'hidden');
      } else {
        id_recenter_layer.value = myform.edit_layer.value;
      }
      doSubmit();
    }
    xAppendChild(td, image);
  }
  
  xAppendChild(row, td);
  xAppendChild(tbody, row);
}

/**
 * Removes a row to the edit features table
 */
Map.prototype.editTableRemoveRow = function(table, aFeature) {
  var row = xGetElementById('edit_row_' + aFeature.id);
  table.firstChild.removeChild(row);
}

/**
 * Removes a row to the edit features table
 */
Map.prototype.editTableDeleteRow = function(table, aFeature) {
  var row = xGetElementById('edit_row_' + aFeature.id);
  row.className = 'deleted';
  var inputs = xGetElementsByTagName('input', row);
  var l = inputs.length;
  for (var i = 0; i < l; i++) {
    inputs[i].setAttribute('disabled',true);
  }
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

function array_unset(array,index) {
  // unset $array[$index], shifting others values
  var res = new Array();
  var i = 0;
  var l = array.length;
  for (var i = 0; i < l; i++) {
    if (i != index) {
      res.push(array[i]);
    }
  }
  return res;
}
