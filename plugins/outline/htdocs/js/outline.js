function addLabel(text) {
  outlineLabelInput = xGetElementById('outlineLabelInputDiv')
  xHide(outlineLabelInput)
  
  if (!dhtmlBox.isActive && dhtmlBox.Xpoints.length > 0 && !dhtmlBox.keyEscape) {
    outlineLabelText = xGetElementById('outline_label_text')
    outlineLabelText.value = text
    xMoveTo(outlineLabelInput,dhtmlBox.Xpoints[dhtmlBox.Xpoints.length -1] + xPageX(dhtmlBox.anchor) + 5,dhtmlBox.Ypoints[dhtmlBox.Ypoints.length -1] + xPageY(dhtmlBox.anchor) + 5	)
    outlineLabelInput.style.zIndex = dhtmlBox.target.style.zIndex + 1
    xShow(outlineLabelInput)
  }
}