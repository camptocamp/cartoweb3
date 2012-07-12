var currentLayerReorderSelected = -1;

function layerReorderCurrent(key) 
{
  currentLayerReorderSelected = key;
}

function reorderUpside()
{
  if (currentLayerReorderSelected == -1) return;

  var from = currentLayerReorderSelected;
  var to  = reorderPreviousPosition(from);
  
  var container = xGetElementById("layerReorderContainer");
  var opacity = 
    xGetElementsByClassName('layersOpacity', container, 'select');
  var indexOpacity = new Array();
  
  for (i=0 ; i < opacity.length ; i++) { 
    indexOpacity[opacity[i].name.substr(14)] 
      = opacity[i].selectedIndex;
  }    
  
  reorderLayerMove(from, to);
  
  var container = xGetElementById("layerReorderContainer");
  var opacity
    = xGetElementsByClassName('layersOpacity', container, 'select');
  
  for (i=0 ; i < opacity.length ; i++) { 
    opacity[i].selectedIndex
      = indexOpacity[opacity[i].name.substr(14)];
  }
}

function reorderDownside()
{
  if (currentLayerReorderSelected == -1)
     return;

  var from = currentLayerReorderSelected;
  var to  = reorderNextPosition(currentLayerReorderSelected); 
  
  var container 
    = xGetElementById("layerReorderContainer");
  var opacity 
    = xGetElementsByClassName('layersOpacity', container, 'select');
  var indexOpacity = new Array();
    
  for (i=0 ; i < opacity.length ; i++) { 
    indexOpacity[opacity[i].name.substr(14)]
      = opacity[i].selectedIndex;
  }    
  
  reorderLayerMove(from, to);
  
  var container = xGetElementById("layerReorderContainer");
  var opacity 
    = xGetElementsByClassName('layersOpacity', container, 'select');
    
  for (i=0 ; i < opacity.length ; i++) { 
    opacity[i].selectedIndex
      = indexOpacity[opacity[i].name.substr(14)];
  }
}

function reorderPreviousPosition(idFrom)
{
  var container = xGetElementById("layerReorderContainer");
  var layers = xGetElementsByClassName('layerReorder', container, 'div');
  
  for (i=0 ; i < layers.length ; i++) {
    if (layers[i].id.substr(13) == idFrom) {
      if(i == 0) return layers[0].id.substr(13);
      return layers[i-1].id.substr(13);
    }
  }
}

function reorderNextPosition(idFrom)
{
  var container = xGetElementById("layerReorderContainer");
  var layers = xGetElementsByClassName('layerReorder', container, 'div');
  
  for (i=0 ; i < layers.length ; i++) { 
    if (layers[i].id.substr(13) == idFrom) {
      if(i + 2 >= layers.length) return 'last';
      return layers[i+2].id.substr(13);
    }
  }
}

function reorderLayerMove(idFrom, idTo) 
{
  if (idFrom == idTo) {
      return;
  }
  var container = xGetElementById("layerReorderContainer");
  var layers = xGetElementsByClassName('layerReorder', container, 'div');
  var from = '';
  var str = '';

  for (i=0 ; i < layers.length ; i++) { 
    if (layers[i].id.substr(13) == idFrom) {
      from = '<div id="layerReorder_' + idFrom + '" class="layerReorder">'
             + layers[i].innerHTML + '</div>';
    }
  }
      
  for (i=0 ; i < layers.length ; i++) {
    var currentId = layers[i].id.substr(13);

    if (currentId == idTo) str += from;
  
    if (currentId != idFrom) {
      str += '<div id="layerReorder_' + currentId 
             + '" class="layerReorder">' 
             + layers[i].innerHTML + '</div>';
    }
  }

  container.innerHTML = str;
  var currentLayer
    = xGetElementById("layerReorderRadio_" + currentLayerReorderSelected);
  currentLayer.checked = true;
}

function retrieveOrder()
{
  var container = xGetElementById("layerReorderContainer");
  var layers = xGetElementsByClassName('layerReorder', container, 'div');
  
  var OrderedIds = new Array();
  for (i=0 ; i < layers.length - 1 ; i++) {
    OrderedIds.push(layers[i].id.substr(13));
  }

  document.carto_form.layersReorder.value = OrderedIds.join(",");
}
