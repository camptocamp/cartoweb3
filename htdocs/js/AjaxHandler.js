/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

/*
 * Uses: prototype.js
 * Uses: AjaxHelper.js - for common features (i.e. getHttpPostRequest)
 */

/**
 * CartoWeb's AJAX core mecanism.
 * Provides action binding and action triggering.
 * Handles AJAX requests, responses, and plugin Javascript logic calls.
 * 
 * Entry points: attachAction() and doAction().
 */
AjaxHandler = {

    /*
     * Constants
     */
    PROFILE_DEVELOPMENT: 'development',
    PROFILE_PRODUCTION: 'production',
    PROFILE_CUSTOM: 'custom',

    /**
     * URL to send AJAX request to (uses current url if empty)
     */
    baseUrl: '',

    /**
     * Id of the CartoWeb's HTML form element
     */
    cartoFormId: 'carto_form',

    /**
     * Profile: development or production
     */
    profile: this.PROFILE_PRODUCTION,

    /**
     * Wether AJAX actions are processed (true) or ignored (false)
     */
    processActions: true,

    /**
     * Number of actions pending (requests that haven't received a reply)
     */
    pendingActions: 0,



    /**
     * Sets the profile for AjaxHandler 
     * @param string Cartoweb's profile (production, development, ...)
     */
    setProfile: function(profile) {
        Logger.trace('Setting Ajaxhandler\'s profile to <b>' + profile + '</b>...');
        switch(profile) {
            case this.PROFILE_DEVELOPMENT:
            case this.PROFILE_PRODUCTION:
            case this.PROFILE_CUSTOM:
                this.profile = profile;
            break;
            default:
                this.profile = this.PROFILE_PRODUCTION;
                Logger.warn('Incorrect profile: ' + profile + ', setting default profile.');
            break;
        }
        Logger.confirm('Profile set to <b>'+this.profile+'</b>.');
    },

    /**
     * Sets the profile for AjaxHandler
     * @return string Cartoweb's profile (production, development, ...)
     */
    getProfile: function() {
        return this.profile;
    },    

    /**
     * Sets the profile for AjaxHandler
     * @param string Cartoweb's profile (production, development, ...)
     * @see AjaxHandler.setProfile()
     * @deprecated
     */
    setMode: function(profile) {
        this.setProfile(profile);
    },


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
     * @requires AjaxHelper
     */
    buildPostRequest: function(formId) {
        if (formId == undefined) {
          var formId = this.cartoFormId;
        }
        return AjaxHelper.buildQuery(formId);
    },

    /**
     * Returns the query string from an HTMLElement
     *
     * @param HTMLElement Element object to create the query from
     * @return string HTTP GET string from the given element
     * @requires AjaxHelper
     */
    buildRequestFrom: function(htmlElement) {
        return AjaxHelper.buildQueryFrom(htmlElement);
    },

    /**
     * Returns the query string from an HTML A element
     *
     * @param HTMLLinkElement Element object to create the querystring from
     * @return string HTTP GET string from the given A element
     * @requires AjaxHelper
     */
    getQueryString: function(ahrefElement) {
        AjaxHelper.getQueryString(ahrefElement);
    },
     
    /**
     * Handles the Cartoclient AJAX response, passing to each plugin its output
     * and the argObject
     * to it's Javascript plugin image
     * (i.e. AjaxPlugins.[pluginName].handleResponse()
     * @private
     */
    handlePluginReponse: function(response, argObject) {

        /*
         * Creates an array with all plugin responses, format:
         * array[pluginName][paramType][paramId] = paramValue
         * (paramType is either htmlCode or variable)
         */
        var pluginElements = response.responseXML.getElementsByTagName('plugin');
        var pluginArray = new Array(pluginElements.length);
        for (var i = 0; i < pluginElements.length; i++) {

            var currentPluginElement = pluginElements.item(i);

            var variableElements
                = currentPluginElement.getElementsByTagName('variable');
            var varArray = new Array(variableElements.length);
            for (var j = 0; j < variableElements.length; j++) {
                var currentVariableElement = variableElements.item(j);
                varArray[currentVariableElement.getAttribute('id')]
                    = AjaxHelper.decodeForSafari(
                        currentVariableElement.getAttribute('value'));
            }

            var htmlCodeElements
                = currentPluginElement.getElementsByTagName('htmlCode');
            var htmlCodeArray = new Array(htmlCodeElements.length);
            for (j = 0; j < htmlCodeElements.length; j++) {
                var currentHtmlCodeElement = htmlCodeElements.item(j);
                htmlCodeArray[currentHtmlCodeElement.getAttribute('id')]
                    = AjaxHelper.decodeForSafari(
                        currentHtmlCodeElement.getAttribute('value'));
            }
            
            var currentPluginName = currentPluginElement.getAttribute('name');
            pluginArray[i] = new Array(3);
            pluginArray[i]['pluginName'] = currentPluginName;
            pluginArray[i]['variables'] = varArray;
            pluginArray[i]['htmlCode'] = htmlCodeArray;
        }
        
        // Display list of plugins that responded in debugger
        var pluginList = '';
        for (var i = 0; i < pluginArray.length; i++) {
            pluginList += pluginArray[i]['pluginName'] + ' ';
        }
        Logger.trace('Plugins that gave response: <strong>' + pluginList + '</strong>');
        
        /*
         * Calls all plugins that gave a response (= are present in pluginArray)
         * and passes their respective param array
         */
        for (i = 0; i < pluginArray.length; i++) {
            var pluginName = pluginArray[i]['pluginName'];
            var pluginName = pluginName.charAt(0).toUpperCase()
                             + pluginName.substr(1); // ucfisrt()
            Logger.header('Updating GUI for plugin ' + pluginName);
            
            // Checks the existence of JS plugin objects
            if (!AjaxHelper.exists('AjaxPlugins.' + pluginName)) {
                Logger.warn('AjaxHandler.handlePluginReponse(): ' +
                    'object <b>AjaxPlugins.' + pluginName + '</b> not found. ' +
                    'AJAX response processing will be ignored for ' +
                    'this plugin.');
            } else if (!AjaxHelper.exists('AjaxPlugins.'+pluginName)) {
                Logger.warn('AjaxPlugins.' + pluginName + '.handlePluginReponse(): ' +
                    'method <b>AjaxPlugins.' + pluginName + '.handleResponse()</b> ' +
                    'not found. AJAX response processing will be ' +
                    'ignored for this plugin.');
            } else {
                try {
                    eval('AjaxPlugins.' + pluginName 
                         + '.handleResponse(pluginArray[i], argObject)');                  
                } catch (e) {
                    Logger.error('An error occured in Plugins ' + pluginName);
                    AjaxHelper.handleError(e);
                }
            }
        }
    },

    /**
     * Handles the AJAX request to the Cartoclient.
     * @param string Id of the triggered action (format: [PluginName].[ActionName]
     * @param Object Object containing arbitrary data for plugins' JS part's use
     * @param Object Object containing POST and/or GET queries for the Cartoclient
     * @private
     */
    actionRequest: function(actionId, argObject, queryObject) {

        Logger.trace('Initiating AJAX request');

        // Increments the number of pending actions
        this.pendingActions++;
        Logger.trace('Pending actions: ' + this.pendingActions);
        
        // Adds triggered actionId to GET parameters
        queryObject.get = 'ajaxActionRequest=' + actionId + '&' + queryObject.get;
                
        Logger.trace('GET params:<br />' + queryObject.get);
        Logger.trace('POST params:<br />' + queryObject.post);        
        
        // Builds request url
        var url = this.getBaseUrl() + '?' + queryObject.get;
        
        Logger.trace('Waiting for response...');
        var myAjax = new Ajax.Request(url,
            {method: 'post', postBody: queryObject.post,
             onComplete: function(response) {
                 Logger.trace('Response received!');
                 responseTag = response.responseText.substring(0, 5);
                 
                // Decrements the number of pending actions
                AjaxHandler.pendingActions--;                 

                 if (responseTag != '<?xml') {
                     Logger.error('AjaxHandler.actionRequest(): ' +
                                  'received response is malformed!');
                     if (AjaxHandler.profile == AjaxHandler.PROFILE_PRODUCTION) {
                         if (AjaxHelper.exists('AjaxPlugins.Common.onCartoclientError')) {
                             AjaxPlugins.Common.onCartoclientError();
                         } else {
                             Logger.warn('AjaxHandler.actionRequest(): method ' +
                                         'AjaxPlugins.Common.onCartoclientError ' +
                                         'not found. User will not be notified ' +
                                         'on errors');
                         }
                     } else {
                         var showFaillure = confirm('Ajax response is no XML, ' +
                                        'probably a CartoWeb failure.\r\n' +
                                        'Click OK to show it.');
                         if (showFaillure) {
                             ajaxErrorDivElement = document.createElement('div');
                             ajaxErrorDivElement.id = 'ajaxErrorMessage';
                             ajaxErrorDivElement.innerHTML = response.responseText;
                             ajaxErrorDivElement.onclick = function() {
                                 // Removes this error div when clicked
                                 Element.remove(this);
                             };
                             $(AjaxHandler.cartoFormId).appendChild(ajaxErrorDivElement);
                         }
                     }
                     
                 } else {                        
                     // Calls Plugins refresh logic
                     AjaxHandler.handlePluginReponse(response, argObject);

                     var requestedPluginName = actionId.substr(0, actionId.indexOf('.'));
                     var requestedActionName = actionId.substr(actionId.indexOf('.') + 1);
                     
                     // Calls onAfterAjaxCall method for the called plugin's action
                     // if the method exists
                     Logger.header('Calling ' + 
                                   requestedPluginName+ '.' +requestedActionName +
                                   ' onAfterAjaxCall()');
                     if (AjaxHelper.exists('AjaxPlugins.' + requestedPluginName +
                         '.Actions.' + requestedActionName + '.onAfterAjaxCall')) {
                          // Calls AjaxPlugins.<pluginName>.Actions.<actionName>.onAfterAjaxCall
                          try {
                              eval('AjaxPlugins.' + requestedPluginName +
                                   '.Actions.' + requestedActionName +
                                   '.onAfterAjaxCall(argObject)');
                          } catch (e) {
                              Logger.error('An error occured in Plugins ' + pluginName);
                              AjaxHelper.handleError(e);
                          }
                      }

                  }

                  // Executes the common onAfterAjaxCall in anyway
                  Logger.header('Calling AjaxPlugins.Common.onAfterAjaxCall()');
                  if (typeof AjaxPlugins.Common.onAfterAjaxCall != 'undefined') {
                      AjaxPlugins.Common.onAfterAjaxCall(actionId);
                  }

                  // Call the plugin's onAfterAjaxCallGeneral logic
                  // if the methods is defined
                  Logger.header('Calling plugin onAfterAjaxCallGeneral()');
                  for(var pluginName in AjaxPlugins) {
                    if (typeof(AjaxPlugins[pluginName]) == 'object' && 
                        pluginName.toLowerCase() != 'common' && 
                        pluginName.toLowerCase() != 'initializableplugins') {
                      Logger.note('Calling AjaxPlugins.' + pluginName + '.onAfterAjaxCallGeneral');
                      if (AjaxHelper.exists('AjaxPlugins.' + pluginName + '.onAfterAjaxCallGeneral')) {
                          try {
                              eval('AjaxPlugins.' + pluginName + '.onAfterAjaxCallGeneral(argObject)');
                          } catch (e) {
                              Logger.error('An error occured when calling onAfterAjaxCallGeneral in plugin ' + pluginName);
                              AjaxHelper.handleError(e);
                          }
                      }
                    }
                  }

                  Logger.header('--- Action ' + actionId + ' complete ---<br />');
              }
          });
    },
    
    /**
     * Triggers an action: this will start the CartoWeb's AJAX mecanism
     * (use this method when triggering action with HTML onclick="" attribute)
     * @param string Id of the triggered action (format: [PluginName].[ActionName]
     * @param Object Object containing arbitrary data for plugins' JS part's use
     */
    doAction: function(actionId, argObject) {

        Logger.header('--- Action '+ actionId +' triggered ---');
        
        if (!this.processActions) {
            Logger.warn('AJAX is disabled, action ignored...');
            return true;
            Logger.header('--- Action '+ actionId +' ignored ---');
        }

        /*
         * Creates the argObject and/or adds actionName and pluginName properties,
         * used by plugins predefined methods
         */
        argObject = argObject || {};
        var pluginName = actionId.substr(0, actionId.indexOf('.'));
        var actionName = actionId.substr(actionId.indexOf('.') + 1);
        argObject.actionName = actionName;
        argObject.pluginName = pluginName;

        // Checks the existence of the required objects and methods
        if (!AjaxHelper.exists('AjaxPlugins')) {
            Logger.error('AjaxHandler.doAction(): ' +
                'object <b>AjaxPlugins</b> not found. ' +
                'AJAX action aborted.');
        } else if (!AjaxHelper.exists('AjaxPlugins.' + pluginName)) {
            Logger.error('AjaxHandler.doAction(): ' +
                'object <b>AjaxPlugins.' + pluginName + '</b> not found. ' +
                'AJAX action aborted.');
        } else if (!AjaxHelper.exists('AjaxPlugins.' + pluginName + '.Actions')) {
            Logger.error('AjaxHandler.doAction(): ' +
                'object <b>AjaxPlugins.' + pluginName + '.Actions</b> not found. ' +
                'AJAX action aborted.');
        } else if (!AjaxHelper.exists('AjaxPlugins.'+pluginName+'.Actions.' + actionName)) {
            Logger.error('AjaxHandler.doAction(): ' +
                'object <b>AjaxPlugins.' + pluginName + '.Actions.' + actionName + '</b> ' +
                'not found. ' +
                'AJAX action aborted.');
        } else {
            // Ask the plugin that triggered the action to build GET and POST queries
            // if the methods are defined
            var httpPostQuery = '';
            var httpGetQuery = '';
            if (AjaxHelper.exists('AjaxPlugins.' + pluginName +
                '.Actions.' + actionName + '.buildPostRequest')) {
                try {
                    eval('httpPostQuery = AjaxPlugins.' + pluginName + '.Actions.' +
                         actionName + '.buildPostRequest(argObject)');
                } catch (e) {
                    Logger.error('An error occured in Plugins ' + pluginName);
                    AjaxHelper.handleError(e);
                }
            }            
            if (AjaxHelper.exists('AjaxPlugins.' + pluginName +
                '.Actions.' + actionName + '.buildGetRequest')) {
                try {
                    eval('httpGetQuery = AjaxPlugins.' + pluginName + '.Actions.' +
                         actionName + '.buildGetRequest(argObject)');
                } catch (e) {
                    Logger.error('An error occured in Plugins ' + pluginName);
                    AjaxHelper.handleError(e);
                }
            }            
    
            // Call the common and plugin's onBeforeAjaxCall logic
            // if the methods are defined
            Logger.header('Calling ' + 
                          pluginName+ '.' +actionName +
                          ' onBeforeAjaxCall()');
            if (AjaxHelper.exists('AjaxPlugins.' + pluginName +
                '.Actions.' + actionName + '.onBeforeAjaxCall')) {
                try {
                    eval('AjaxPlugins.' + pluginName + '.Actions.' + actionName +
                         '.onBeforeAjaxCall(argObject)');
                } catch (e) {
                    Logger.error('An error occured in Plugins ' + pluginName);
                    AjaxHelper.handleError(e);
                }
            }            

            // Call the plugin's onBeforeAjaxCallGeneral logic
            // if the methods is defined
            Logger.header('Calling plugin onBeforeAjaxCallGeneral()');
            for(var pluginName in AjaxPlugins) {
              if (typeof(AjaxPlugins[pluginName]) == 'object' && 
                  pluginName.toLowerCase() != 'common' && 
                  pluginName.toLowerCase() != 'initializableplugins') {
                Logger.note('Calling AjaxPlugins.' + pluginName + '.onBeforeAjaxCallGeneral');
                if (AjaxHelper.exists('AjaxPlugins.' + pluginName + '.onBeforeAjaxCallGeneral')) {
                    try {
                        eval('AjaxPlugins.' + pluginName + '.onBeforeAjaxCallGeneral(argObject)');
                    } catch (e) {
                        Logger.error('An error occured when calling onBeforeAjaxCallGeneral in plugin ' + pluginName);
                        AjaxHelper.handleError(e);
                    }
                }
              }
            }

            Logger.header('Calling AjaxPlugins.Common.onBeforeAjaxCall()');
            if (AjaxHelper.exists(AjaxPlugins.Common.onBeforeAjaxCall)) {
                AjaxPlugins.Common.onBeforeAjaxCall(actionId);
            }
    
            // Call actionRequest() to perform the AJAX call
            this.actionRequest(actionId, argObject, {post: httpPostQuery, get: httpGetQuery});
        }
    },
    
    /**
     * Attaches an action to the given element (HTML element),
     * preventing Javascript errors
     * @param string Id of the HTML element to attach the listener to
     * @param string Type of event (click, change, mouseover, ...)
     * @param string Id of the triggered action (format: [PluginName].[ActionName])
     * @param Object Object containing arbitrary data for plugins' JS part's use
     * @param bool Prevent event from bubbling through the DOM
     * @return bool True if success, false otherwise
     * @requires AjaxHelper
     */
    attachAction: function(elementId, evType, actionId, argObject, useCapture) {
        Logger.note('AjaxHandler.attachAction(): Attaching action:'+actionId+' to element id:'+elementId+'...');
        useCapture = useCapture || false;
        argObject = argObject || {};
        if (typeof(elementId) == 'string') {
            var element = $(elementId);
        } else {
            Logger.error('argument elementId must be a string'
                         + ' (argument of type \''+typeof(elementId)
                         + '\' received)');
            return false;            
        }
        if (typeof(element) == undefined || element == null) {
            Logger.warn('element ' + elementId + ' does not exist!');
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
            // Prevent default behavior (= onClick="return false;")
            if (event.returnValue) {
                event.returnValue = false;
            }
            if (event.preventDefault) {
                event.preventDefault();
            }
            Event.stop(event); // Uses prototype.js Event object
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
     * @return bool True if update succeeds, false otherwise
     */
    updateDomElement: function(elementId, property, value) {

        // These checks prevent Javascript errors        
        var element = $(elementId);
        Logger.note('AjaxHandler.updateDomElement(): Updating <b>' + elementId
                    + '</b> element\'s <b>' + property + '</b>\'s value...');

        if (typeof(elementId) != 'string') {
            Logger.error('property argument must be a string!');
            return false;            
        }
        
        if (typeof(property) != 'string') {
            Logger.error('property argument must be a string!');
            return false;
        }
        if (typeof(element) == 'undefined' || element == null) {
            Logger.warn('given element (' + elementId + ') was not found in the DOM!');
            return false;
        }

        try {
            eval('elementAttr = element.' + property);
        } catch (e) {
            Logger.error('An error occured in Plugins ' + pluginName);
            AjaxHelper.handleError(e);
        }

        if (typeof(elementAttr) == 'undefined') {
            Logger.warn('property ' + element.id + '.' + property
                        + ' was not found in the DOM!');        
            return false;
        }
        try {
            eval('element.' + property + ' = value');
        } catch (e) {
            Logger.error('An error occured in Plugins ' + pluginName);
            AjaxHelper.handleError(e);
        }
        Logger.confirm('Done.');
        return true;
    }
};
