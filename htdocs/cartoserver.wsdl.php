<?php
header("Content-Type: text/xml");

define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	
	// FIXME: duplicated from server.php

	set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOSERVER_HOME . 'include/');
                 
	require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');
	
	$serverConfig = new ServerConfig();
	
	if (!@$serverConfig->reverseProxyUrl)
		die('Reverse proxy seems to be used, but no "reverseProxyUrl" ' .
			'parameter set in configuration');
	
	$soapAddress = $serverConfig->reverseProxyUrl;
	
} else {
	$soapAddress = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . 
				$_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); 
}			
				
$soapAddress .= '/server.php';

$wsdlContent = file_get_contents(CARTOSERVER_HOME . 'server/cartoserver.wsdl');

print str_replace('{SOAP_ADDRESS}', $soapAddress, $wsdlContent);

?>