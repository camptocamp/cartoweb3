function showLocationSelector() {
  var locsel = xGetElementById('locationSelector');
  if (!locsel) {
    return;
  }
  
  if (showLocation) {
    locsel.style.display = 'block';
    showLocation = false;
  } else {
    locsel.style.display = 'none';
    showLocation = true;
  }
}

function setHandleView() {
  document.carto_form.handleView.value = 1;
}

function checkBeforeDelete(viewId) {
  if (window.confirm(deleteMsg + viewId + qMark)) {
    setHandleView();
    document.carto_form.viewDelete.value = 1;
    document.carto_form.submit();
  }
}

function resetViewLoadId() {
  document.carto_form.viewLoadId.value = '';
}

function resetViewLoadTitleId() {
  document.carto_form.viewLoadTitleId.options[0].selected = true;
}

function loadView() {
  if (document.carto_form.viewBrowseId.value == 0) {
    return;
  }
 
  if (document.carto_form.viewLoadId) {
    resetViewLoadId();
  }
  
  setHandleView();
  document.carto_form.viewBrowse.value = 1;
  document.carto_form.submit();
}
