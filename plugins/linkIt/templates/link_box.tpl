<div id="linkItBox" style="display:none">
{t}The current page is available using following URL:{/t}<br />
<form><input type="text" id="linkItUrl" name="linkItUrl" value="{$linkItUrl}" /></form>
<div id="linkItUrlAlert" class="alert" {if !$isUrlTooLong}style="display:none"{/if}>{t}URL is too long{/t}</div>
<a href="javascript:void(0)" onclick="javascript:xGetElementById('linkItBox').style.display='none';return false;">{t}Close{/t}</a>
</div>
