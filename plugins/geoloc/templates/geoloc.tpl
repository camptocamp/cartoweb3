{* $Id: geoloc.tpl 94 2009-05-14 16:21:32Z bruno.friedmann $ *}
<div id="geoLocBox" style="display:none">
<fieldset>
<legend>
<a href="javascript:void(0)" onclick="javascript:xGetElementById('geoLocBox').style.display='none';return false;" title="{t}Close{/t}">{t}X{/t}</a>
{t}Point's coordinates:{/t}</legend>
<form><p>{t}The asked point coordinates are{/t}:
<br />&nbsp;<br />
<input type="text" id="geoloc_values" name="geoloc_values" value="" />
<br />&nbsp;
</p>
</form>
</fieldset>
</div>
