/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */

function FormItemSelected() {
  document.carto_form.submit();
}
  
function CheckRadio(theIndex) {
  document.carto_form.tool[theIndex].checked = true;
}
