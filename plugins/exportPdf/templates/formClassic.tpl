<div>{t}Format:{/t} 
<select name="pdfFormat"
onchange="javascript:updateResolutions({$pdfResolution_selected});">
{html_options options=$pdfFormat_options selected=$pdfFormat_selected}
</select></div>

<div>{t}Resolution:{/t}
{html_options name="pdfResolution" options=$pdfResolution_options 
selected=$pdfResolution_selected}</div>

<div><input type="radio" name="pdfOrientation" id="ptt" value="portrait" 
{if $pdfOrientation == 'portrait'}checked="checked"{/if} /><label 
for="ptt">{t}Portrait{/t}</label>
<input type="radio" name="pdfOrientation" id="lsp" value="landscape"
{if $pdfOrientation == 'landscape'}checked="checked"{/if} /><label
for="lsp">{t}Landscape{/t}</label></div>
