// This object is standalone and doesn't use AjaxHandler
AjaxPlugins.ToolTips = {

    /**
     * Delay before querying the mouseovered point
     * @var int [milliseconds]
     */
    timeoutBeforeRequest: 250,

    /**
     * Tolerated mouse delta before sending a new AJAX query:
     * after an AJAX query was sent, no other query will follow until the mouse
     * has moved x pixels from the last queried position
     * @var int [pixels]
     */
    toolTipsTolerance: 2,

    /**
     * Id of the checkbox for switching on/off the tooltips
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

    /*
     * Internal use
     */
    _lastMousePos: [null, null],
    _timerMouseMove: null,
    _timerMouseOut: null,
    _ajaxRequest: null,

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
        Event.observe(this.eventDivId, 'mousemove', this.mouseMove.bindAsEventListener(this));
        Event.observe(this.eventDivId, 'mouseout', this.mouseOut.bindAsEventListener(this));
        this._result = new AjaxPlugins.ToolTips.Result();
    },

    isToolTipsActive: function() {
        return (($(this.toolTipsSwitchId) == null ||
                $(this.toolTipsSwitchId).checked));
    },
    
    mouseOut: function(e) {
        if (this.checkMouseLeave($(this.eventDivId), e)) {
//            Logger.note('AjaxPlugins.ToolTips: mouse is out map');
            clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);
        }
    },
    
    mouseMove: function() {
        if (AjaxPlugins.ToolTips.isToolTipsActive()) {
            clearTimeout(AjaxPlugins.ToolTips._timerMouseMove);
            AjaxPlugins.ToolTips._timerMouseMove = setTimeout(
                "AjaxPlugins.ToolTips.checkMove()",
                AjaxPlugins.ToolTips.timeoutBeforeRequest
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
            // TODO, in production mode don't show exceptions
            if (responseHtml.toLowerCase().indexOf('Exception') != -1) {
                this._result.content = "TODO, in production mode don't show exceptions";
            }
            this._result.content = responseHtml;
            this._result.show();
        } else {
            Logger.note('AjaxPlugins.ToolTips: no result');
        }
    },

    switchOnOff: function(checkBox) {
        if (!checkBox.checked) AjaxPlugins.ToolTips._result.hide();
    },
    
    buildQueryString: function(argObject) {
        Logger.trace('AjaxPlugins.ToolTips: sending request for coords '+geoX+', '+geoY+'...');
        return queryString = 'geoX='+geoX+
                         '&geoY='+geoY+
                         '&charSet='+this.charSet+
                         '&lang='+this.lang;
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

    handleResponse: function(pluginOutput) {
        eval(pluginOutput.variable.tooltips_active);
    },

    checkMouseLeave: function(element, evt) {
        if (element.contains && evt.toElement) {
            return !element.contains(evt.toElement);
        } else if (evt.relatedTarget) {
            return !this.containsDOM(element, evt.relatedTarget);
        }
    },

    containsDOM: function(container, containee) {
        var isParent = false;
        do {
            if ((isParent = container == containee)) break;
            containee = containee.parentNode;
        } while (containee != null);
        return isParent;
    }
};

/*
 * Plugin actions
 */
AjaxPlugins.ToolTips.Actions = {

};

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
    
    prevCursor: null,
    
    /**
     * Timeout in ms before the tooltips is hidden
     * is set in the constructor
     */    
    timeoutBeforeHide: 3000,

    initialize: function(options) {
        // needed to get the 'overDiv' element when the page is loaded
        overlib('');
        nd();
        
//        this.timeoutBeforeHide = options.timeoutBeforeHide;

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
        if (this.prevCursor == null) {
            this.prevCursor = $(AjaxPlugins.ToolTips.eventDivId).style.cursor;
        }
        $(AjaxPlugins.ToolTips.eventDivId).style.cursor = "wait";
        overlib('');
        nd();
    },

    stopWaiting: function() {
        if (this.prevCursor != null) {
            $(AjaxPlugins.ToolTips.eventDivId).style.cursor = this.prevCursor;
        }
    },

    show: function() {
        // result can be simply displayed somewhere in the page
        // AjaxHandler.updateDomElement('resultContainer', 'innerHTML', this.content);

        // or in an overlib tooltip
        return overlib(this.content, CAPTION, this.title,
                           STICKY, NOCLOSE, TIMEOUT, this.timeoutBeforeHide);
    },

    hide: function() {
        return nd();
    }
}