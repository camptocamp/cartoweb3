<script type="text/javascript" src="{r type=js plugin=outline}outline.js{/r}"></script>
<script type="text/javascript">
  /*<![CDATA[*/
    var lineDefaultLabel = '{t}line label{/t}';
    var polyDefaultLabel = '{t}polygon label{/t}';
    var rectangleDefaultLabel = '{t}rectangle label{/t}';
    var pointDefaultLabel = '{t}point label{/t}';
    var circleDefaultLabel = '{t}circle label{/t}';
    
    var displayMeasures = {if $outline_displayMeasures|default:''}true{else}false{/if};
  /*]]>*/
</script>
<div id="outlineLabelInputDiv">
  <div>
      <input type="text" id="outline_label_text" name="outline_label_text"
      value="{t}your label{/t}" onfocus="this.value = '';
      {literal}this.form.onsubmit = function() {doSubmit()};"{/literal} />
      <input type="button" value="{t}ok{/t}" style="margin:1px"
      onclick="return CartoWeb.trigger('Outline.AddFeature', 'doSubmit()');" />
  </div>
</div>
