function FormItemSelected() {
  document.carto_form.submit();
  xShow(xGetElementById('mapAnchorDiv'));
}
  
function CheckRadio(theIndex) {
  document.carto_form.tool[theIndex].checked = true;
}

function setActiveButton(toolname, outline) {

  var tools = new Array('zoom_in', 'zoom_out', 'pan', 'query',
	'outline_point', 'outline_line', 'outline_rectangle', 'outline_poly',
	 'distance', 'surface');

  for (var i = 0; i < tools.length; i++) {
    var elt = xGetElementById(tools[i] + '_icon');

    if(elt == null) return;

    if (tools[i] == toolname) {
      elt.className = "toolbar_on";
    } else {
      elt.className = "toolbar_off";
    }
  }

  if(outline) {
        if(toolname == 'outline_point' || toolname == 'outline_line' || 
        	toolname == 'outline_rectangle' || toolname == 'outline_poly')
        	ontop(5);
  }
}

function setSearchFrame(project, type) {
  var ifr = document.getElementById('search');
  ifr.style.height = '350px';
  ifr.src = project + '/search/index.php?project=' + project + '&searchname=' + type;
}

function collapseKeymap() {
  var keymap = document.getElementById('floatkeymap');
  var switcher = document.getElementById('switcherimg');
  var pic = switcher.getAttribute('src');
  var oldStatus;
  var newStatus;
  
  if (keymap.style.display == 'none') {
    oldStatus = 'off';
    newStatus = 'on';
    keymap.style.display = 'inline';
    switcher.setAttribute('title', hideKeymapMsg);
    document.carto_form.collapse_keymap.value = '0';
  } else {
    oldStatus = 'on';
    newStatus = 'off';
    keymap.style.display = 'none';
    switcher.setAttribute('title', showKeymapMsg);
    document.carto_form.collapse_keymap.value = '1';
  }

  var re = new RegExp(newStatus);
  var newpic = pic.replace(re, oldStatus);
  switcher.setAttribute('src', newpic);
}



onLoadString = "";
onLoadString += "keymap = xGetElementById('floatkeymap');";
onLoadString += "anchor = xGetElementById('mapAnchorDiv');";
onLoadString += "xMoveTo(keymap,xPageX(anchor) + 5,xPageY(anchor) + 5);";
onLoadString += "switcher = xGetElementById('keymapswitcher');";
onLoadString += "xMoveTo(switcher,xPageX(anchor) + 5,xPageY(anchor) + 5);";
onLoadString += "keymapCont = xGetElementById('keymapContainer');";
onLoadString += "xShow(keymapCont);";
