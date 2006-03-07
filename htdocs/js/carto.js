/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function FormItemSelected() {
    doSubmit();
}

function setActiveToolButton(toolid) {
    for (var i = 0; i < cw3_tools.length; i++) {
        var elt = xGetElementById(cw3_tools[i] + "_icon");

        if(elt == null) {
            return;
        }
    
        if (cw3_tools[i] == toolid) {
            elt.className = "toolbar_on";
        } else {
            elt.className = "toolbar_off";
        }
    }
    var elt = xGetElementById("tool");
    elt.value = toolid;
}

function checkRadio(id) {
    xGetElementById(id).checked = 'checked';
}
