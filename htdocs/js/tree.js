/**************************************************************************
        Copyright (c) 2001 Geir Landrö (drop@destroydrop.com)
        JavaScript Tree - www.destroydrop.com/hugi/javascript/tree/
        Version 0.96    

        This script can be used freely as long as all copyright messages are
        intact.

        Copyright (c) 2002 Sylvain Pasche (sylvain_pasche@yahoo.fr)
           Modified to use in cartoweb
	   
        Copyright (c) 2003 Daniel FAIVRE (webmaster@geomaticien.com)
           Modified to use in cartoweb with dynamic legends
        
        Copyright (c) 2004 Pierre GIRAUD (pierre.giraud@camptocamp.com)
          Modified to be formated with tables for text line breaks

**************************************************************************/

// Arrays for nodes and icons
var nodes               = new Array();;
var openNodes   	= new Array();
var icons               = new Array(6);
/* --------------------- Modified 2003-08-29 by dF ---------------------------- */ 
var debug		= false;
// may be used in some applications to forbid layers management
var hideLegendCheckBoxes = false;

function HasLegend(node)
  {
  for (i=0; i< nodes.length; i++)
    {
    var nodeValues = nodes[i].split("|");
    if ((nodeValues[1] == node) & (nodeValues[7])) return true;
    }
    return false;
  }	

/* --------------------- End modified 2003-08-29 by dF -------------------------*/

