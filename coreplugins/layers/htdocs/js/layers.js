function layersInit() {
  var layersroot = xGetElementById('layersroot');
  var layers = xGetElementsByTagName('input', layersroot);
  
  for (i=0; i<layers.length;i++) {
    if (layers[i].checked == false) {
      var pid = xGetElementById('id' + layers[i].id.substr(2));
      if (!pid) continue;
      layers[i].indeterminate = !isChildrenUnchecked(pid.id);
    }
  } 
}

function writeOpenNodes(shortcut) {
  if (shortcut) {
    document.carto_form.elements.openNodes.value = openNodes;
    return;
  }
  
  var nodesList = new Array();
  for (var i = 0; i < openNodes.length; i++) {
    nodesList.push(openNodes[i]);
  }
  document.carto_form.elements.openNodes.value = nodesList;
}

function isInOpenNodes(id) {
  for (var i = 0; i < openNodes.length; i++) {
    if (openNodes[i] == id) return i + 1;
  }
  return false;
}

function updateOpenNodes(id, open) {
  var isModified = false;
  if (open) { 
    if (!isInOpenNodes(id)) {
      // adds node to list
      openNodes.push(id);
      isModified = true;
    }
  } else {
    var i = isInOpenNodes(id);
    if (i > 0) {
      // removes node from list
      delete(openNodes[i - 1]);
      isModified = true;
    }
  }
  if(isModified) writeOpenNodes();
}

function replacePic(obj, from, to) {
  var imgs = obj.getElementsByTagName('img');
  var pic = imgs[0].getAttribute('src');
  var re = new RegExp(from);
  pic = pic.replace(re, to);
  imgs[0].setAttribute('src', pic);
}

function shift(id) {
  var obj = document.getElementById(id);
  var key = document.getElementById('x' + id);
  var iid = id.substr(2);
  var visible;

  if (obj.style.display != '') {
    if (obj.style.display != 'none') visible = true;
    else visible = false;
  } else {
    if (obj.className == 'v') visible = true;
    else visible = false;
  }
  
  if (visible) { 
    replacePic(key, 'minus', 'plus');
    obj.style.display = 'none';
    updateOpenNodes(iid);
  }
  else {
    replacePic(key, 'plus', 'minus');
    obj.style.display = 'block';
    updateOpenNodes(iid,true);
  }
}

 function expandAll(id) {
  var mydiv = document.getElementById(id);
  var divs = mydiv.getElementsByTagName('div');
  var key;
  
  openNodes = new Array();
  
  for (var i = 0; i < divs.length; i++) {
    divs[i].style.display = 'block';
    var nid = divs[i].id;
    openNodes.push(nid.substr(2));
    key = document.getElementById('x' + nid);
    if (key) replacePic(key, 'plus', 'minus');
  }

  writeOpenNodes(true);
}

function closeAll(id) {
  var mydiv = document.getElementById(id);
  var divs = mydiv.getElementsByTagName('div');
  var key;
  
  for (var i = 0; i < divs.length; i++) {    
    key = document.getElementById('x' + divs[i].id);
    if (key) replacePic(key, 'minus', 'plus');
      
    if (divs[i].getAttribute('id')) {
        divs[i].style.display = 'none';    
    }
  }

  openNodes = new Array();
  writeOpenNodes(true);
}

function checkChildren(id,val) {
  var mydiv = document.getElementById(id);
  if (!mydiv) return;
  
  var divs = mydiv.getElementsByTagName('input');
  if (val != false) val = true;

  for (var i = 0; i < divs.length; i++) {
    if (divs[i].name.substring(0, 6) == 'layers')
      divs[i].checked = val;
  }
}

function isChildrenChecked(id) {
  var dparent = document.getElementById(id);
  var celts = dparent.getElementsByTagName('input');
  for (var i = 0; i < celts.length; i++) {
    if (!celts[i].checked) return false;
  }
  return true;
}

function isChildrenUnchecked(id) {
  var dparent = document.getElementById(id);
  var celts = dparent.getElementsByTagName('input');
  for (var i = 0; i < celts.length; i++) {
    if (celts[i].checked) return false;
  }
  return true;
}

function updateChecked(id,skipChildren) {
  var refresh = xGetElementById("refresh");
  if (refresh != null && !CartoWeb.isAjaxMode())
    refresh.className = "form_button_hilight";

  var obj = document.getElementById('in' + id);
  if (!obj) return;
  var val = obj.checked;
  
  if (!skipChildren) checkChildren('id' + id, val);
  
  var pid = obj.parentNode.getAttribute('id');
  if (!pid) return;
  var iid = pid.substr(2);
  var iparent = document.getElementById('in' + iid);
 
  if (!iparent) return;

  // if node has been unchecked, makes sure parents are unchecked too
  if (val == false) {
    iparent.checked = false;
    iparent.indeterminate = !isChildrenUnchecked(pid);
    updateChecked(iid, true);
  }
  // if all siblings are checked, makes sure parents are checked too
  else if (isChildrenChecked(pid)) {
    iparent.checked = true;
    iparent.indeterminate = false;
    updateChecked(iid, true);
  }
  else iparent.indeterminate = true;
}

function goToScale(scale) {
  document.carto_form.recenter_doit.value = 1;
  var newOpt = document.createElement('option');
  newOpt.value = scale;
  newOpt.selected = true;
  document.carto_form.recenter_scale.options.add(newOpt);
  FormItemSelected();
}