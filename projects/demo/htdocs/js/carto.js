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