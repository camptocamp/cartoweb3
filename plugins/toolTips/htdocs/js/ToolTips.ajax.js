// This object is standalone and doesn't use AjaxHandler
AjaxPlugins.ToolTips = {

    /**
     * Delay before querying the mouseovered point
     * @var int [milliseconds]
     */
    toolTipsTimeout: 250,

    /**
     * Tolerated mouse delta before sending a new AJAX query:
     * after an AJAX query was sent, no other query will follow until the mouse
     * has moved x pixels from the last queried position
     * @var int [pixels]
     */
    toolTipsTolerance: 2,

    /**
     * Id of the mouseover GUI container (HTMLElement)
     * @var string [HTMLElement id]
     */
    toolTipsSwitchId: 'toolTipsSwitch',

    /**
     * Id of the waiting image element
     * @var string [HTMLElement id]
     */
    tooltipWaitingId: 'tooltipWaiting',

    /**
     * Id of the listening HTML element (where to attach events)
     * @var string [HTMLElement id]
     */
    eventDivId: 'map_rootLayer',

    /**
     * Id of the optional imagemap HTML element
     * @var string [HTMLElement id]
     */
    imagemapId: null,

    /**
     * Url of the ToolTips AJAX service
     * (set in dhtmlcode.tpl)
     * @var string
     */
    serviceUrl: null,
    /**
     * Current CartoWeb scale
     * (set in dhtmlcode.tpl)
     * @var float [geo units per pixel]
     */
    scale: null,
    /**
     * Current CartoWeb langugage
     * @var string
     */
    lang: null,
    /**
     * Current CartoWeb encoding charset
     * (set in dhtmlcode.tpl)
     * @var string
     */
    charSet: 'utf-8',

    /**
     * Defines if mouse is already over an area
     * @var boolean
     */
    isOverArea: false,

    /**
     * Ajax request deactivation can be forced
     * For example when cursor in over tooltip
     * @var boolean
     */
    isAjaxForcedDeactivated: false,

    /**
     * Defines if mouse is over the tooltip
     * @var boolean
     */
    isOverTooltip: false,

    /*
     * Internal use
     */
    _lastMousePos: [null, null],
    _timerMouseMove: null,
    _timerMouseOut: null,
    _ajaxRequest: null,
    _currentOverArea: null,


    init: function() {
        this.delayInit();
    },
    delayInit: function(timesExecuted) {
        if (typeof(timesExecuted) == 'undefined')
            timesExecuted = 0;
        if ($('map_raster_img') == null) {
            if (timesExecuted < 20)
                setTimeout(this.delayInit, 500);
        } else {
            // The code below will execute only when map_raster_img exists
            AjaxPlugins.ToolTips.processInit();
        }
    },
    processInit: function() {
        Event.observe(this.eventDivId, 'mousemove', this.mouseMove);
        Event.observe(this.eventDivId, 'mouseout', this.mouseOut);
        this._result = new AjaxPlugins.ToolTips.Result();
    },

    isAjaxActive: function() {
        return (($(this.toolTipsSwitchId) == null ||
                $(this.toolTipsSwitchId).checked)
                && !this.isOverArea
                && !this.isAjaxForcedDeactivated);
    },
    mouseOut: function(e) {
        if (checkMouseLeave(this, e)) {
            Logger.note('AjaxPlugins.ToolTips: mouse is out map');

            clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);

            if (!AjaxPlugins.ToolTips.isOverArea) {
                AjaxPlugins.ToolTips.abortRequest();
                AjaxPlugins.ToolTips._result.hide();
            }
        }
    },
    mouseMove: function() {
        if (AjaxPlugins.ToolTips.isAjaxActive()) {
            clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);
            AjaxPlugins.ToolTips._timerMouseMove = setTimeout(
                "AjaxPlugins.ToolTips.checkMove()",
                AjaxPlugins.ToolTips.toolTipsTimeout
            );
            AjaxPlugins.ToolTips._result.hide();
        }
    },
    hasMoved: function() {
        var toleranceGeo = AjaxPlugins.ToolTips.toolTipsTolerance
                          * AjaxPlugins.ToolTips.scale;

        var xHasMoved = geoX < AjaxPlugins.ToolTips._lastMousePos[0] - toleranceGeo
                     || geoX > AjaxPlugins.ToolTips._lastMousePos[0] + toleranceGeo
        var yHasMoved = geoY < AjaxPlugins.ToolTips._lastMousePos[1] - toleranceGeo
                     || geoY > AjaxPlugins.ToolTips._lastMousePos[1] + toleranceGeo
        return xHasMoved || yHasMoved;
    },

    checkMove: function() {
        if (AjaxPlugins.ToolTips.hasMoved()) {
            this.sendRequest();
            this._lastMousePos = [geoX, geoY];
        }
    },

    displayResult: function(result) {
        this._result.stopWaiting();
        var responseHtml = result.responseText;
	    if (responseHtml != '' && responseHtml != null ) {
		    this._result.content = responseHtml;
            this._result.show();
        } else {
            Logger.note('AjaxPlugins.ToolTips: no result');
        }
    },

    switchOnOff: function(checkBox) {
        if (!checkBox.checked) AjaxPlugins.ToolTips._result.hide();
    },
    mouseOverArea: function() {
        AjaxPlugins.ToolTips.isOverArea = true;
    },
    mouseMoveArea: function(e) {
        e = e || window.event;
        var target = e.srcElement || e.target;
        clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);
        AjaxPlugins.ToolTips._currentOverArea = target;
        AjaxPlugins.ToolTips._timerMouseMove = setTimeout(
            "AjaxPlugins.ToolTips.checkMoveArea()",
            AjaxPlugins.ToolTips.toolTipsTimeout
        );
    },
    mouseOutArea: function() {
        clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);
        AjaxPlugins.ToolTips.abortRequest();
        AjaxPlugins.ToolTips.isOverArea = false;
    },
    checkMoveArea: function() {
        if (AjaxPlugins.ToolTips.hasMoved()) {
            this.showAttributes();
            this._lastMousePos = [geoX, geoY];
        }
    },
    buildQueryString: function(argObject) {
        // query string for an area with id
        if (argObject && argObject.layer && argObject.id) {
            Logger.trace('AjaxPlugins.ToolTips: sending request for feature' +
                          ': layer ' + argObject.layer + ', id ' + argObject.id );
            return queryString = 'layer=' + argObject.layer +
                                 '&id=' + argObject.id;
        }
        // query string for mouseover timeout
        else {
            Logger.trace('AjaxPlugins.ToolTips: sending request for coords '+geoX+', '+geoY+'...');
            return queryString = 'geoX='+geoX+
                             '&geoY='+geoY+
                             '&charSet='+this.charSet+
                             '&lang='+this.lang;
        }
    },

    sendRequest: function(argObject) {
        this.abortRequest();
        this._result.reset();
        this._result.wait();

        var url = this.serviceUrl + '&' + this.buildQueryString(argObject);
        Logger.send('Request sent with url :' + url);
		this._ajaxRequest = new Ajax.Request (
            url,
			{method: 'get', onComplete: showResponse, onFailure: reportError}
		);

		function showResponse(result) {
		    AjaxPlugins.ToolTips.displayResult(result);
		}
		function reportError(e) {
			Logger.error('Error: ' + e.toString());
		}

    },

    abortRequest: function() {
        // Aborts the running request, if any
        if (this._ajaxRequest != null) {
            Logger.note('AjaxPlugins.ToolTips: AJAX request aborted!');
            this._ajaxRequest.transport.abort();
            this._ajaxRequest = null;
        }
    },

    /**
     * Loads an blank image to use map areas with
     */
    useMap: function() {
        var image = xCreateElement('img');
        image.style.position = "absolute";
        image.src = "gfx/layout/blank.gif";
        image.border = 0;
        image.id = mainmap.id + "_imagemap";
        this.imagemapId = image.id;
        image.width = mainmap.getDisplay('map')._width;
        image.height = mainmap.getDisplay('map')._height;
        image.useMap = "#map1";
        image._display = mainmap.getDisplay('map');
        xAppendChild(mainmap.getDisplay('map').rootDisplayLayer, image);

        // add an event for onmouseover to all areas
        for (var i = 0; i < $('map1').childNodes.length; i++) {
            if ($('map1').childNodes[i].tagName != 'AREA') continue;

            // clear title element to avoid double tooltips
            $('map1').childNodes[i].title = '';

            $('map1').childNodes[i]._display = mainmap.getDisplay('map');
            Event.observe($('map1').childNodes[i].id, 'mousemove', this.mouseMoveArea, false);
            Event.observe($('map1').childNodes[i].id, 'mouseout',
              function() {AjaxPlugins.ToolTips._result.hide()}, false);

            // activate or deactivate mouseover timeout management
            Event.observe($('map1').childNodes[i].id, 'mousemove',
              this.mouseOverArea, false);
            Event.observe($('map1').childNodes[i].id, 'mouseout',
              this.mouseOutArea, false);
        }
    },

    /**
     * Display feature attributes relative to an area tag in Logger
     * TODO : use a generic displayResults function
     */
    showAttributes: function() {

        element = this._currentOverArea;
        Logger.trace('AjaxPlugins.ToolTips: Mouse over feature: ' +
                     'layer ' + element._fid.layer + ', id ' + element._fid.id);
        if (element._attributes) {
            // manually build result as it was a XML HTTP response
            var result = {};
            result.responseText = '<table>';
            for (var name in element._attributes) {
                // avoid prototype constructor (getObjectClass)
                if (typeof element._attributes[name] == 'function') {
                    continue;
                }
                result.responseText += '<tr><td>' + name + '</td>';
                result.responseText += '<td>: ' + element._attributes[name] + '</td></tr>';
            }
            result.responseText += '</table>';

            AjaxPlugins.ToolTips.displayResult(result);
            AjaxPlugins.ToolTips.abortRequest();
        } else {
           AjaxPlugins.ToolTips.sendRequest(element._fid);
        }

    },

    handleResponse: function(pluginOutput) {
    	var imagemapHtmlCode = pluginOutput.htmlCode.imagemapHtmlCode;
        AjaxHandler.updateDomElement('map1', 'innerHTML', imagemapHtmlCode);
        AjaxPlugins.ToolTips.useMap();
        xHide(mainmap.getDisplay('map').eventPad);
        var imagemapJavascriptCode = pluginOutput.htmlCode.imagemapJavascriptCode;
        eval(imagemapJavascriptCode);
    }
};


