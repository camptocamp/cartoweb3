/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function setupToolbar() {
  var myform = document.forms['carto_form']
  var toolbar_idx = myform.js_toolbar_idx.value;
  ontopToolbar(toolbar_idx);
}

function ontopToolbar(id) {
  var myfolders = xGetElementsByClassName('toolbar', null, null);
  for (i = 0; i < myfolders.length; i++) {
    var currentFolder = myfolders[i];
    var current = currentFolder.id.substr(7);
//    var currentLabel = xGetElementById('toolbar_label' + current);
    if (current == id) {
      currentFolder.style.display = "block";
//      currentLabel.className = 'active';
    } else {
      currentFolder.style.display = "none";
//     currentLabel.className = '';
    }
  }
  document.carto_form['js_toolbar_idx'].value = id;
  // temporary check to prevent safari from screwing the template
  // to remove once safari is patched
  // see http://bugzilla.opendarwin.org/show_bug.cgi?id=3677 for bug description
  // see http://developer.apple.com/internet/safari/uamatrix.html for safari version detection
  safari = false;
  xUA = navigator.userAgent.toLowerCase();
  i = xUA.indexOf('safari');
  if (i>0) {
      v = xUA.slice(i+7,xUA.length);
      if (v <= 312 || v == 412) {
          safari = true;
      }
  }
}
EventManager.Add(window, 'load', setupToolbar, false);