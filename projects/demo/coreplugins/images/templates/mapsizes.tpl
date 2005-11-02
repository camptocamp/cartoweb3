<p>{t}Mapsize:{/t} <select name="mapsize" id="mapsize" onchange="
	//AjaxHandler.doAction('Images.changeMapSize');
	javascript:FormItemSelected();
">
{html_options options=$mapsizes_options selected=$mapsize_selected}
</select></p>
