<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

{*
{config_load file=smarty_config.conf section="setup"}
*}

<html:html locale="true">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="css/style.css" title="stylesheet">
<meta name="author" content="Sylvain Pasche">
<meta name="email" content="sylvain dot pasche at camptocamp dot com">
<title>{$cartoclient_title}</title>
<html:base/>
</head>
<body>

<logic:present name="cartoclientForm">

<div class="banner">
        <h1>
            {$cartoclient_title}
        </h1>
</div>

<form method="post" action="{$smarty.server.PHP_SELF}" name="main_form">

<div class="leftbar">

    <logic:equal name="config" property="drawKeyMap" value="true">
                <div align="center">
                        <html-el:image src="$*#cartoclientForm.keyMap.url#*" property="keyMapButton"/>
                </div>
    </logic:equal>

     <logic:equal name="config" property="useMapSize" value="true">
     <p>
        <bean:message key="cartoclient.mapSize"/>
     </p>
      <html:select property="mapSize" onchange="document.cartoclientForm.submit();">
       <html:options collection="cartoInfo_mapsize" property="value"   labelProperty="label"/>
      </html:select>
     </logic:equal>
          
      <logic:equal name="config" property="useLevel" value="true">      
        <p>
                <bean:message key="cartoclient.level"/>
        </p> 
        <html:select property="level" onchange="document.cartoclientForm.submit();">
                <html:options collection="cartoInfo_level" property="value"   labelProperty="label"/>
        </html:select>
       </logic:equal>

     <logic:equal name="config" property="useBboxRegion" value="true">    
      <p>
        <bean:message key="cartoclient.bboxRegion"/>
      </p>   
          <html:select property="bboxRegion" onchange="document.cartoclientForm.submit();">
        <html:options collection="cartoInfo_bboxregion" property="value"   labelProperty="label"/>
      </html:select>
      </logic:equal>

     <logic:equal name="config" property="useScaleList" value="true">    
      <p>
      <bean:message key="cartoclient.scaleList"/>
      </p>   
          <html:select property="scaleList" onchange="document.cartoclientForm.submit();">
        <html:options collection="cartoInfo_scalelist" property="value"   labelProperty="label"/>
      </html:select>
      </logic:equal>
      
<logic:equal name="config" property="useJsTree" value="false">

        <logic:iterate id="layer" name="cartoInfo" property="layersInfo">
                 <p>
         <html:multibox property="selectedLayers">
                                <bean:write name="layer" property="name"/>
                        </html:multibox> 
        <!--
        <img src="clientIcons/<bean:write name="layer" property="icon"/>" />
          -->         


         <bean:write name="layer" property="caption"/>
                </p>

</logic:iterate>

</logic:equal>

<p>
<html:image page="/icons/icon_redraw.png" property="strutsButton"/>
<bean:message key="cartoclient.reload"/>
</p>

<logic:equal name="config" property="useJsTree" value="true">

<div align="left"  style="align: left" >
<!-- DHTML folders -->

<!--
<link rel="StyleSheet" href="tree.css" type="text/css">
<script language="Javascript" type="text/javascript" src="js/tree.js"></script>
<script language="Javascript" type="text/javascript">
   var Tree = new Array;
-->

<app:jsTree />
</script>           
  <div class="tree" id="tree">
    <script type="text/javascript">
        <!--
            createTree(Tree, 0, null);
          //-->
        </script>
        </div> 
  </div>
</logic:equal>

<!-- smarty part -->

<p>
{* html_checkboxes name="layers" options=$layers selected=$selected_layers separator="<br />" *}
</p>

<p>
_2_

{$layers2}
</p>

<p>
_3_

{$layers3}
</p>


<p>
_4_

{$layers4}
</p>


<p>
<input type="submit" name="refresh" value="refresh"/>
<input type="submit" name="reset_session" value="reset_session"/>

</p>

{if $hello_active|default:''}
<p>
Hello plugin test:
</p>
<p>
<input type="text" name="hello_input"/>
</p>
{/if}


{if $outliner_active|default:''}
<p>
Outliner plugin:
</p>
<p>
{html_checkboxes name="outliners" options=$outliners selected=$selected_outliners separator="<br />"}
</p>
{/if}


</div>

<div class="content">
<html:errors/>

<p>
{html_radios name="tool" options=$tools selected=$selected_tool }
</p>


<p>
<html:radio property="tool" value="zoom_in"/>
<html:img page="/icons/tool_zoomin_1.gif"/>
<bean:message key="tool.zoomIn"/>

<html:radio property="tool" value="zoom_out"/>
<html:img page="/icons/tool_zoomout_1.gif"/>
<bean:message key="tool.zoomOut"/>

<html:radio property="tool" value="recenter"/>
<html:img page="/icons/tool_recentre_1.gif"/>
<bean:message key="tool.recenter"/>

<html:radio property="tool" value="query_point"/>
<html:img page="/icons/tool_info_1.gif"/>
<bean:message key="tool.query"/>

</p>

<p>
<table>
<tr>
        <td>
                <input type="image" src="gfx/layout/north_west.gif" name="pan_nw"/>
        <td align="center">
                <input type="image" src="gfx/layout/north.gif" name="pan_n"/>
        <td>
                <input type="image" src="gfx/layout/north_east.gif" name="pan_ne"/>
<tr>
        <td>
                <input type="image" src="gfx/layout/west.gif" name="pan_w"/>
        <td>
                <input type="image" src="{$mainmap_path}" name="mainmap"/>
        <td>
                <input type="image" src="gfx/layout/east.gif" name="pan_e"/>
<tr>
        <td>
                <input type="image" src="gfx/layout/south_west.gif" name="pan_sw"/>
        <td align="center">
                <input type="image" src="gfx/layout/south.gif" name="pan_s"/>
        <td>
                <input type="image" src="gfx/layout/south_east.gif" name="pan_se"/>
<tr>
        <td/>
        <td align="center">
            <logic:equal name="config" property="drawScaleBar" value="true">
                <html-el:img src="$*#cartoclientForm.scaleBar.url#*"/>
                </logic:equal>
        <td/>
</table>
</p>

        <logic:equal name="cartoclientForm" property="noResults" value="true">
<div style="color:red">
<bean:message key="cartoclient.noResults"/>
</div>
        </logic:equal>

{if $hello_message|default:''}
<h1>Hello plugin says: {$hello_message}</h1>
{/if}

<logic:present name="cartoclientForm" property="queryResultInfos">

<logic:iterate id="results" name="cartoclientForm" property="queryResultInfos">
        <h1>Info</h1>

        <table>
        <tr><td>
                <bean:write name="results" property="name"/><br/>
        <tr>
        <logic:iterate id="field" name="results" property="fields">
                <td>
                <bean:write name="field"/><br/>
        </logic:iterate>
        
        <logic:iterate id="line" name="results" property="cells">
                        <tr>
                        <logic:iterate id="cell" name="line">
                                <td>
                                <bean:write name="cell"/><br/>
                        </logic:iterate>
        </logic:iterate>
        </table>
</logic:iterate>
</logic:present>

</html:form>
</form>

</div>
<!--
<div align="right"><p><a href="http://validator.w3.org/check/referer"><img src="http://www.w3.org/Icons/valid-html401" height="31" width="88" border="0" alt="Valid HTML 4.01!"></a></p></div>
-->

</logic:present>


<pre>
<hr/>
Request:
{$debug_request}
<hr/>
ClientContext:
{$debug_clientcontext}
<hr/>
</pre>


</body>
</html:html>
