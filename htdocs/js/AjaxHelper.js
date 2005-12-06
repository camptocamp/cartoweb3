/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/*
 * Used by: AjaxHandler.js
 */

/**
 * Provides helper methods for AjaxHandler
 * i.e. form fields collector, query builder, event handler, findpos
 */
var AjaxHelper = {

    /**
     * General method that builds a request string from an HTMLFormElement and
     * returns a formatted string: 'elemeentName=elemeentValue' or 'elemeentName='
     * @param HTMLFormElement
     */
    buildQueryFrom: function(htmlElement) {
    	
    	if (htmlElement == undefined)
    		return;
    	
    	inputType = htmlElement.getAttribute('type');
	    paramName = htmlElement.getAttribute('name');
	    if (inputType == 'text')
	    	paramValue = htmlElement.value
	    else
	    	paramValue = htmlElement.getAttribute('value')
	    	
	    if (paramValue != null) {
     		return paramName + '=' + paramValue;
	    } else {	    
            // HTTP POST requests parameters HAVE TO be followed by '='
            // even when they have no associated value
     		return paramName + '=';
     	}
	},   


    /**
     * Returns the HTTP POST/GET string from the form specified by formId,
     * by creating a query string from all the given form's inputs and selects
     * but the submit and image types inputs.
     *
     * @param string Id of the form to parse.
     * @return string HTTP GET string from formId
     */
    buildQuery: function(formId) {
        
        var queryString = ''; // String to be returned
        formElement = document.getElementById(formId);

        /*
         * Process <input> elements
         */
        inputElements = formElement.getElementsByTagName('input');
        for (var i=0; i < inputElements.length; i++) {
            // Collect the current input only if it's is not 'submit' or 'image'
            currentElement = inputElements.item(i);
            inputType = currentElement.getAttribute('type');
            
            if (inputType == 'radio' || inputType == 'checkbox') {
                if (currentElement.checked)
                    queryString = queryString + this.buildQueryFrom(currentElement) + '&';
                    
            } else if (inputType == 'submit' || inputType == 'image') {
                // Do nothing. Sending the submit inputs in POST Request would make
                // the serverside act like all buttons on the form were clicked.
                // And we don't want that.
                
            } else {    
                queryString = queryString + this.buildQueryFrom(currentElement) + '&';
            }
        }

        /*
         * Process <select> elements
         */
        selectElements = formElement.getElementsByTagName('select');
        for (var i=0; i < selectElements.length; i++) {
            // Get the param name (i.e. fetch the name attr)
            currentElement = selectElements.item(i);
            paramName = currentElement.getAttribute('name');
            
            // Get the param value(s)
            // (i.e. fetch the checked options element's value attr)
            optionElements = currentElement.getElementsByTagName('option');
            for (var j=0; j < optionElements.length; j++) {
                currentElement = optionElements.item(j);
                if (currentElement.selected) {
                        paramValue = currentElement.getAttribute('value');
                    if (paramValue == null) paramValue = '';
                    queryString = queryString + paramName + '=' + paramValue + '&';
                }
            }
        }
        return queryString;
    },

            
    /**
     * Returns the query string contained in the href attribute
     * of the given HTML <a> element object.
     * @param HTMLLinkElement <a> element object
     * @return string HTTP GET string from ahrefElement
     */
    getQueryString: function(ahrefElement) {
    	if (ahrefElement == undefined)
    		return;
        // Retrieves the string after the '?' of the element's href attribute.
        startChar = ahrefElement.indexOf('?');
        endChar = ahrefElement.length;
        if (startChar != '-1') {
            var queryString = ahrefElement.substring(startChar+1, endChar);
        } else {
            var queryString = '';
        }
        return queryString;
    },
	
	/**
	 * Cross-browser event handling (IE5/NS6/Moz/Gecko)
	 * By Scott Andrew
	 * @example addEvent(window, 'load', addListeners, false);
	 * 
	 * @param HTMLElement HTML DOM Element object to attach the listener on
	 * @param string Type of the triggering event (i.e. click, change, ...)
	 * @param string Funtion name to be called on event trigger
	 * @param bool Prevent event from bubbling through the DOM
	 */
	addEvent: function (elm, evType, fn, useCapture) {
	  if (useCapture == undefined)
	  	useCapture = false;
	  if (elm.addEventListener) {
	    elm.addEventListener(evType, fn, useCapture);
	    return true;
	  } else if (elm.attachEvent) {
	    var r = elm.attachEvent('on' + evType, fn);
	    return r;
	  } else {
	    elm['on' + evType] = fn;
	  }
	},

	/* 
	 * Finds clicked position on an element
	 * (simulates the posting of a <input type="image" ...>)
	 * Used by AjaxPlugins.Location.Actions.pan.buildPostRequest()
	 *
	 * This seems bogous on Safari!
	 *
	 * @param Event
	 * @return Object Object with x and y properties, containing the clicked
	 *                position, relative to the clicked element
	 */
	getClickedPos: function(ev) {
	
		function findPosX(obj)
		{
			var curleft = 0;
			if (obj.offsetParent)
			{
				while (obj.offsetParent)
				{
					curleft += obj.offsetLeft
					obj = obj.offsetParent;
				}
			}
			else if (obj.x)
				curleft += obj.x;
			return curleft;
		}
		
		function findPosY(obj)
		{
			var curtop = 0;
			if (obj.offsetParent)
			{
				while (obj.offsetParent)
				{
					curtop += obj.offsetTop
					obj = obj.offsetParent;
				}
			}
			else if (obj.y)
				curtop += obj.y;
			return curtop;
		}
	
		var e = window.event ? window.event : ev;
		var t = e.target ? e.target : e.srcElement;
		
		var mX, my;
		if (e.pageX && e.pageY) {
			mX = e.pageX;
			mY = e.pageY;
		} else {
			mX = e.clientX;
			mY = e.clientY;
		}
		
		mX += document.body.scrollLeft;
		mY += document.body.scrollTop;
		
		return {
			x: mX - findPosX(t),
			y: mY - findPosY(t)
		};
	},
	
	getCurrentTool: function() {
		var selectedTool = null;
        inputElements = document.getElementsByTagName('input');
        for (var i=0; i < inputElements.length; i++) {
            currentElement = inputElements.item(i);
            inputType = currentElement.getAttribute('type');
            inputName = currentElement.getAttribute('name');
            if (inputType == 'radio' && inputName == 'tool') {
                if (currentElement.checked) {
                    selectedTool = currentElement.value;
                    break;
                }
            }                    
        }
        return selectedTool;
    }
}