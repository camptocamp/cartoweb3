<input type="hidden" name="pdfMarginX" value="{$pdfMarginX}" />
<input type="hidden" name="pdfMarginY" value="{$pdfMarginY}" />
{foreach from=$pdfFormatDimensions item=formatDimension}
<input type="hidden" name="pdf{$formatDimension->format}x"                     
                     id="pdf{$formatDimension->format}x" value="{$formatDimension->xsize}" />
<input type="hidden" name="pdf{$formatDimension->format}y" 
                     id="pdf{$formatDimension->format}y" value="{$formatDimension->ysize}" />
{/foreach}

<div id="pdf_format"><fieldset><legend>{t}Format and Resolution (dpi){/t}</legend>
<select name="pdfFormat"
onchange="javascript:updateResolutions({$pdfResolution_selected}); mainmap.updatePdfFeature('map');">
{html_options options=$pdfFormat_options selected=$pdfFormat_selected}
</select>
{html_options name="pdfResolution" options=$pdfResolution_options 
selected=$pdfResolution_selected}</fieldset></div>

<div id="pdf_scale"><fieldset><legend>{t}Scale:{/t}</legend>
<select name="pdfScale" 
onchange="javascript: mainmap.updatePdfFeature('map');">
{html_options options=$pdfScale_options selected=$pdfScale_selected}
</select> 
<input type="button" name="pdfRecenter" value="{t}PDF recenter{/t}" onclick="javascript: mainmap.pdfRecenter();" class="form_button form_button_inline" />
</fieldset>
</div>

<div id="pdf_orientation"><fieldset><legend>{t}Orientation{/t}</legend>
<input type="radio" name="pdfOrientation" id="ptt" value="portrait" 
{if $pdfOrientation == 'portrait'}checked="checked"{/if} 
onclick="javascript: mainmap.updatePdfFeature('map');" /><label 
for="ptt">{t}Portrait{/t}</label>
<input type="radio" name="pdfOrientation" id="lsp" value="landscape"
{if $pdfOrientation == 'landscape'}checked="checked"{/if} 
onclick="javascript: mainmap.updatePdfFeature('map');" /><label
for="lsp">{t}Landscape{/t}</label>


<input type="hidden" name="pdfMapAngle" value="{$pdfMapAngle}" />
<input type="hidden" name="pdfMapCenterX" value="{$pdfMapCenterX}" />
<input type="hidden" name="pdfMapCenterY" value="{$pdfMapCenterY}" />
<br />
{t}Rotation{/t}: <span id="pdfrotate_angledegree">0 </span>&deg;
<br />
<input type="button" name="pdfrotateminus" value="{t}-5°{/t}" onclick="javascript: mainmap.rotatePdfFeature(-5);" class="form_button form_button_inline" />
<input type="button" name="pdfrotateplus" value="{t}+5°{/t}" onclick="javascript: mainmap.rotatePdfFeature(5);" class="form_button form_button_inline" />
 
{t}Free rotate{/t}: <input type="text" id="pdfrotatefreevalue" name="pdfrotatefreevalue" size="3" />
<input type="button" name="pdfrotateset" value="{t}set{/t}" onclick="javascript: mainmap.rotatePdfFeature(xGetElementById('pdfrotatefreevalue').value, true);" class="form_button form_button_inline" />
</fieldset></div>