// Loads all icons that are used in the tree
function preloadIcons() {
    icons[0] = new Image();
    icons[0].src = "gfx/tree/plus.png";
    icons[1] = new Image();
    icons[1].src = "gfx/tree/plusbottom.gif";
    icons[2] = new Image();
    icons[2].src = "gfx/tree/minus.png";
    icons[3] = new Image();
    icons[3].src = "gfx/tree/minusbottom.gif";
    icons[4] = new Image();
    icons[4].src = "gfx/tree/folder.gif";
    icons[5] = new Image();
    icons[5].src = "gfx/tree/folderopen.gif";
}
// Create the tree
function createTree(arrName, startNode, openNode) {
    nodes = arrName;
    if (nodes.length > 0) {
        preloadIcons();
        if (startNode == null) startNode = 0;
        if (openNode != 0 || openNode != null) setOpenNodes(openNode);
        
        if (startNode !=0) {
            var nodeValues = nodes[getArrayId(startNode)].split("|");
            document.write("<a href=\"" + nodeValues[3] + 
                           "\" onmouseover=\"window.status='" + nodeValues[2] + 
                           "';return true;\" onmouseout=\"window.status=' ';" 
                           + "return true;\"><img src=\"gfx/tree/folderopen.gif\" " +
                           "align=\"absbottom\" alt=\"\" />" + 
                           nodeValues[2] + "</a><br />");
        } else document.write("<img src=\"gfx/tree/icon_eye.png\" " +
                              "align=\"absbottom\" alt=\"\" /> Légende<br />");
        
        var recursedNodes = new Array();
        
        addNode(startNode, recursedNodes);
    }
}
// Returns the position of a node in the array
function getArrayId(node) {
    for (i=0; i<nodes.length; i++) {
        var nodeValues = nodes[i].split("|");
        if (nodeValues[0]==node) return i;
    }
}
// Puts in array nodes that will be open
function setOpenNodes(openNode) {
    for (i=0; i<nodes.length; i++) {
        var nodeValues = nodes[i].split("|");
        if (nodeValues[0]==openNode) {
            openNodes.push(nodeValues[0]);
            setOpenNodes(nodeValues[1]);
        }
    } 
}
// Checks if a node is open
function isNodeOpen(node) {
    for (i=0; i<openNodes.length; i++)
        if (openNodes[i]==node) return true;
    return false;
}
// Checks if a node has any children
function hasChildNode(parentNode) {
    for (i=0; i< nodes.length; i++) {
        var nodeValues = nodes[i].split("|");
        if (nodeValues[1] == parentNode) return true;
    }
    return false;
}
// Checks if a node is the last sibling
function lastSibling (node, parentNode) {
    var lastChild = 0;
    for (i=0; i< nodes.length; i++) {
        var nodeValues = nodes[i].split("|");
        if (nodeValues[1] == parentNode)
            lastChild = nodeValues[0];
    }
    if (lastChild==node) return true;
    return false;
}
// Checks if all children are checked
function allChildrenChecked(parent) {

    for (i=0; i< nodes.length; i++) {
        var nodeValues = nodes[i].split("|");

        if (nodeValues[1] == parent)
            if (nodeValues[5] != "checked")
                return false;
    }
    return true;

}
// Adds a new node in the tree
function addNode(parentNode, recursedNodes) {
    for (var i = 0; i < nodes.length; i++) {
        
        var nodeValues = nodes[i].split("|");
        if (nodeValues[1] == parentNode) {
            document.write("<table cellpadding=0 cellspacing=0 class=tree>")
            document.write("<tr>")
                        
            var ls      = lastSibling(nodeValues[0], nodeValues[1]);
            var hcn     = hasChildNode(nodeValues[0]);
            //var ino = isNodeOpen(nodeValues[0]);

            var nodeId = nodeValues[0];
            var parentNodeId = nodeValues[1];
            var nodeName = nodeValues[2];
            var nodeUrl = nodeValues[3];
            var nodeIcon = nodeValues[4];
            var nodeChecked = (nodeValues[5] == "checked");
            var ino = (nodeValues[6] == "open");
            var isLegend = nodeValues[7];
            var isOutOfScale = nodeValues[8];   	    

            // Write out line & empty icons
            for (g=0; g<recursedNodes.length; g++) {
                if (recursedNodes[g] == 1) 
                    document.write("<td background=\"gfx/tree/line.gif\" valign=top><img src=\"gfx/tree/line.gif\" " +
                                   "align=\"absbottom\" alt=\"\" />");
                else  
                    document.write("<td><img src=\"gfx/tree/empty.gif\" " +
                                   "align=\"absbottom\" alt=\"\" />");
            }
            document.write("</td>")

            // put in array line & empty icons
            if (ls) recursedNodes.push(0);
            else recursedNodes.push(1);

            // Write out join icons
            if (hcn) {
                if (ls) {
                    document.write("<td valign=top><a href=\"javascript: oc('" + nodeId + 
                                   "', 1);\"><img id=\"join" + nodeId + 
                                   "\" src=\"gfx/tree/");
                    if (ino) document.write("minus");
                    else document.write("plus");
                    document.write("bottom.gif\" border=0 align=\"absbottom\" " +
                                   "alt=\"Open/Close node\" /></a>");
                } else {
                    document.write("<td background=\"gfx/tree/line.gif\" valign=top ><a href=\"javascript: oc('" + nodeId + 
                                   "', 0);\"><img id=\"join" + nodeId + 
                                   "\" src=\"gfx/tree/");
                    if (ino) document.write("minus");
                    else document.write("plus");
                    document.write(".png\" border=0 align=\"absbottom\" " + 
                                   "alt=\"Open/Close node\" /></a>");
                }
            } else {
                if (ls) document.write("<td valign=top><img src=\"gfx/tree/join.gif\" " + 
                                       "align=\"absbottom\" alt=\"\" />");
                else document.write("<td background=\"gfx/tree/line.gif\" valign=top><img src=\"gfx/tree/joinbottom.gif\" " + 
                                    "align=\"absbottom\" alt=\"\" />");
            }
            document.write("</td>")

            // Write out folder & page icons
            document.write("<td valign=top align=left>")
            document.write("<img id=\"icon" + nodeId + 
                           "\" src=\"" + nodeIcon + 
                           "\" vspace=3 "+ (isOutOfScale ? " style='{filter: gray;}'" : "") + "/>");
            document.write("</td>")
    
            //checkbox
            if (((!hcn || HasLegend(nodeId)) && nodeChecked)
                || (hcn && allChildrenChecked(nodeId)) && (!HasLegend(nodeId)))	    
                checked = "checked=\"checked\"";
            else
                checked = "";

            //write hidden input to save open state
            if (hcn)
            {
                document.write("<input type=\"hidden\" id=\"opennode_" + nodeId 
                               + "\" name=\"opennode_" + nodeId + "\" value=\"" +
                               ino + "\" />");
            }
	        // Write checkbox  
            document.write("<td valign=top>")
            if (isLegend)
            {
	            document.write("&nbsp;");
            }
            else
            {
                if (!hideLegendCheckBoxes) document.write("<input type=\"checkbox\" " + checked +
                           " id=\"check" + nodeId + "\" " +
                           " onclick=\"javascript: selOne('" + nodeId + "', 0)\"" +
                           " value=\"Y\" name=\"layer_" + (nodeId) + "\"" + (isOutOfScale ? " style='{color: gray;}'" : "") + "/>");		   
                else document.write("&nbsp;");
            }
		    document.write("</td>");
	    /* Warning: javascript onmouseover used to make IE report an missing ; error message */
            document.write("<td>")
            // Start link
            if (nodeUrl) {
                document.write("<a href=\"" + nodeUrl + "\"" + ((nodeUrl.substring(0,11) != "javascript:") ? "target=\"nodes\"" : "") + ">");
            }
	    
            // Write out node name
            document.write("<span class='tree'" + (isOutOfScale ? " style='color:gray'" : "") + ">" + nodeName + "</span>");
	    	    
            // End link
            if (nodeUrl) {
                document.write("</a>");
            }
            document.write("</td></tr></table>")

            // If node has children write out divs and go deeper
            if (hcn) {
                document.write("<div id=\"div" + nodeId + "\"");
/* ---------------------      nowrap added         ---------------------------- */
                if (!ino)
                    document.write(" style=\"display: none; white-space:nowrap\"");
                else
                    document.write(" style=\"white-space:nowrap\"");
                document.write(">");               
                addNode(nodeId, recursedNodes);
                document.write("</div>");
                
            }
            // remove last line or empty icon 
            recursedNodes.pop();
        }
    }
}
function getParent(node) {

    for (i=0; i< nodes.length; i++) {
        var nodeValues = nodes[i].split("|");
        if (nodeValues[0] == node) 
            return nodeValues[1];
    }
    return -1;
}

