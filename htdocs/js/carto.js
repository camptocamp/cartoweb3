/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function FormItemSelected() {
  doSubmit();
}

function setActiveToolButton(toolid) {
  
  for (var i = 0; i < cw3_tools.length; i++) {
  
    var elt = xGetElementById(cw3_tools[i] + '_icon');
    if(elt == null) continue;

    if (typeof toolbar_rendering != 'undefined' && 
        toolbar_rendering == 'swap') {

      var pic = elt.getAttribute('src');
      var picExt = pic.substr(pic.lastIndexOf('.') + 1);

      if (cw3_tools[i] == toolid) {
        var from = cw3_tools[i] + '.' + picExt;
        var to = cw3_tools[i] + '_over.' + picExt;
      } else {
        var from = cw3_tools[i] + '_over.' + picExt;
        var to = cw3_tools[i] + '.' + picExt;
      }

      var re = new RegExp(from);
      var newpic = pic.replace(re, to);
       
      if (newpic != pic) {
        elt.setAttribute('src', newpic);
      }
    
    } else {
      // default case: toolbar_rendering = outline
      if (cw3_tools[i] == toolid) {
        elt.className = "toolbar_on";
      } else {
        elt.className = "toolbar_off";
      }
    }
  }
  
  var elt = xGetElementById("tool");
  elt.value = toolid;
}

function checkRadio(id) {
  xGetElementById(id).checked = 'checked';
}

function redirectTo(url) {
  var version = 0;
  if (navigator.appVersion.indexOf("MSIE") != -1) {
    temp = navigator.appVersion.split("MSIE");
    version = parseFloat(temp[1]);
  }

  if (version >= 6.0) {
    // only for IE6
    document.URL = url;
  } else {
    window.location.href = url;
  }
}
