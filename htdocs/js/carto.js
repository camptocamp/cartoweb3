/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function FormItemSelected() {
  doSubmit();
}

function setActiveToolButton(toolid) {
  
  for (var i = 0; i < cw3_tools.length; i++) {
  
    var elt = xGetElementById(cw3_tools[i] + "_icon");
    if(elt == null) return;

    if (typeof toolbar_rendering != 'undefined' && 
        toolbar_rendering == 'swap') {
      if (cw3_tools[i] == toolid) {
        var from = cw3_tools[i];
        var to = cw3_tools[i] + '_over';
      } else {
        var from = cw3_tools[i] + '_over';
        var to = cw3_tools[i];
      }

      var pic = elt.getAttribute('src');
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
