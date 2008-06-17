AjaxPlugins.Common.oldOnBeforeAjaxCall = AjaxPlugins.Common.onBeforeAjaxCall;
AjaxPlugins.Common.onBeforeAjaxCall = function(actionId) {
    xGetElementById('linkItBox').style.display = 'none';
    if ($('tool').value == 'linkit'){
          linkItClose();
    }
    this.oldOnBeforeAjaxCall();
}

function linkItClose() {
  if (onLinkitClose && onLinkitClose != ''){
    if (typeof(mainmap) != 'undefined') {
      eval("mainmap."+onLinkitClose+"('map')");
    }
    setActiveToolButton(onLinkitClose);
  }
  xGetElementById('linkItBox').style.display='none';
}