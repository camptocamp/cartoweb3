<div id="wmsBrowserLight">
    <script language="javascript" type="text/javascript">    
        {literal}    
        function showResponse () {
            Element.hide($('loadWmsBrowerLight'));
            $('DivWmsLayersResult').innerHTML = req.responseText;
        }                        
        
        function getLayers() {           
            {/literal}{if $servers neq null}{literal}
                if (document.carto_form.wmsList.checked==true) {
                    selectBox = document.carto_form.owsUrlList;
                    var url = selectBox.options[selectBox.selectedIndex].value;
                } else if (document.carto_form.wmsUrl.checked==true) {
                    var url = document.carto_form.owsUrl.value;
                } else {
                    return;
                }
            {/literal}{else}{literal}
                var url = document.carto_form.owsUrl.value;
            {/literal}{/if}{literal}
            Element.show($('loadWmsBrowerLight'));
            new Ajax.Updater('DivWmsLayersResult',
                    '{/literal}{$selfUrl}{literal}?owsUrl='+url+
                    '&project={/literal}{$project}{literal}&owsInfoHarwester=1',
                    {onComplete: showResponse});
            document.carto_form.url.value = url;
        }
        {/literal}
    </script>

    <fieldset>
        <legend>{t}Quick OGC Layer Loader{/t}</legend>
        {if $servers neq null}
            <div style="text-align:left;">
                <input type="radio" name="serverType" id="wmsList" value="0" 
                       {if $userServerOn eq 0}checked="checked"{/if}/>
                <select id="owsUrlList" name="owsUrlList" style="width:180px;">
                    {foreach key=cid item=s from=$servers}
                        <option value="{$s.url}">{$s.label}</option>
                    {/foreach}
                </select><br></br>
                <input type="radio" name="serverType" id="wmsUrl" value="1" 
                       {if $userServerOn eq 1}checked="checked"{/if}/>
        {/if}
                <label for="wmsUrl">{t}Server URL{/t}</label>:
                <input type="text" size="22" id="owsUrl" name="owsUrl" value="{$userServer}"/>
                <input type="hidden" id="url" name="url" value=""/>
                <br/>
            </div>
            <input type="button" onclick="getLayers();" class="form_button" value="{t}LoadLayersList{/t}"/><br/>
            <br/>
               <div id="loadWmsBrowerLight" style="display:none;float:right;margin:10px;">
                <img src="{r type=gfx plugin=wmsBrowserLight}indicator.gif{/r}">
            </div>
              <div id="DivWmsLayersResult"></div>
              <!-- 
              Uncomment this part when using div with effect to display WmsBrowserLight plugin
              <div style='align:right'>
                <input type="button" onclick="Effect.toggle($('wms'), 'appear' );" class="form_button" value="{t}Close{/t}"/><br/>
              </div> 
              -->    
    </fieldset>
</div>