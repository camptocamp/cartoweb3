<script type="text/javascript" src="{r type=js plugin=outline}outline.js{/r}"></script>
<script type="text/javascript">
  /*<![CDATA[*/
    var lineDefaultLabel = '{t}line label{/t}';
    var polyDefaultLabel = '{t}polygon label{/t}';
    var rectangleDefaultLabel = '{t}rectangle label{/t}';
    var pointDefaultLabel = '{t}point label{/t}';
  /*]]>*/
</script>
<div id="outlineLabelInputDiv" style="position:absolute;visibility:hidden;padding:10px;background-color:#f5f5f5;border:1px dashed #dfdfdf;">
  <div>
      <input type="text" id="outline_label_text" name="outline_label_text"
      value="{t}your label{/t}" onfocus="this.value = '';
      {literal}this.form.onsubmit = function() {doSubmit()};"{/literal} />
      <input type="button" value="{t}ok{/t}" style="margin:1px"
      onclick="{literal}if (typeof(AjaxHandler) != 'undefined') {AjaxHandler.doAction('Outline.AddFeature'); return false;} else doSubmit();{/literal}" />
  </div>
</div>
