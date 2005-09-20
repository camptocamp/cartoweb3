<?xml version="1.0" encoding="{$charset}" standalone="yes"?>
<viewdata>
 {foreach from=$plugins key=pluginName item=plugin}
 <plugin name="{$pluginName}" recorderversion="{$plugin.recorderVersion}">
 {$plugin.data}
 </plugin>
 {/foreach}
</viewdata>
