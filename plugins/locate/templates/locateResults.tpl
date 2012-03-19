<?xml version="1.0" encoding="utf-8" ?>
<search-ajax-response>
   <response type="object" id="results">
       {foreach from=$items item=item}
       <item key="{$item.0}" value="{$item.1}" />
       {/foreach}
   </response>
</search-ajax-response>