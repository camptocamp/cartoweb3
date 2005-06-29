<script type="text/javascript">
<!--
var showLocation = {if $viewShow}false{else}true{/if};
var deleteMsg = '{t}Are you sure you want to delete view #{/t}';
var qMark = '{t}?{/t}';
//-->
</script>

{if $viewMsg}<div id="viewMsg" style="font-weight:bold;color:red;">{$viewMsg}</div>{/if}

<fieldset>
<legend>{t}Go to view{/t}</legend>
{if $viewOptions}
<div>{t}Title{/t} 
<select name="viewLoadTitleId" onchange="javascript:resetViewLoadId();">
{html_options options=$viewOptions selected=$viewId}
</select> {t}or{/t} {t}Id{/t} <input type="text" name="viewLoadId" 
id="viewLoadId" value="{strip}{if $viewId}{$viewId}{/if}{/strip}" size="4" onfocus="javascript:resetViewLoadTitleId();" /></div>
<input type="submit" name="viewLoad" value="{t}Load view{/t}"
onclick="javascript:setHandleView();" />
{else}
{t}No view available{/t}
{/if}
</fieldset>

<fieldset>
<legend>{t}Edit view{/t}</legend>
<div>{t}View #{/t} {if $viewId}{$viewId}{else}- ({t}new view{/t}){/if}</div>
{if $viewId}
<div><label for="viewActive">{t}Memorize form{/t} </label><input 
type="checkbox" name="viewActive" id="viewActive" {if $viewActive}checked="checked"{/if} /></div>
{/if}
<div>{t}Title{/t} <input type="text" name="viewTitle" id="viewTitle" value="{$viewTitle}" /></div>
<div>{t}Author{/t} <input type="text" name="author" id="author" value="{$author}" /></div>
<div><label for="viewShow" onfocus="javascript:showLocationSelector();">{t}Show view{/t} </label><input type="checkbox" name="viewShow" id="viewShow" 
onclick="javascript:showLocationSelector();" {if $viewShow}checked="checked"{/if} /></div>
{if $viewLocationOptions}
<div id="locationSelector" {if !$viewShow}style="display:none;"{/if}>{t}Place view label before{/t}
<input type="hidden" name="viewLocationUpdate" value="0" />
<select name="viewLocationId" 
onchange="javascript:document.carto_form.viewLocationUpdate.value=1;">
{html_options options=$viewLocationOptions selected=$viewLocationId}</div>
</select>
</div>
{/if}
<input type="hidden" name="handleView" value="0" />
{if $viewId}
<input type="hidden" name="viewUpdateId" value="{$viewId}" />
<input type="submit" name="viewUpdate" value="{t}Update view{/t}"
onclick="javascript:setHandleView();" />
{/if}
<input type="submit" name="viewSave" 
value="{strip}{if $viewId}{t}Save as new view{/t}{else}{t}Save view{/t}{/if}{/strip}" onclick="javascript:setHandleView();" />
</fieldset>

{if $viewId}
<fieldset>
<legend>{t}Delete view{/t}</legend>
<input type="hidden" name="viewDeleteId" value="{$viewId}" />
<input type="hidden" name="viewDelete" value="0" />
<input type="button" name="viewDeleteButton" value="{t}Delete view #{/t} {$viewId}" onclick="javascript:checkBeforeDelete({$viewId});" />
</fieldset>
{/if}
