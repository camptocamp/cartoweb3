<script type="text/javascript">
<!--
var resolutions = new Array();
{foreach from=$pdfAllowedResolutions key=formatId item=formatResolutions}
resolutions['{$formatId}'] = new Array({foreach 
from=$formatResolutions name=formatRes key=resId
item=resLabel}{$resId},'{$resLabel}'{if !$smarty.foreach.formatRes.last},{/if}{/foreach});
{/foreach}
{literal}
function pdfFormSubmit(myForm) {
{/literal}
  var prevAction = myForm.action
  var prevTarget = myForm.target
  myForm.action = '{$exportScriptPath}'
  myForm.target = '_blank'
  myform.submit()
  myForm.action = prevAction
  myForm.target = prevTarget
{literal}
}
{/literal}
//-->
</script>
<div id="pdf">
<input type="hidden" name="pdfExport" value="1" />
<div id="pdf_format"><fieldset><legend>{t}Format and Resolution (dpi){/t}</legend>
<select name="pdfFormat"
onchange="javascript:updateResolutions({$pdfResolution_selected});">
{html_options options=$pdfFormat_options selected=$pdfFormat_selected}
</select>
&nbsp;&nbsp;&nbsp;
{html_options name="pdfResolution" options=$pdfResolution_options 
selected=$pdfResolution_selected}</fieldset></div>

<div id="pdf_orientation"><fieldset><legend>{t}Orientation{/t}</legend>
<input type="radio" name="pdfOrientation" id="ptt" value="portrait" 
{if $pdfOrientation == 'portrait'}checked="checked"{/if} /><label 
for="ptt">{t}Portrait{/t}</label>
&nbsp;&nbsp;&nbsp;<input type="radio" name="pdfOrientation" id="lsp" value="landscape"
{if $pdfOrientation == 'landscape'}checked="checked"{/if} /><label 
for="lsp">{t}Landscape{/t}</label>
</fieldset></div>

{if $pdfTitle}<div id="pdf_title"><fieldset><legend>{t}Title{/t}</legend><input type="text" name="pdfTitle" value="" /></fieldset></div>{/if}

{if $pdfNote}<div id="pdf_note"><fieldset><legend>{t}Note{/t}</legend><input type="text" name="pdfNote" value="" /></fieldset></div>{/if}

{if $pdfScalebar || $pdfOverview || $pdfQueryResult}
<div id="pdf_option">
<fieldset><legend>{t}Options{/t}</legend>
{if $pdfScalebar}<div><input type="checkbox" name="pdfScalebar" id="pdfScalebar" checked="checked" />
<label for="pdfScalebar">{t}Scalebar{/t}</label></div>{/if}
{if $pdfOverview}<div><input type="checkbox" name="pdfOverview" id="pdfOverview" />
<label for="pdfOverview">{t}Overview{/t}</label></div>{/if}
{if $pdfQueryResult}<div><input type="checkbox" name="pdfQueryResult" id="pdfQueryResult" />
<label for="pdfQueryResult">{t}QueryResult{/t}</label></div>{/if}
</fieldset>
</div>
{/if}

{if $pdfLegend}
<div id="pdf_legend">
<fieldset>
<legend>{t}Legend{/t}</legend>
<div><input type="radio" name="pdfLegend" value="in" id="legendIn" /><label
for="legendIn">{t}On map{/t}</label></div>
<div><input type="radio" name="pdfLegend" value="out" id="legendOut" /><label
for="legendOut">{t}In new page{/t}</label></div>
<div><input type="radio" name="pdfLegend" value="0" id="legendNone" 
checked="checked" /><label for="legendNone">{t}None{/t}</label></div>
</fieldset>
</div>
{/if}

<div class="mini">
  <input type="submit" name="pdfPrint" value="{t}Print{/t}" class="form_button" onclick="pdfFormSubmit(this.form)"/>
</div>
</div>
