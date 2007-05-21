/**
 * submit a custom scale value using the existing scale system
 * @param event
 */
function setCustomScale(event){
    if (typeof(Prototype) == 'undefined'){
      alert('Warning, the javascript prototype library is not loaded! Freescale wont work.');
    } else {
        if (event.keyCode == Event.KEY_RETURN) {
            var scaleSelect = xGetElementById('recenter_scale');
            var customScale = xGetElementById('custom_scale').value;
            if(isNaN(parseInt(customScale))){
                alert('invalid scale');
            } else {
                customScale = parseInt(customScale);
                if(customScale > 1){
                    scaleSelect[scaleSelect.selectedIndex].value = customScale;
                    xGetElementById('recenter_doit').value=1;
                    CartoWeb.trigger('Location.Zoom', 'FormItemSelected()');
                } else {
                    alert('invalid scale');
                }
            }
            Event.stop(event);
        }
    }
}