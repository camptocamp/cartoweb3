<?php
/**
 * @package Htdocs
 * @version $Id$
 */

header("Content-Type: text/xml");

/**
 * Root directory for server scripts
 */
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    
    $ini_array = parse_ini_file(CARTOSERVER_HOME . 'server_conf/server.ini');

    if (in_array('reverseProxyUrl', $ini_array))
        die('Reverse proxy seems to be used, but no "reverseProxyUrl" ' .
            'parameter set in configuration');
    
    $soapAddress = $ini_array['reverseProxyUrl'];
} else {
    $soapAddress = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . 
                $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); 
}           
                
$soapAddress .= '/server.php';

$wsdlContent = file_get_contents(CARTOSERVER_HOME . 'server/cartoserver.wsdl');

print str_replace('{SOAP_ADDRESS}', $soapAddress, $wsdlContent);

?>