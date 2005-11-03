function FormItemSelected() {
  doSubmit();
}
  
function checkRadio(id) {
  xGetElementById(id).checked = 'checked';
}

function setActiveToolButton(toolid) {
  for (var i = 0; i < cw3_tools.length; i++) {
    var elt = xGetElementById(cw3_tools[i] + "_icon");

    if(elt == null) return;
    
    if (cw3_tools[i] == toolid) {
      elt.className = "toolbar_on";
    } else {
      elt.className = "toolbar_off";
    }
  }
  var elt = xGetElementById("tool");
  elt.value = toolid;
  
  if (typeof myfolders != "undefined") { // not on page load
    if(toolid.indexOf('outline')!=-1){
      ontop(5);
    }
    if(toolid.indexOf('query')!=-1){
      ontop(7);
    }
  }
}

function setSearchFrame(project, type) {
  var ifr = document.getElementById('search');
  ifr.style.height = '350px';
  ifr.src = project + '/search/index.php?project=' + project + '&searchname=' + type;
}

function resetSession() {
    elm = xGetElementById('fake_reset');
    elm.name = 'reset_session';
    elm.value = 'reset_session';
    document.carto_form.posted.value=0;
    FormItemSelected();
}