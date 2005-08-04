<script type="text/javascript" src="{r type=js plugin=outline}outline.js{/r}"></script>
<div id="outlineLabelInputDiv"  style="position:absolute;visibility:hidden;padding:10px;background-color:#f5f5f5;border:1px dashed #dfdfdf;">
{literal}
  <div>
      <input type="text" id="outline_label_text" name="outline_label_text"
      value="your label" onfocus="this.value = '';
      this.form.onsubmit = function() {doSubmit()};" />
      <input type="button" value="ok" style="margin:1px"
      onclick="doSubmit();" />
{/literal}
  </div>
</div>