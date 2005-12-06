/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/*
 * Uses: Prototype-1.3.1.js - for Ajax.Request class and $() function
 * Uses: AjaxHelper.js - for common features (i.e. getHttpPostRequest)
 */

/**
 * CartoWeb's AJAX core mecanism.
 * Provides action binding and action triggering.
 * Handles async requests, responses, and plugin Javascript logic calls.
 * 
 * Entry points: attachAction() and doAction().
 */
AjaxHandler = {

	baseUrl: '', // uses current url if empty
	cartoFormId: 'carto_form',

	/**
	 * Sets cartoweb's base url
	 * (format: http://hostname/dir1/dir12/dir121/script_name.ext)
	 * If set to null or '', the browser displayed url will be used
	 * @param string Cartoweb's base url
	 */
	setBaseUrl: function(baseUrl) {
		this.baseUrl = baseUrl;
	},

	/**
	 * Returns cartoweb's base url
	 * @return string Cartoweb's base url
	 */
	getBaseUrl: function() {
		return this.baseUrl;
	},

	/**
	 * Sets Cartoweb's form id
	 * @param string Cartoweb's form id
	 */
	setCartoFormId: function(cartoFormId) {
		this.cartoFormId = cartoFormId;
	},

	/**
	 * Returns Cartoweb's form id
	 * @return string Cartoweb's form id
	 */
	getCartoFormId: function() {
		this.cartoFormId;
	},

    /**
     * Returns the query string from the whole carto_form
     * or, if specified, the form id given by the formId argument
     *
     * @param string Id of the form to parse (optional)
     * @return string HTTP GET string from formId
     */
	buildPostRequest: function(formId) {
		if (formId == undefined)
			formId = this.cartoFormId;
		return AjaxHelper.buildQuery(formId);
	},

	buildRequestFrom: function(htmlElement) {
		return AjaxHelper.buildQueryFrom(htmlElement);
	},
    getQueryString: function(ahrefElement) {
	    AjaxHelper.getQueryString(ahrefElement);
    },
	 
	/**
	 * Handles the Cartoclient AJAX response, passing to each plugin its output
	 * and the argObject
	 * to it's Javascript plugin image
	 * (i.e. AjaxPlugins.[pluginName].handleResponse()
	 */
	handlePluginReponse: function(response, argObject) {

		Logger.act('Updating GUI');
		
		/*
		 * Creates an array with all plugin responses: array[pluginName][paramType][paramId] = paramValue
		 * (paramType is either htmlCode or variable)
		 */
		pluginElements = response.responseXML.getElementsByTagName('plugin');
		pluginArray = new Array(pluginElements.length);
		for (i=0; i<pluginElements.length; i++) {

			currentPluginElement = pluginElements.item(i);

			variableElements = currentPluginElement.getElementsByTagName('variable')
			varArray = new Array(variableElements.length);
			for (j=0; j<variableElements.length; j++) {
				currentVariableElement = variableElements.item(j);
				varArray[currentVariableElement.getAttribute('id')] = currentVariableElement.getAttribute('value');
			}

			htmlCodeElements = currentPluginElement.getElementsByTagName('htmlCode')
			htmlCodeArray = new Array(htmlCodeElements.length);
			for (j=0; j<htmlCodeElements.length; j++) {
				currentHtmlCodeElement = htmlCodeElements.item(j);
				htmlCodeArray[currentHtmlCodeElement.getAttribute('id')] = currentHtmlCodeElement.getAttribute('value');
			}
			
			currentPluginName = currentPluginElement.getAttribute('name');
			pluginArray[i] = new Array(3);
			pluginArray[i]['pluginName'] = currentPluginName;
			pluginArray[i]['variables'] = varArray;
			pluginArray[i]['htmlCode'] = htmlCodeArray;
		}
		
		/*
		 * Calls all plugins that gave a response (= are present in pluginArray)
		 * and passes their respective param array
		 */
		for (i=0; i<pluginArray.length; i++) {
			pluginName = pluginArray[i]['pluginName'];
			pluginName = pluginName.charAt(0).toUpperCase() + pluginName.substr(1); // ucfisrt()
			eval('AjaxPlugins.' + pluginName + '.handleResponse(pluginArray[i], argObject)');
		}
	},

    /**
     * Handles the AJAX request to the Cartoclient.
     * @param string Id of the triggered action (format: [PluginName].[ActionName]
     * @param Object Object containing arbitrary data for plugins' JS part's use
     * @param Object Object containing POST and/or GET queries for the Cartoclient
     */
	actionRequest: function(actionId, argObject, queryObject) {

		Logger.trace('Initiating async request');

		var url = this.getBaseUrl()
			+ '?ajaxActionRequest=' + actionId + '&'
			+ queryObject.get;
		var myAjax = new Ajax.Request (
			url,
			{method: 'post', postBody: queryObject.post,
				onComplete: function(response) {
				Logger.trace('Receiving response');
					if (response.responseXML == undefined) {
						showFaillure = confirm('Ajax response is no XML, probably a CartoClient faillure.\r\nClick OK to show it.');
						if (showFaillure) {
							ajaxErrorDivElement = document.createElement('div');
							ajaxErrorDivElement.id = 'ajaxError';
							ajaxErrorDivElement.style.position = 'absolute';
							ajaxErrorDivElement.style.zIndex = 100;
							ajaxErrorDivElement.style.top = 0;
							ajaxErrorDivElement.style.left = 0;						
							ajaxErrorDivElement.style.padding = 5;
							ajaxErrorDivElement.style.color = 'black';
							ajaxErrorDivElement.style.backgroundColor = 'silver';
							ajaxErrorDivElement.style.border = '3px solid red';						
							ajaxErrorDivElement.innerHTML = response.responseText;
							ajaxErrorDivElement.onclick = function() {
								// TODO: Remove this error div when clicked
							};
							$('carto_form').appendChild(ajaxErrorDivElement);
						}
					} else {
						AjaxHandler.handlePluginReponse(response, argObject);
						// Call onAfterAjaxCall method for the called action
						requestedPluginName = actionId.substr(0, actionId.indexOf('.'));
						requestedActionName = actionId.substr(actionId.indexOf('.')+1);
						eval('AjaxPlugins.' + requestedPluginName + '.Actions.' + requestedActionName + '.onAfterAjaxCall(argObject)');
						AjaxPlugins.Common.onAfterAjaxCall(actionId);
					}
				}
			}
		);
	},
	
	/**
	 * Triggers an action: this will start the CartoWeb's AJAX mecanism
	 * (use this method when triggering action with HTML onclick="" attribute)
     * @param string Id of the triggered action (format: [PluginName].[ActionName]
     * @param Object Object containing arbitrary data for plugins' JS part's use
	 */
	doAction: function(actionId, argObject) {

		Logger.act('Action '+ actionId +' triggered');

        /*
         * Creates the argObject and/or adds actionName and pluginName properties,
         * used by plugins predefined methods
         */
		if (argObject == undefined)
			argObject = {};
		pluginName = actionId.substr(0, actionId.indexOf('.'));
		actionName = actionId.substr(actionId.indexOf('.')+1);
		argObject.actionName = actionName;
		argObject.pluginName = pluginName;
		/*
		 * Ask the plugin that triggered the action to build GET and POST queries
		 */
		eval('httpPostQuery = AjaxPlugins.' + pluginName + '.Actions.' + actionName + '.buildPostRequest(argObject)');
		eval('httpGetQuery = AjaxPlugins.' + pluginName + '.Actions.' + actionName + '.buildGetRequest(argObject)');
		/*
		 * Call the common and plugin's onBeforeAjaxCall logic
		 */
		eval('AjaxPlugins.' + pluginName + '.Actions.' + actionName + '.onBeforeAjaxCall(argObject)');
		AjaxPlugins.Common.onBeforeAjaxCall(actionId);
		/*
		 * Call actionRequest() to perform the AJAX call
		 */
		this.actionRequest(actionId, argObject, {post: httpPostQuery, get: httpGetQuery});
	},
	
	
	/**
	 * Attaches an action to the given element (HTML element),
	 * preventing Javascript errors
	 * @param HTMLElement HTML DOM Element object to attach the listener on
     * @param string Id of the triggered action (format: [PluginName].[ActionName]
     * @param Object Object containing arbitrary data for plugins' JS part's use
	 * @param bool Prevent event from bubbling through the DOM
	 */
	attachAction: function(elementId, evType, actionId, argObject, useCapture) {
		Logger.trace('AjaxHandler.attachAction(): Attaching action:'+actionId+' to element id:'+elementId+'...');
		if (typeof(useCapture) == 'undefined')
			useCapture = false;		
		if (typeof(argObject) == 'undefined')
			argObject = {};
		if (typeof(elementId) == 'string') {
			element = $(elementId);
		} else {
			Logger.error('argument elementId must be a string (\''+typeof(elementId)+'\' received)');
			return false;			
		}
		if (typeof(element) == undefined || element == null) {
			Logger.warn('element '+elementId+' does not exist!');
			return false;
		}
		// Attaches the listener to the evType event on the element
		AjaxHelper.addEvent(element, evType, function(event) {
		    // Fixes event and target issues
			var event = window.event ? window.event : event;
			var target = event.target ? event.target : event.srcElement;
			// Defines predefined argObject properties: event & target
			argObject.event = event;
			argObject.target = target;
			AjaxHandler.doAction(actionId, argObject);
			// Prevent default comportment (= onClick="return false;")
			if (event.returnValue) 
				event.returnValue = false;
			if (event.preventDefault)
				event.preventDefault();
		}, useCapture);
		
		Logger.confirm('Done.');
		return true;
	},

	
	/**
	 * Updates the given property of the given element with the given value
	 * avoiding Javascript errors, and returns true if success, false otherwise
	 * @param string DOM element id
     * @param string DOM element property
     * @param string value of the 
	 * @return bool true if update succeeds, false otherwise
	 */
	updateDomElement: function(elementId, property, value) {

		// These checks prevent Javascript errors		
		element = $(elementId);
		Logger.trace('AjaxHandler.updateDomElement(): Updating <b>'+elementId+'</b> element\'s <b>'+property+'</b>\'s value...');

		if (typeof(elementId) != 'string') {
			Logger.error('property argument must be a string!');
			return false;			
		}
		
		if (typeof(property) != 'string') {
			Logger.error('property argument must be a string!');
			return false;
		}
		if (typeof(element) == 'undefined' || element == null) {
			Logger.warn('given element was not found in the DOM!');
			return false;
		}

		eval('elementAttr = element.'+property);
		if (typeof(elementAttr) == 'undefined') {
			Logger.warn('given element\'s property was not found in the DOM!');		
			return false;
		}
		eval('element.'+property+' = value');
		Logger.confirm('Done.');
		return true;
	},


	// waitFor() is Bogous, don't use it for now
	waitFor: function(elementId, functionToExecute, timeout, waitTimeout,  timesChecked) { // timeout in seconds
		if (typeof(elementId) == 'undefined')
			elementId = '';
		if (typeof(functionToExecute) == 'undefined')
			functionToExecute = function() {};
		if (typeof(timeout) == 'undefined')
			timeout = 5; // timeout in seconds
		if (typeof(waitTimeout) == 'undefined')
			waitTimeout = 5000; // waitTimeout in ms
		if (typeof(timesChecked) == 'undefined') {
			timesChecked = 0;
		} else {
			timesChecked += 1;
		}

		if (timesChecked > 1)
			alert('waitTimout('+timesChecked+'): ' + waitTimeout * timesChecked / 1000 + ' < ' + timeout);
		
		// Initialise only if elementId element exists, else
		// wait a little while before trying again
		if ($(elementId) == null) {
			if (waitTimeout * timesChecked / 1000 < timeout) {
				setTimeout(AjaxHandler.waitFor(elementId, functionToExecute, timeout, waitTimeout, timesChecked), waitTimeout);
			} else {
				alert('AjaxHandler.waitFor(): Timeout reached, element ' + elementId + ' still doesn\'t exists...');
			}
		} else {
			// When the elementId element exists, perform the given function
			functionToExecute();
		}	
	}
};



Logger = {	
	/* Avaiable log levels:
	 * 0 = none
	 * 1 = acts
	 * 2 = traces
	 * 3 = errors
	 * 4 = warns
	 * 5 = confirms
	 */
	level: 4,
	
	send: function(msg) {
		if( this.level > 0
			&& typeof( jsTrace ) != 'undefined' ) {
			jsTrace.send( msg );
		}	
	},
	
	act: function(msg) {
		if (this.level >= 1)
			this.send('<br /><font size="medium"><strong>' + msg + '</strong></font>');
	},
	trace: function(msg) {
		if (this.level >= 2)
			this.send('<font color="lightgray">' + msg + '</font>');
	},
	error: function(msg) {
		if (this.level >= 3)
			this.send('<font color="red">' + 'Error: ' + msg + '</font>');
	},	
	warn: function(msg) {
		if (this.level >= 4)
			this.send('<font color="orange">' + 'Warning: ' + msg + '</font>');
	},
	confirm: function(msg) {
		if (this.level >= 5)
			this.send('<font color="lightgreen">' + 'OK: ' + msg + '</font>');
	},
}
Logger.act ('Initializing the AJAX Javascript debugger...');