stateSelected = -1;


function reorderSelect(elt) 
{
    if (stateSelected == -1) { 
    	stateSelected = elt.id;
    } else {
    	stateSelected = -1;
    }
}


function reorderInterFlush() 
{
    var container = xGetElementById("reorderContainer");
    var layers = 
        xGetElementsByClassName('layerReorderInterSelect', container, 'div');
    for (i=0 ; i < layers.length ; i++) {
    	layers[i].className="layerReorderInter";
    }
}

function reorderLayerMove(idFrom, idTo) 
{
    if (idFrom == idTo) {
   	 return;
    }
    var container = xGetElementById("reorderContainer");
    var layers = xGetElementsByClassName('layerReorder', container, 'div');
    var from = '';
    var str ='';

    for (i=0 ; i < layers.length ; i++) { 
    	if (layers[i].id.substr(13) == idFrom) {
	    from = '<div id="layerReorder_' + idFrom + '" class="layerReorder">'
	           + layers[i].innerHTML + '</div>';
	}
    }
	    
    for (i=0 ; i < layers.length ; i++) {
    	var currentId = layers[i].id.substr(13);

 	if (currentId == idTo) {
	    str += from;
	}
	
    	if (currentId != idFrom) {
	    str += '<div id="layerReorder_' + currentId 
	           + '" class="layerReorder">' 
	           + layers[i].innerHTML + '</div>';
	}
    }

    container.innerHTML = '<div id="reorderContainer">' + str + '</div>';
}


function reorderUnselect(elt) {
    reorderInterFlush();
    if (stateSelected == -1) {
        return;
    }
    if (elt.id.substr(0, 19) == 'layerReorderLayer_') {
        return stateSelected = -1;
    }
    reorderLayerMove(stateSelected.substr(18), elt.id.substr(18));
    stateSelected = -1;
}


function reorderInterOver(elt) {
    reorderInterFlush();
    if (stateSelected != -1) {
        elt.className="layerReorderInterSelect";
    }
}


function retrieveOrder()
{
    var container = xGetElementById("reorderContainer");
    var layers = xGetElementsByClassName('layerReorder', container, 'div');
   
    var OrderedIds = new Array();
    for(i=0 ; i < layers.length ; i++)
    	OrderedIds.push(layers[i].id.substr(13));

    document.carto_form.layersReorder.value = OrderedIds.join(",");
}
