var AjaxHelper = {

    buildHttpRequestFrom: function(htmlElement) {
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
     * Returns the HTTP POST string from the form specified by formId,
     * by creating a query string from all the given form's select inputs
     * but the submit and image types inputs.
     * @param formId string Id of the form to parse.
     * @return string HTTP GET string from formId
     */
    buildHttpPostRequest: function(formId) {
        
        var queryString = ''; // String to be returned
        formElement = document.getElementById(formId);

        // Process <input> elements
        inputElements = formElement.getElementsByTagName('input');
        for (var i=0; i < inputElements.length; i++) {
            // Collect the current input only if it's is not 'submit' or 'image'
            currentElement = inputElements.item(i);
            inputType = currentElement.getAttribute('type');
            
            if (inputType == 'radio' || inputType == 'checkbox') {
                if (currentElement.checked)
                    queryString = queryString + this.buildHttpRequestFrom(currentElement) + '&';
                    
            } else if (inputType == 'submit' || inputType == 'image') {
                // Do nothing. Sending the submit inputs in POST Request would make
                // the serverside act like all buttons on the form were clicked.
                // And we don't want that.
                
            } else {    
                queryString = queryString + this.buildHttpRequestFrom(currentElement) + '&';
            }
        }

        // Process <select> elements
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
     * @param ahref HTMLElement HTML <a> element object
     * @return string HTTP GET string from ahrefElement
     */
    getQueryStringFrom: function(ahrefElement) {
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

	// Creates an associative array containing the corePluginsVariables'
	// name and value (name as array key, value as array value).
	getCorepluginsVariables: function(xmlDocument) {
	    var corePluginsVariables = xmlDocument.getElementsByTagName('variable');
	    varArray = new Array(corePluginsVariables.length);
		for (var i=0; i < corePluginsVariables.length; i++) {
			varName = corePluginsVariables.item(i).getAttribute('name');
			varValue = corePluginsVariables.item(i).getAttribute('value');
			varArray[varName] = varValue;
		}
		return varArray;
	}, 						

	// Creates an array containing the plugins name to refresh
	getPluginsToRefresh: function(xmlDocument) {
	    var pluginNames = xmlDocument.getElementsByTagName('pluginToRefresh');
	    varArray = new Array(pluginNames.length);
		for (var i=0; i < pluginNames.length; i++) {
			varArray[i] = pluginNames.item(i).getAttribute('name');
		}
		return varArray;
	},
	
// Eventlistener handling, for testing purpose only.
// !!! Use the already implemented EventHandler.js
	addEvent: function (elm, evType, fn, useCapture) {
	  // cross-browser event handling for IE5+, NS6+ and Mozilla/Gecko
	  // By Scott Andrew
	  // Example: addEvent(window, 'load', addListeners, false);
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
	// End of addEvent 

	// Finds clicked position on an element
	// (simulates the posting of a <input type="image" ...>)
	// Used by AjaxPlugins.Location.Actions.mapPanByKeymap.buildPostRequest()
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
	} 
}
