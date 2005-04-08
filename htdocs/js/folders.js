/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function setupFolders() {
  myfolders = xGetElementsByClassName('folder', null, null);
  var myform = document.forms['carto_form']
  var folder_idx = myform.js_folder_idx.value;
  ontop(folder_idx);
}

function ontop(id) {
  for (i = 0; i < myfolders.length; i++) {
    currentFolder = myfolders[i];
    current = currentFolder.id.substring(6,7);
    currentLabel = xGetElementById('label' + current);
    if (current == id) {
      currentFolder.style.display = "block";
      currentLabel.className = 'active';
    } else {
      currentFolder.style.display = "none";
      currentLabel.className = '';
    }
  }
  document.carto_form.js_folder_idx.value = id;
  
  if (!isTopRowClicked(id))
    swapRows();
}

// 
function isTopRowClicked(id) {
  var clickedLabel = xGetElementById('label' + id);
  var row = xParent(clickedLabel,true)
  return (clickedLabel.parentNode.id == 'tabnav1');
}

function swapRows() {
  var topRow = xGetElementById('tabnav1');
  var lowRow = xGetElementById('tabnav2');
  var temp = topRow.innerHTML;
  topRow.innerHTML = lowRow.innerHTML;
  lowRow.innerHTML = temp;
}

if (typeof onLoadString != "string") onLoadString = "";
onLoadString += "setupFolders();";
