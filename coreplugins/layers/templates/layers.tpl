<input type="hidden" id="openNodes" name="openNodes" />
<script type="text/javascript">
  <!--
  var openNodes = new Array('{$startOpenNodes}');
  writeOpenNodes(true);
  //-->
</script>
<div id="layerscmd"><a href="javascript:expandAll('layersroot');">{t}expand tree{/t}</a> -
<a href="javascript:closeAll('layersroot');">{t}close tree{/t}</a><br />
<a href="javascript:checkChildren('layersroot');">{t}check all{/t}</a> -
<a href="javascript:checkChildren('layersroot',false);">{t}uncheck all{/t}</a></div>
<div id="layersroot">
{$layerlist}
</div>
