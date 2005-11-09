<script type="text/javascript">
<!--
var showLocation = {if $viewShow}false{else}true{/if};
var deleteMsg = '{t}Are you sure you want to delete view #{/t}';
var qMark = '{t}?{/t}';
//-->
</script>
<div id="viewMsg" style="position:relative;left:10px;"><h1>{t}Go to view{/t}</h1></div>
<div style="position:relative;left:20px;">
  {if $viewOptions}
  <select name="viewLoadTitleId">
    {html_options options=$viewOptions selected=$viewId}
  </select>
  <input type="submit" name="viewLoad" value="{t}Load view{/t}"
  onclick="javascript:setHandleView();" class="form_button" style="vertical-align:middle;margin-left:10px;" />
  {else}
    {t}No view available{/t}
  {/if}
</div>

<br /><br />
<div id="viewMsg" style="position:relative;left:10px;">{if $viewMsg}<h1>{$viewMsg}</h1>
{else}<h1>{t}New view{/t}</h1>{/if}</div>
<div style="position:relative;left:20px;">
  {if $viewId}
  <div><label for="viewActive">{t}Memorize form{/t}</label>
  <input type="checkbox" name="viewActive" id="viewActive" {if $viewActive}checked="checked"{/if} /></div>
  {/if}
  <div>{t}Title{/t} <input type="text" name="viewTitle" id="viewTitle" class="input_text" value="{$viewTitle}" /></div>
  <div>{t}Author{/t} <input type="text" name="author" id="author" class="input_text" value="{$author}" /></div>
  <div><label for="viewShow" onfocus="javascript:showLocationSelector();">{t}Show view{/t} </label>
    <input type="checkbox" name="viewShow" id="viewShow" onclick="javascript:showLocationSelector();" 
    {if $viewShow}checked="checked"{/if} />
  </div>
  {if $viewLocationOptions}
  <div id="locationSelector" {if !$viewShow}style="display:none;"{/if}>{t}Place view label before{/t}
    <input type="hidden" name="viewLocationUpdate" value="0" />
    <select name="viewLocationId" 
    onchange="javascript:document.carto_form.viewLocationUpdate.value=1;">
      {html_options options=$viewLocationOptions selected=$viewLocationId}
    </select>
  </div>
  {/if}
</div>
<input type="hidden" name="handleView" value="0" />
{if $viewId}
<input type="hidden" name="viewUpdateId" value="{$viewId}" />
<input type="submit" name="viewUpdate" class="form_button" value="{t}Update view{/t}"
onclick="javascript:setHandleView();" />
{/if}
<input type="submit" name="viewSave" class="form_button" 
value="{strip}{if $viewId}{t}Save as new view{/t}{else}{t}Save view{/t}{/if}{/strip}" onclick="javascript:setHandleView();" />

{if $viewId}
<br /><br />
<input type="hidden" name="viewDeleteId" value="{$viewId}" />
<input type="hidden" name="viewDelete" value="0" />
<input type="button" name="viewDeleteButton" value="{t}Delete view #{/t} {$viewId}" onclick="javascript:checkBeforeDelete({$viewId});" class="form_button" />
{/if}
<br /><br />