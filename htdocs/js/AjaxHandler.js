/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/*
 * Uses: Prototype-1.3.1.js - for extension mecanism and Ajax.Request class
 * Uses: AjaxHelper.js - for common features (i.e. getHttpPostRequest)
 */

AjaxHandler = {
	baseUrl: '', // uses current url if empty

	cartoFormId: 'carto_form',


	initialize: function(baseUrl, cartoFormId) {
		if (baseUrl == undefined)
			baseUrl = '';
		if (cartoFormId == undefined) {
			cartoFormId = '';
		}

		this.baseUrl = baseUrl;
		this.cartoFormId = cartoFormId;
		this.plugins = new Array();
	},


	setBaseUrl: function(baseUrl) {
		this.baseUrl = baseUrl;
	},


	setCartoFormId: function(cartoFormId) {
		this.cartoFormId = cartoFormId;
	},


	buildPostRequest: function(formId) {
		if (formId == undefined)
			formId = this.cartoFormId;
		return AjaxHelper.buildHttpPostRequest(formId);
	},
	buildRequestFrom: function(htmlElement) {
		return AjaxHelper.buildHttpRequestFrom(htmlElement);
	},
    getQueryStringFrom: function(ahrefElement) {
	    AjaxHelper.getQueryStringFrom(ahrefElement);
    },

	/**
	 * Returns cartoweb's base url (format: http://hostname/dir1/dir12/dir121/script_name.ext)
	 * @return Cartoweb's base url
	 */
	getBaseUrl: function() {
		return this.baseUrl;
	},

	 
	handlePluginReponse: function(response) {
		
		/*
		 * Creates an array with all plugin responses: array[pluginName][paramType][paramName][paramValue]
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
		 * Call all plugins that gave a response (= are present in pluginArray)
		 * and passes their respective param array
		 */
		for (i=0; i<pluginArray.length; i++) {
			pluginName = pluginArray[i]['pluginName'];
			pluginName = pluginName.charAt(0).toUpperCase() + pluginName.substr(1); // ucfisrt()
			eval('AjaxPlugins.' + pluginName + '.handleResponse(pluginArray[i])');
		}
	},


	actionRequest: function(actionId, argObject, httpRequestObject) {
		var url = this.getBaseUrl()
			+ '?ajaxActionRequest=' + actionId + '&'
			+ httpRequestObject.get;
		var myAjax = new Ajax.Request (
			url,
			{method: 'post', postBody: httpRequestObject.post,
				onComplete: function(response) {
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
						AjaxHandler.handlePluginReponse(response);
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
	
	  	
	doAction: function(actionId, argObject) {
		if (argObject == undefined)
			argObject = {};
		pluginName = actionId.substr(0, actionId.indexOf('.'));
		actionName = actionId.substr(actionId.indexOf('.')+1);
		eval('httpPostRequest = AjaxPlugins.' + pluginName + '.Actions.' + actionName + '.buildPostRequest(argObject)');
		eval('httpGetRequest = AjaxPlugins.' + pluginName + '.Actions.' + actionName + '.buildGetRequest(argObject)');
		eval('AjaxPlugins.' + pluginName + '.Actions.' + actionName + '.onBeforeAjaxCall(argObject)');
		AjaxPlugins.Common.onBeforeAjaxCall(actionId);
		this.actionRequest(actionId, argObject, {post: httpPostRequest, get: httpGetRequest});
	},
	
	
	attachAction: function(element, evType, actionName, argObject, useCapture) {
		if (useCapture == undefined)
			useCapture = false;		
		if (argObject == undefined)
			argObject = {};
		// Attaches the listener to the evType event on the element
		AjaxHelper.addEvent(element, evType, function(event) {
		    // Fixes event and target issues
			var event = window.event ? window.event : event;
			var target = event.target ? event.target : event.srcElement;
			// Defines predefined argObject properties: event & target
			argObject.event = event;
			argObject.target = target;
			AjaxHandler.doAction(actionName, argObject);
			// Prevent default comportment (= onClick="return false;")
			if (event.returnValue) 
				event.returnValue = false;
			if (event.preventDefault)
				event.preventDefault();
		}, useCapture);
	},

	// Bogous
	waitFor: function(elementId, functionToExecute, timeout, waitTimeout,  timesChecked) { // timeout in seconds
		if (elementId == undefined)
			elementId = '';
		if (functionToExecute == undefined)
			functionToExecute = function() {};
		if (timeout == undefined)
			timeout = 5; // timeout in seconds
		if (waitTimeout == undefined)
			waitTimeout = 5000; // waitTimeout in ms
		if (timesChecked == undefined) {
			timesChecked = 0;
		} else {
			timesChecked += 1;
		}

		if (timesChecked > 1)
			alert('waitTimout('+timesChecked+'): ' + waitTimeout * timesChecked / 1000 + ' < ' + timeout);
		
		// Initialise only if elementId element exists, else
		// wait a little while before trying again
		if ($(elementId) == undefined) {
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



AjaxHandler.Debug = {

	log: new Array(),
	
	add: function(message) {
		this.log.push('[time]: ' + 'debug msg');
	},
	
	toString: function() {
		str = '';
		for (i=0; i<this.log.length; i++) {
			str += this.log[i] + "\r\n";
		}
		return str;
	}
}