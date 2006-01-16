<br />
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

{if $isModeRotate}
{include file=formRotate.tpl}
{else}
{include file=formClassic.tpl}
{/if}

{if $pdfTitle}<div>{t}Title:{/t} <input type="text" name="pdfTitle" value="{$pdfTitle_value}" /></div>{/if} {if $pdfNote}<div>{t}Note:{/t} <input type="text" name="pdfNote" value="{$pdfNote_value}" /></div>{/if}
{if $pdfScalebar}<div><input type="checkbox" name="pdfScalebar" id="pdfScalebar" {if $pdfScalebar_value}checked="checked"{/if} /> <label for="pdfScalebar">{t}Scalebar{/t}</label></div>{/if}
{if $pdfOverview}<div><input type="checkbox" name="pdfOverview" id="pdfOverview" {if $pdfOverview_value}checked="checked"{/if} /> <label for="pdfOverview">{t}Overview{/t}</label></div>{/if}
{if $pdfQueryResult}<div><input type="checkbox" name="pdfQueryResult" id="pdfQueryResult" {if $pdfQueryResult_value}checked="checked"{/if} /> <label for="pdfQueryResult">{t}QueryResult{/t}</label></div>{/if}

{if $pdfLegend}
<fieldset>
<legend>{t}Legend{/t}</legend>
<div><input type="radio" name="pdfLegend" value="in" id="legendIn" {if $pdfLegend_value == 'in'}checked="checked"{/if} /><label
for="legendIn">{t}On map{/t}</label></div>
<div><input type="radio" name="pdfLegend" value="out" id="legendOut" {if $pdfLegend_value == 'out'}checked="checked"{/if} /><label
for="legendOut">{t}In new page{/t}</label></div>
<div><input type="radio" name="pdfLegend" value="0" id="legendNone" {if !$pdfLegend_value}checked="checked"{/if} /><label
for="legendNone">{t}None{/t}</label></div>
</fieldset>
{/if}

<input type="button" name="pdfPrint" value="{t}Print{/t}" class="form_button" onclick="pdfFormSubmit(this.form)" />
<input type="submit" name="pdfReset" value="{t}Reset Form{/t}" class="form_button" />
