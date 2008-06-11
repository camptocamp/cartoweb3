AjaxPlugins.Common.oldOnBeforeAjaxCall = AjaxPlugins.Common.onBeforeAjaxCall;
AjaxPlugins.Common.onBeforeAjaxCall = function(actionId) {
    xGetElementById('linkItBox').style.display = 'none';
    this.oldOnBeforeAjaxCall();
}
