
function detectBrowserInfo() {
    var hasJava = false;

    if (navigator.javaEnabled() ||
        (navigator.mimeTypes && navigator.mimeTypes.length && 
         typeof(navigator.mimeTypes['application/x-java-vm']) != 'undefined'))
            hasJava = true;

    // Update this if the parameters change
    var BROWSER_INFO_VERSION = 0;
    document.forms['carto_form'].js_accounting.value = 
          "version=" + BROWSER_INFO_VERSION
        + ";hasJava=" + hasJava
        + ";winInnerWidth=" + window.innerWidth
        + ";winInnerHeight=" + window.innerHeight
        + ";screenWidth=" + window.screen.width
        + ";screenHeight=" + window.screen.height
        + ";screenColorDepth=" + window.screen.colorDepth
        ;
}

EventManager.Add(window, 'load', detectBrowserInfo, false);
