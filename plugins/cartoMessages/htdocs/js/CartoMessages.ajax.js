/*
 * This is a pseudo plugin, used to retrieve Cartoweb general information
 * i.e. user/developer message, config parameters, ...
 */

AjaxPlugins.CartoMessages = {

    notifyUserMsgs: true,
    notifyDeveloperMsgs: false,
    
    handleResponse: function(pluginOutput) {

        // check profile
        if (AjaxHandler.getProfile() == 'development') {
            this.notifyDeveloperMsgs = true;
        }

        // Shows developer and user messages in jsTrace debugger window
        if (pluginOutput.htmlCode.developerMessages != '') {
            var developerMsgs = eval(pluginOutput.variables.developerMessages);
            Logger.trace('Developer messages: ' + developerMsgs.length);
            if (developerMsgs.length > 0) Logger.note(this.formatHtml(developerMsgs));
            if (this.notifyDeveloperMsgs) this.notifyDeveloper(developerMsgs);
        }
        if (pluginOutput.htmlCode.userMessages != '') {
            var userMsgs = eval(pluginOutput.variables.userMessages);
            Logger.trace('User messages: ' + userMsgs.length);
            if (userMsgs.length > 0) Logger.note(this.formatHtml(userMsgs));
            if (this.notifyUserMsgs) this.notifyUser(userMsgs);
        }
    },
    
    notifyUser: function(messageArray, header) {
        if (messageArray.length < 1) return;
        var header = header || 'Message(s): \r\n \r\n';
        alert(header + this.formatAlertBox(messageArray));        
    },

    notifyDeveloper: function(messageArray, header) {
        if (messageArray.length < 1) return;
        this.outputMessages(messageArray);
    },
    
    formatHtml: function(messageArray) {
        var formattedString = '';
        messageArray.each(function(msg) {
            formattedString += '<span style="color: yellow">';
            formattedString += '&nbsp;&nbsp;&nbsp;*&nbsp;' + msg;
            formattedString += '</span><br />';
        });
        return formattedString;
    },

    formatAlertBox: function(messageArray) {
        var formattedString = '';
        messageArray.each(function(msg) {
            msg = msg.replace(/&#039;/g, "'");
            formattedString += '\t * ' + msg + '\r\n';
        });
        return formattedString;
    },

    outputMessages: function(messageArray) {
        outputStringTarget = xGetElementById('developperMsgs');
        if (outputStringTarget) {
            var outputString = "Developer Messages: <br />";
            messageArray.each(function(msg) {
                msg = msg.replace(/\,/g, ",\n");
                outputString += '<pre>' + msg + '\n##(notice: line return added '
                             + 'after , for better display)##</pre>';
            });
            outputStringTarget.innerHTML = outputString;
        }
    }
};