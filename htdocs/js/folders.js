/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function setupFolders() {
    var current;
    var folder_idx = document.carto_form.js_folder_idx.value;

    var container = xGetElementById('container');
    xShow(container);
    for (i = 0; i < myfolders.length; i++) {
                current = myfolders[i];
		folder = xGetElementById('folder' + current);
		xMoveTo(folder,xPageX(container),xPageY(container));
		xShow(folder);

		xWidth(folder,xWidth(container));
    }
    ontop(myfolders[folder_idx]);
    xWidth(xGetElementById("blue"),200);
}

function ontop(id) {

    for (i = 0; i < myfolders.length; i++) {
                current = myfolders[i];
		currentFolder = xGetElementById('folder'+ current);
		xHide(currentFolder);
		
		currentLabel = xGetElementById('label' + current);
		
		if (currentLabel.style) {
			currentLabel.style.backgroundColor = '#EBEBEB';
			currentLabel.style.borderColor = 'black';
			currentLabel.style.borderStyle = 'none';
			currentLabel.style.borderBottomStyle = 'solid';
			currentLabel.style.borderWidth = '0px';
			currentLabel.style.borderBottomWidth = '1px';
		}

		if (current == id) {
		    document.carto_form.js_folder_idx.value = i;
		}
    }
	folder = xGetElementById('folder'+ id);
	xShow(folder);
	
	label = xGetElementById('label'+ id);
	if (label.style) {
		label.style.backgroundColor = 'white';
		label.style.borderBottomColor = 'white';
		label.style.borderStyle = 'solid';
		label.style.borderBottomStyle = 'none';
		label.style.borderWidth = '1px';
		label.style.borderBottomWidth = '0px';
	}
}

