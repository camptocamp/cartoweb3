<?xml version='1.0' encoding='{$encoding}'?>
<pluginResponse>
{foreach from=$pluginResponses key=pluginName item=pluginResponse}
	<plugin name="{$pluginName}">
	{foreach from=$pluginResponse->getVariables() key=variableId item=variableValue}
		<variable id="{$variableId}" value="{$variableValue|escape}"/>
	{/foreach}
	{foreach from=$pluginResponse->getHtmlCode() key=htmlCodeId item=htmlCodeContent}
		<htmlCode id="{$htmlCodeId}" value="{$htmlCodeContent|escape}"/>
	{/foreach}
	</plugin>
{/foreach}
</pluginResponse>