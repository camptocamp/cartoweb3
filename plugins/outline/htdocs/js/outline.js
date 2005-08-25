/**
 * Displays a text input form to add a label to the drawn object
 * @param text default text in the field
 * @param x x position of the input
 * @param y y position of the input
 */
function addLabel(text,x,y) {
  outlineLabelInput = xGetElementById('outlineLabelInputDiv');
  outlineLabelText = xGetElementById('outline_label_text');
  outlineLabelText.value = text;
  xMoveTo(outlineLabelInput,x + 5,y + 5);
  outlineLabelInput.style.zIndex = 3;
  xShow(outlineLabelInput);
}

/**
 * Hides the input form to add a label
 */
function hideLabel() {
  outlineLabelInput = xGetElementById('outlineLabelInputDiv');
  xHide(outlineLabelInput);
}