<br />
<script type="text/javascript">
/*<![CDATA[*/
{literal}
function domPdfFormSubmit(myForm) {
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

<div>{t}Title:{/t} <input type="text" name="pdfTitle" value="" /></div>

<div>{t}Format:{/t} 
{html_options name="pdfSize" values=$sizes output=$sizes}</div>

<div>{t}Resolution:{/t}
{html_options name="pdfResolution" values=$resolutions output=$resolutions}</div>

<div>{t}Orientation:{/t}
{html_options name="pdfOrientation" values=$orientations output=$orientations}</div>

<input type="button" name="domPdfPrint" value="{t}Print{/t}" class="form_button" onclick="domPdfFormSubmit(this.form);" />
