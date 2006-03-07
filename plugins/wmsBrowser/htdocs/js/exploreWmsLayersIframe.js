/*
 * mapbrowser_template_en.html have hugely been modified by CartoWeb dev team.
 * The only kept javascipt function is previewLayer.
 *
////////////////////////////////////////////////////////////////////////////////
// MapBrowser application                                                     //
//                                                                            //
// @project     MapLab                                                        //
// @purpose     This is the dbase database management utility page.           //
// @author      William A. Bronsema, C.E.T. (bronsema@dmsolutions.ca)         //
// @copyright                                                                 //
// <b>Copyright (c) 2002, DM Solutions Group Inc.</b>                         //
// Permission is hereby granted, free of charge, to any person obtaining a    //
// copy of this software and associated documentation files(the "Software"),  //
// to deal in the Software without restriction, including without limitation  //
// the rights to use, copy, modify, merge, publish, distribute, sublicense,   //
// and/or sell copies of the Software, and to permit persons to whom the      //
// Software is furnished to do so, subject to the following conditions:       //
//                                                                            //
// The above copyright notice and this permission notice shall be included    //
// in all copies or substantial portions of the Software.                     //
//                                                                            //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR //
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,   //
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL   //
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER //
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING    //
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER        //
// DEALINGS IN THE SOFTWARE.                                                  //
////////////////////////////////////////////////////////////////////////////////
 */

/*
 * Shows loading bar
 */
function showActivityLayer() {
  activityLayer = parent.document.getElementById('ActivityLayer');
  xShow(activityLayer);
}

/*
 * Submits form
 */
function doSubmit() {
  parent.hideActivityLayer();
  xShow(xGetElementById('loadbarDiv'));
  myform.submit();
}

/* 
 * Puts the layer in the map for previewing
 * @param string layer name
 * @param string layer title
 * @param string layer srs
 * @param string connection url
 * @param string wms server version
 * @param string image format 
 * @param string layer bbox(comma-separated)
 */ 
function previewLayer(name, title, connection, version, srs, format, bbox, abs, metadataUrl) {
  metadatas = new Array(name, connection, version, srs, format, bbox);
  if (!checkMetadatas(metadatas))
    return;
  showActivityLayer();
  // build the WMS connection string
  var url = connection;
  url += "service=WMS&";
  url += "styles=&";
  url += "version=" + version + "&";
  url += "request=GetMap&";
  url += "SRS=" + srs + "&";
  url += "BBOX="+ bbox + "&";
  url += "width=220&";
  url += "height=220&";
  url += "layers=" + name + "&";
  url += "format=" + format + "&";
  url += "exceptions=application/vnd.ogc.se_inimage";
  parent.document.images.mapimage.src = url;
  //show abstract and add layer
  showAbstract(title, abs, metadataUrl);
  selectLayer(name);
}

/*
 * Updates the selected layer
 * @param string layer name
 */
function selectLayer(layerName) {
  document.getElementById('selectedWmsLayer').value = layerName;
}

/*
 * Builds open nodes list
 */
function writeOpenNodes(shortcut) {
  if (shortcut) {
    document.getElementById('openNodes').value = openNodes;
    return;
  }
  
  var nodesList = new Array();
  for (var i = 0; i < openNodes.length; i++) {
    nodesList.push(openNodes[i]);
  }
  document.getElementById('openNodes').value = nodesList;
}

/*
 * Return true if node which id is specified is an open node, else return false
 */
function isInOpenNodes(id) {
  for (var i = 0; i < openNodes.length; i++) {
    if (openNodes[i] == id) return i + 1;
  }
  return false;
}

/*
 * Updates wms layers tree open nodes
 */
function updateOpenNodes(id, open) {
  var isModified = false;
  if (open) { 
    if (!isInOpenNodes(id)) {
      // adds node to list
      openNodes.push(id);
      isModified = true;
    }
  } else {
    var i = isInOpenNodes(id);
    if (i > 0) {
      // removes node from list
      delete(openNodes[i - 1]);
      isModified = true;
    }
  }
  if(isModified) writeOpenNodes();
}

/*
 * Replaces wms layers tree node image
 */
function replacePic(obj, from, to) {
  var imgs = obj.getElementsByTagName('img');
  var pic = imgs[0].getAttribute('src');
  var re = new RegExp(from);
  pic = pic.replace(re, to);
  imgs[0].setAttribute('src', pic);
}

/*
 * Opens or closes a wms layers tree node
 */
function shift(id) {
  var obj = document.getElementById(id);
  var key = document.getElementById('x' + id);
  var iid = id.substr(2);
  var visible;

  if (obj.style.display != '') {
    if (obj.style.display != 'none') visible = true;
    else visible = false;
  } else {
    if (obj.className == 'v') visible = true;
    else visible = false;
  }
  
  if (visible) { 
    replacePic(key, 'minus', 'plus');
    obj.style.display = 'none';
    updateOpenNodes(iid);
  }
  else {
    replacePic(key, 'plus', 'minus');
    obj.style.display = 'block';
    updateOpenNodes(iid,true);
  }
}
