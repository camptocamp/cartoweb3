/*
graphTools.js

javascript functions to create and draw lines and points
for map navigation tool (zoom box, distance and surface measurement)

Part of this code was previously found from wz_jsgraphics.js (http://www.walterzorn.com)
and hardly adapted

Copyright 2004 Pierre GIRAUD
http://www.camptocamp.com
*/

var jg_ihtm, jg_ie, jg_fast, jg_dom, jg_moz,
jg_n4 = (document.layers && typeof document.classes != "undefined");


function chkDHTM(x, i)
{
	x = document.body || null;
	jg_ie = x && typeof x.insertAdjacentHTML != "undefined";
	jg_dom = (x && !jg_ie &&
		typeof x.appendChild != "undefined" &&
		typeof document.createRange != "undefined" &&
		typeof (i = document.createRange()).setStartBefore != "undefined" &&
		typeof i.createContextualFragment != "undefined");
	jg_ihtm = !jg_ie && !jg_dom && x && typeof x.innerHTML != "undefined";
	jg_fast = jg_ie && document.all && !window.opera;
	jg_moz = jg_dom && typeof x.style.MozOpacity != "undefined";
}

function pntDoc()
{
	this.wnd.document.write(this.htm);
	this.htm = '';
}


function pntCnvDom()
{
	var x = document.createRange();
	x.setStartBefore(this.cnv);
	x = x.createContextualFragment(this.htm);
	this.cnv.appendChild(x);
	this.htm = '';
}


function pntCnvIe()
{
	this.cnv.insertAdjacentHTML("beforeEnd",this.htm);
	this.htm = '';
}

function pntCnv()
{
	this.htm = '';
}


function mkDivPt(x,y)
{
	this.htm += '<div class="point" style="left:'+x+';top:'+y+'"></div>';
	//this.htm += '<div style="position:absolute;left:'+x+';top:'+y+';width:4px;height:4px;background-color:red;overflow:hidden"></div>';
}

function mkLineH(x,y,h)
{
	this.htm += '<div class="lineH" style="left:'+x+';top:'+y+';height:'+h+'"></div>';	
}

function mkLineW(x,y,w)
{
	this.htm += '<div class="lineW" style="left:'+x+';top:'+y+';width:'+w+'"></div>';
}

function mkLinePts(x1,y1,x2,y2,d2pts) { //function added to draw lines with few points
  	this.printing = true;
	var dx = x2-x1;
	var dy = y2-y1;
	var darc = Math.sqrt(dx * dx + dy * dy); //arc length	
	var nb_pts = Math.round(darc / d2pts); // number of points on the arc
	var dx2pts = dx / nb_pts;
	var dy2pts = dy / nb_pts;
	var i = 0;
	this.mkDivPt(x1,y1);
	while (i < nb_pts) {
		++i;
		this.mkDivPt(Math.round(x1 + dx2pts * i), Math.round(y1 + dy2pts * i));
		//this.mkDiv(Math.round(x1 + dx2pts * i), Math.round(y1 + dy2pts * i),this.ptSize,this.ptSize);
	}
}

function mkRect(x, y, w, h, thickness)
{
	this.drawLineH(x, y, h);
	this.drawLineH(x+w, y,h);
	this.drawLineW(x, y+h,w+thickness);
	this.drawLineW(x, y,w);
}


function jsGraphics(id, wnd)
{
	this.setColor = new Function('arg', 'this.color = arg.toLowerCase();');

	this.setStroke = function(a,b)
	{
		this.stroke = a;
		this.ptSize = b;
			this.drawRect = mkRect;
			this.drawLinePts = mkLinePts;
			this.drawLineH = mkLineH;
			this.drawLineW = mkLineW;
	};
	
	this.drawPolylinePts = function(x, y, d) {
		for (var i=0; i<x.length - 1 ; i++) this.drawLinePts(x[i], y[i], x[i+1], y[i+1], d);
	}


	this.setPrintable = function()
	{
		this.mkDivPt = mkDivPt;
	};

	this.clear = function()
	{
		this.htm = "";
		if (this.cnv) this.cnv.innerHTML = this.defhtm;
	};


	this.setStroke(1);
	this.color = '#000000';
	this.htm = '';
	this.wnd = wnd || window;

	if (!(jg_ie || jg_dom || jg_ihtm)) chkDHTM();
	if (typeof id != 'string' || !id) {this.paint = pntDoc;}
	else
	{
		this.cnv = document.all? (this.wnd.document.all[id] || null)
			: document.getElementById? (this.wnd.document.getElementById(id) || null)
			: null;
		this.defhtm = (this.cnv && this.cnv.innerHTML)? this.cnv.innerHTML : '';
		this.paint = jg_dom? pntCnvDom : jg_ie? pntCnvIe : jg_ihtm? pntCnvIhtm : pntCnv;
	}

	this.setPrintable();
}