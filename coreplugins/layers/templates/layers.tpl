{literal}
<style>
#layersroot { text-align:left;margin-left:10px;}
.v, .nov { text-align:left;margin-left:10px;}
.lk { text-decoration:none;color:black;font-family:courier;font-size:8px;}
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
    
    for (i = 0; i < divs.length; i++) {
        divs[i].style.display = 'block';
        var key = document.getElementById('x' + divs[i].id);
        if(key) key.innerHTML = '-';
    }
}

function closeAll(id)
{
    var mydiv = document.getElementById(id);
    var divs = mydiv.getElementsByTagName('div');
    
    for (i = 0; i < divs.length; i++) {    
        var key = document.getElementById('x' + divs[i].id);
        if(key) key.innerHTML = '+';
        
        if(divs[i].getAttribute('id')) {
            divs[i].style.display = 'none';    
        }
    }
}

function updateChecked(id, isLayerGroup)
{
    //var obj = document.getElementById(id);
    // to be continued... 
}
//-->
</script>
{/literal}
<p style="font-size:0.9em;text-align:center;"><a href="#" onclick="expandAll('layersroot')">expand</a> -
<a href="#" onclick="closeAll('layersroot')">close</a></p>
<div id="layersroot">
{$layerlist}
</div>
