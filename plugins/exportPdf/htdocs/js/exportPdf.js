function updateResolutions(defaultRes) {
  var format = document.pdfForm.pdfFormat.value;
  if (!resolutions[format])
    return;
  
  var res = resolutions[format];
  var pdfRes = document.pdfForm.pdfResolution;
  var selRes = document.pdfForm.pdfResolution.value;
  
  // remove existing options
  pdfRes.options.length = 0;
  
  var selected;
  for (var i = 0; i < res.length; ) {
    defSelected = (res[i] == defaultRes);
    selected = (res[i] == selRes);
    pdfRes.options[i/2] = new Option(res[i+1], res[i], defSelected, selected);
    i += 2;
  }
}
