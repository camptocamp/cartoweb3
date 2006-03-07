<script type="text/javascript">
  /*<![CDATA[*/
    var openNodes = new Array('{$startOpenNodes}');
    writeOpenNodes(true);
  /*]]>*/
</script>
<div id="wmsServerLayers">
  <div id="folder{$wmsLayers.server_id}" class="treeItem">
    <img border="0"
         src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}treeServer.gif{/r}"
         alt="treeServer" />
    <span class="treeLink" id="itemSpan{$wmsLayers.server_id}">
      {$wmsLayers.title}
    </span>
  </div>
  {defun name="drawChildren" list=$wmsLayers.layers}
    {foreach name="wmsLayersloop" from=$list item=wmsLayer}
      {if $wmsLayer.layers}
        <div id="folder{$wmsLayer.layer_id}" class="treeItem">
          <a href="javascript:shift('id{$wmsLayer.node_id}');" 
             id="xid{$wmsLayer.node_id}" class="lk">
            <img  alt="{if $wmsLayer.groupFolded}+{else}-{/if}"
                  src="{r type=gfx plugin=layers}{if $wmsLayer.groupFolded}plus{else}minus{/if}.gif{/r}" 
                  title="" style="margin-left:5px;"/>
          </a>
          <img border="0" alt="treeLayers" 
               src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}treeLayers.gif{/r}" />
          <span class="treeLink" id=itemSpan{$wmsLayer.layer_id}">
            {$wmsLayer.title}
          </span>
        </div>
        <div class="{if $wmsLayer.groupFolded}nov{else}v{/if}" 
             id="id{$wmsLayer.node_id}">
          {fun name="drawChildren" list=$wmsLayer.layers}
        </div>
      {else}
        <div id="item{$wmsLayer.layer_id}" style="display:block;">
          <table border="0" cellspacing="0" cellpadding="0" width="100%"><tbody>
            <tr><td valign="top">
              <img src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}blank.gif{/r}" 
                   height="22" width="18" />
            </td><td>
              <img id="itemIcon{$wmsLayer.layer_id}" border="0" alt="treeLayers"
                   src="{r plugin=wmsBrowser type=gfx/layout/exploreWmsLayers}treeLayers.gif{/r}" />
            </td><td nowrap="nowrap" valign="middle" width="100%">
              <span class="treeLink" id="itemSpan{$wmsLayer.layer_id}">
                <a href="javascript:previewLayer('{$wmsLayer.name}', '{$wmsLayer.title}', '{$wmsLayer.onlineresource}', '{$wmsLayer.server_version}', '{$wmsLayer.srs}', '{$wmsLayer.format}', '{$wmsLayer.latlonboundingbox}', '{$wmsLayer.abstract}', '{$wmsLayer.metadataurl_href}');" 
                   id="itemTextLink{$wmsLayer.layer_id}" class="treeLink">
                  {$wmsLayer.title}
                </a>
              </span>
            </td></tr>
          </tbody></table>
        </div>
      {/if}
    {/foreach}
  {/defun}
</div>