/**
 * may be overwriten
 */

AjaxPlugins.ToolTips.Result = Class.create();
AjaxPlugins.ToolTips.Result.prototype = {
    /**
     * title for the result
     * @var string
     */
    title: '',

    /**
     * html content
     * @var string
     */
    content: '',

    initialize: function(element) {
        // needed to get the 'overDiv' element when the page is loaded
        overlib('');
        nd();

        // some things to get sticky overlib working
        function mouseOverTooltip() {
            AjaxPlugins.ToolTips.isAjaxForcedDeactivated = true;
            clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);
            clearTimeout(AjaxPlugins.ToolTips._timerMouseOut);
        }
        function mouseOutTooltip(e) {
             AjaxPlugins.ToolTips.isAjaxForcedDeactivated = false;
        }
        Event.observe('overDiv', 'mouseover', mouseOverTooltip, false);
        Event.observe('overDiv', 'mouseout', mouseOutTooltip, false);

        Logger.note("Result initialized");
    },

    reset: function() {
        this.title = '';
        this.content = '';
    },

    wait: function() {
        if ($(AjaxPlugins.ToolTips.tooltipWaitingId) != null)
            $(AjaxPlugins.ToolTips.tooltipWaitingId).style.display = "block";
        overlib('');
        nd();
    },

    stopWaiting: function() {
        if ($(AjaxPlugins.ToolTips.tooltipWaitingId) != null)
            $(AjaxPlugins.ToolTips.tooltipWaitingId).style.display = "none";
    },

    show: function() {
        // result can be simply displayed somewhere in the page
        // AjaxHandler.updateDomElement('resultContainer', 'innerHTML', this.content);

        // or in an overlib tooltip
        return overlib(this.content, CAPTION, this.title, STICKY, NOCLOSE,
            TIMEOUT, 3000);
    },

    hide: function() {
        return nd();
    }
}

function checkMouseLeave(element, evt) {
  if (element.contains && evt.toElement) {
    return !element.contains(evt.toElement);
  }
  else if (evt.relatedTarget) {
    return !containsDOM(element, evt.relatedTarget);
  }
}
function containsDOM(container, containee) {
  var isParent = false;
  do {
    if ((isParent = container == containee))
      break;
    containee = containee.parentNode;
  }
  while (containee != null);
  return isParent;
}