<script type="text/javascript">
/*<![CDATA[*/
var resolutions = new Array();
{foreach from=$pdfAllowedResolutions key=formatId item=formatResolutions}
resolutions['{$formatId}'] = new Array({foreach
from=$formatResolutions name=formatRes key=resId
item=resLabel}{$resId},'{$resLabel}'{if !$smarty.foreach.formatRes.last},{/if}{/foreach});
{/foreach}
{literal}
function pdfFormSubmit(myForm) {
{/literal}
  var prevAction = myForm.action;
  var prevTarget = myForm.target;
  myForm.action = '{$exportScriptPath}';
  myForm.target = '_blank';
  myform.submit();
  myForm.action = prevAction;
  myForm.target = prevTarget;
{literal}
}
{/literal}
/*]]>*/
</script>

<div id="pdf">
{if $isModeRotate}
{include file=formRotate.tpl}
{else}
{include file=formClassic.tpl}
{/if}

{if $pdfTitle}<div id="pdf_title"><fieldset><legend>{t}Title{/t}</legend><input type="text" class="input_text" name="pdfTitle" value="{$pdfTitle_value}" /></fieldset></div>{/if}

{if $pdfNote}<div id="pdf_note"><fieldset><legend>{t}Note{/t}</legend><input type="text" class="input_text" name="pdfNote" value="{$pdfNote_value}" /></fieldset></div>{/if}

{if $pdfScalebar || $pdfOverview || $pdfQueryResult}
<div id="pdf_option">
<fieldset><legend>{t}Options{/t}</legend>
{if $pdfScalebar}<div><input type="checkbox" name="pdfScalebar" id="pdfScalebar" {if $pdfScalebar_value}checked="checked"{/if}/>
<label for="pdfScalebar">{t}Scalebar{/t}</label></div>{/if}
{if $pdfOverview}<div><input type="checkbox" name="pdfOverview" id="pdfOverview" {if $pdfOverview_value}checked="checked"{/if} />
<label for="pdfOverview">{t}Overview{/t}</label></div>{/if}
{if $pdfQueryResult}<div><input type="checkbox" name="pdfQueryResult" id="pdfQueryResult" {if $pdfQueryResult_value}checked="checked"{/if} />
<label for="pdfQueryResult">{t}QueryResult{/t}</label></div>{/if}
</fieldset>
</div>
{/if}

{if $pdfLegend}
<div id="pdf_legend">
<fieldset>
<legend>{t}Legend{/t}</legend>
<div><input type="radio" name="pdfLegend" value="in" id="legendIn" {if $pdfLegend_value == 'in'}checked="checked"{/if} /><label
for="legendIn">{t}On map{/t}</label></div>
<div><input type="radio" name="pdfLegend" value="out" id="legendOut" {if $pdfLegend_value == 'out'}checked="checked"{/if} /><label
for="legendOut">{t}In new page{/t}</label></div>
<div><input type="radio" name="pdfLegend" value="0" id="legendNone" {if !$pdfLegend_value}checked="checked"{/if} /><label for="legendNone">{t}None{/t}</label></div>
</fieldset>
</div>
{/if}

<center>
<input type="button" name="pdfPrint" value="{t}Print{/t}" class="form_button" onclick="pdfFormSubmit(this.form)" />
{if $isModeRotate}
<input type="button" name="pdfReset" value="{t}Reset Form{/t}" class="form_button" onclick="mainmap.resetPdfFeature('map');"/>
{else}
<input type="submit" name="pdfReset" value="{t}Reset Form{/t}" class="form_button" />
{/if}
</center>
</div>
