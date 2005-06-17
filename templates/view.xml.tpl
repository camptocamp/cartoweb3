<?xml version='1.0' encoding="{$charset}" standalone='yes'?>
<view>
 <metadata>
 {foreach from=$metas key=metaName item=metaVal}
  <{$metaName}>{$metaVal}</{$metaName}>
 {/foreach}
 </metadata>
 <sessionData>{$sessionData}</sessionData>
</view>
