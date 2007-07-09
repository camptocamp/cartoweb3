/**
 * Displays a text input form to add a label to the drawn object
 * @param text default text in the field
 * @param x x position of the input
 * @param y y position of the input
 */
function addLabel(text,x,y) {
  outlineLabelInput = xGetElementById('outlineLabelInputDiv');
  outlineLabelText = xGetElementById('outline_label_text');
  outlineLabelText.value = text;
  xMoveTo(outlineLabelInput,x + 5,y + 5);
  outlineLabelInput.style.zIndex = 3;
  xShow(outlineLabelInput);
}

/**
 * Hides the input form to add a label
 */
function hideLabel() {
  outlineLabelInput = xGetElementById('outlineLabelInputDiv');
  xHide(outlineLabelInput);
}

/**
 * Activates matching tool depending on outline folder actions
 */
function selectTool(e) {
    var el;
    if (window.event && window.event.srcElement)
        el = window.event.srcElement;
    if (e && e.target)
        el = e.target;
    if (!el) return;

    switch (el.id){
      case 'outline_point_symbol_d' :
      case 'outline_point_color_d' :
      case 'outline_point_size' :
      case 'outline_point_color' :
      case 'outline_point_transparency' :
        mainmap.outline_point('map');
        setActiveToolButton('outline_point');
      break;
      case 'outline_line_color_d' :
      case 'outline_line_size' :
      case 'outline_line_color' :
      case 'outline_line_transparency' :
        mainmap.outline_line('map');
        setActiveToolButton('outline_line');
      break;
      case 'outline_polygon_outline_color_d' :
      case 'outline_polygon_background_color_d' :
      case 'outline_polygon_outline_color' :
      case 'outline_polygon_background_color' :
      case 'outline_polygon_transparency' :
        mainmap.outline_poly('map');
        setActiveToolButton('outline_poly');
      break;
    }
}

/**
 * Initializes event listener for the different elements in the page
 */
function addOutlineToolListeners() {

  var onClickElements = new Array('outline_point_symbol_d','outline_point_color_d',
                    'outline_line_color_d','outline_polygon_outline_color_d',
                    'outline_polygon_background_color_d');

  var onFocusElements = new Array('outline_point_size','outline_point_color',
                    'outline_line_size','outline_line_color','outline_line_transparency',
                    'outline_polygon_outline_color','outline_polygon_background_color',
                    'outline_polygon_transparency');

  for (var i = 0; i < onClickElements.length; i++){
    elm = xGetElementById(onClickElements[i]);
    if (!elm)
      continue;
    EventManager.Add(elm, 'click', selectTool, false);
  }
  
  for (var i = 0; i < onFocusElements.length; i++){
    elm = xGetElementById(onFocusElements[i]);
    if (!elm)
      continue;
    EventManager.Add(elm, 'focus', selectTool, false);
    /* add attribute to input element */
    elm.setAttribute('autocomplete','off');
  }
}

EventManager.Add(window, 'load', addOutlineToolListeners, false);
