function collapseKeymap() {
  var keymap = xGetElementById('floatkeymap');
  var switcher = xGetElementById('switcherimg');
  var oldStatus;
  var newStatus;

  if (keymap.style.display == 'none') {
    oldStatus = '_off';
    newStatus = '_on';
    keymap.style.display = 'block';
    switcher.title = hideKeymapMsg
    myform.collapse_keymap.value = '0';
  } else {
    oldStatus = '_on';
    newStatus = '_off';
    keymap.style.display = 'none';
    switcher.title = showKeymapMsg;
    document.forms['carto_form'].collapse_keymap.value = '1';
  }

  var re = new RegExp(newStatus);
  var newpic = switcher.src.replace(re, oldStatus);
  switcher.src = newpic;
}

function updateKeymapStatus() {
  if (hideKeymap)
    collapseKeymap();
}

function keymapInit() {
  updateKeymapStatus();
  keymapCont = xGetElementById('keymapContainer');
  xShow(keymapCont);
}

if (typeof onLoadString != "string") onLoadString = "";
onLoadString += "keymapInit();";