// Return true if all children are checked
function allSiblingsChecked(node) {

    parentId = getParent(node);

    for (i=0; i< nodes.length; i++) {
        var nodeValues = nodes[i].split("|");
        if (nodeValues[1] == parentId) {
            childId = nodeValues[0];
            if (!document.getElementById("check" + childId).checked)
                return false;
        }
    }
    return true;
}

// click on a checkbox
function selOne(node, bottom) {
    var hcn = hasChildNode(node);
/* --------------------- Modified 2003-08-31 by dF ---------------------------- */     
    if (debug)
      {
      alert("click on " + node + (hcn ? " has children":"") + " HasLegend=" + HasLegend(node));
      }

    if (hcn && !HasLegend(node)) {
/* --------------------- End modified 2003-08-31 by dF -------------------------*/
        parentChecked = document.getElementById("check" + node).checked;
        for (i=0; i< nodes.length; i++) {
            var nodeValues = nodes[i].split("|");
            if (nodeValues[1] == node) {
                childId = nodeValues[0];
                document.getElementById("check" + childId).checked = 
                    parentChecked;
            }
        }
    } else {
        if (getParent(node) != "")
            document.getElementById("check" + getParent(node)).checked =
                allSiblingsChecked(node);        
    }
}

// Opens or closes a node
function oc(node, bottom) {

    var theDiv = document.getElementById("div" + node);
    var theJoin = document.getElementById("join" + node);
    var theIcon = document.getElementById("icon" + node);
    var theHidden = document.getElementById("opennode_" + node);

    if (theDiv.style.display == 'none') {
        //open
        theHidden.value = "true";
        if (bottom==1) theJoin.src = icons[3].src;
        else theJoin.src = icons[2].src;
        //theIcon.src = icons[5].src;
        theDiv.style.display = '';
    } else {
        // close
        theHidden.value = "false";
        if (bottom==1) theJoin.src = icons[1].src;
        else theJoin.src = icons[0].src;
        //theIcon.src = icons[4].src;
        theDiv.style.display = 'none';
    }
}
// Push and pop not implemented in IE(crap!    don´t know about NS though)
if(!Array.prototype.push) {
    function array_push() {
        for(var i=0;i<arguments.length;i++)
            this[this.length]=arguments[i];
        return this.length;
    }
    Array.prototype.push = array_push;
}
if(!Array.prototype.pop) {
    function array_pop(){
        lastElement = this[this.length-1];
        this.length = Math.max(this.length-1,0);
        return lastElement;
    }
    Array.prototype.pop = array_pop;
}
