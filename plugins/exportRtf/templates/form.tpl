<br />
<center><b>Export RTF</b></center>
<br />
{$exportScriptPath}
<script type="text/javascript">
/*<![CDATA[*/
{literal}
function rtfFormSubmit(myForm) {
{/literal}  
    var prevAction = myForm.action;
    var prevTarget = myForm.target;
    myForm.action = '?mode=Rtf';
    myForm.target = '_blank';
    myform.submit();  
    myForm.action = prevAction;
    myForm.target = prevTarget;
{literal}
}
{/literal}
/*]]>*/
</script>

{$rtfOptionalValues}

{if $rtfTitle} <div>{t}Title:{/t} <input type="text" name="rtfTitle" value="" /></div>{/if}
{if $rtfScalebar}<div><input type="checkbox" name="rtfScalebar" id="rtfScalebar"  /> <label for="rtfScalebar">{t}Scalebar{/t}</label></div>{/if}
{if $rtfOverview}<div><input type="checkbox" name="rtfOverview" id="rtfOverview"  /> <label for="rtfOverview">{t}Overview{/t}</label></div>{/if}
{if $rtfQueryResult}<div><input type="checkbox" name="rtfQueryResult" id="rtfQueryResult" /> 
<label for="rtfQueryResult">{t}QueryResult{/t}</label></div>{/if}
<input type="button" name="rtfPrint" value="{t}Export{/t}" class="form_button" onclick="rtfFormSubmit(this.form)" />
<input type="submit" name="rtfReset" value="{t}Reset Form{/t}" class="form_button" />
