{literal}
<style type="text/css">
#layersroot { text-align:left;margin-left:10px; font-size:0.8em;}
.v, .nov { text-align:left;margin-left:10px;}
.lk { text-decoration:none;color:black;font-family:courier;font-size:1em;}
.nov { display:none;}
</style>
<script type="text/javascript">
  <!--
  function shift(id)
  {
    var obj = document.getElementById(id);
    var key = document.getElementById('x' + id);
    
    if(key.innerHTML == '-') { 
      key.innerHTML = '+';
      obj.style.display = 'none';
    }
    else {
      key.innerHTML = '-';
      obj.style.display = 'block';
    }
  }

  function expandAll(id)
  {
    var mydiv = document.getElementById(id);
    var divs = mydiv.getElementsByTagName('div');
    var key;
    
    for (var i = 0; i < divs.length; i++) {
      divs[i].style.display = 'block';
      key = document.getElementById('x' + divs[i].id);
      if(key) key.innerHTML = '-';
    }
  }

  function closeAll(id)
  {
    var mydiv = document.getElementById(id);
    var divs = mydiv.getElementsByTagName('div');
    var key;
    
    for (var i = 0; i < divs.length; i++) {    
      key = document.getElementById('x' + divs[i].id);
      if(key) key.innerHTML = '+';
        
      if(divs[i].getAttribute('id')) {
          divs[i].style.display = 'none';    
      }
    }
  }

  function checkChildren(id,val) {
    var mydiv = document.getElementById(id);
    if(!mydiv) return;
    
    var divs = mydiv.getElementsByTagName('input');
    if (val != false) val = true;

    for (var i = 0; i < divs.length; i++) {
      if(divs[i].name.substring(0, 6) == 'layers')
        divs[i].checked = val;
    }
  }
  
  function isChildrenChecked(id) {
    var dparent = document.getElementById(id);
    var celts = dparent.getElementsByTagName('input');
    for (var i = 0; i < celts.length; i++) {
      if (!celts[i].checked) return false;
    }
    return true;
  }

  function updateChecked(id,skipChildren)
  {
    var obj = document.getElementById('in' + id);
    if(!obj) return;
    var val = obj.checked;
    
    if (!skipChildren) checkChildren('id' + id, val);
    
    var pid = obj.parentNode.getAttribute('id');
    var iid = pid.substr(2);
    var iparent = document.getElementById('in' + iid);
   
    if (!iparent) return;

    // if node has been unchecked, makes sure parents are unchecked too
    if (val == false) {
      iparent.checked = false;
      updateChecked(iid, true);
    }
    // if all siblings are checked, makes sure parents are checked too
    else if (isChildrenChecked(pid)) {
      iparent.checked = true;
      updateChecked(iid, true);
    }
  }
//-->
</script>
{/literal}
<div id="layerscmd"><a href="javascript:expandAll('layersroot');">{$expand}</a> -
<a href="javascript:closeAll('layersroot');">{$close}</a><br />
<a href="javascript:checkChildren('layersroot');">{$check}</a> -
<a href="javascript:checkChildren('layersroot',false);">{$uncheck}</a></div>
<div id="layersroot">
{$layerlist}
</div>
