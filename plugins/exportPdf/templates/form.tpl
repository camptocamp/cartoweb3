<br />
<form method="post" action="{$exportScriptPath}">

<div>{t}Format:{/t} 
{html_options name="pdfFormat" options=$pdfFormat_options selected=$pdfFormat_selected}</div>

<div>{t}Resolution:{/t}
{html_options name="pdfResolution" options=$pdfResolution_options 
selected=$pdfResolution_selected}</div>

<div><input type="radio" name="pdfOrientation" id="ptt" value="portrait" 
{if $pdfOrientation == 'portrait'}checked="checked"{/if} /><label 
for="ptt">{t}Portrait{/t}</label>
<input type="radio" name="pdfOrientation" id="lsp" value="landscape"
{if $pdfOrientation == 'landscape'}checked="checked"{/if} /><label
for="lsp">{t}Landscape{/t}</label></div>

{if $pdfTitle}<div>{t}Title:{/t} <input type="text" name="pdfTitle" value="" /></div>{/if}
{if $pdfNote}<div>{t}Note:{/t} <input type="text" name="pdfNote" value="" /></div>{/if}
{if $pdfScalebar}<div><input type="checkbox" name="pdfScalebar" id="pdfScalebar" />
<label for="pdfScalebar">{t}Scalebar{/t}</label></div>{/if}
{if $pdfOverview}<div><input type="checkbox" name="pdfOverview" id="pdfOverview" />
<label for="pdfOverview">{t}Overview{/t}</label></div>{/if}
{if $pdfQueryResult}<div><input type="checkbox" name="pdfQueryResult" id="pdfQueryResult" />
<label for="pdfQueryResult">{t}QueryResult{/t}</label></div>{/if}

<input type="submit" name="pdfPrint" value="{t}Print{/t}" class="form_button" />
</form>
