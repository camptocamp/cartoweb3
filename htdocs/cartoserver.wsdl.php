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

/**
 * Project handler
 */
require_once(CARTOSERVER_HOME . 'server/ServerProjectHandler.php');

require_once(CARTOSERVER_HOME . 'common/misc_functions.php');


$wsdlContent = file_get_contents(CARTOSERVER_HOME . 'server/cartoserver.wsdl');


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
                
if (array_key_exists('mapId', $_REQUEST) && $_REQUEST['mapId'] != '')
    $mapId = $_REQUEST['mapId'];

$soapAddress .= '/server.php';

if (isset($mapId))
    $soapAddress .= '?mapId=' . $mapId;

$wsdlContent = str_replace('{SOAP_ADDRESS}', $soapAddress, $wsdlContent);

$pluginsRequest = '';
$pluginsResult = '';
$pluginsSpecificWsdl = '';
    
if (isset($mapId)) {
    $projectHandler = new ServerProjectHandler($mapId);
    
    $iniFile = 'server_conf/' . $projectHandler->getMapName() . '/' . 
                                $projectHandler->getMapName() . '.ini';
    $iniFile = CARTOSERVER_HOME . $projectHandler->getPath(CARTOSERVER_HOME, $iniFile);

    $iniArray = parse_ini_file($iniFile);    
    if (array_key_exists('mapInfo.loadPlugins', $iniArray)) {
        $plugins = ConfigParser::parseArray($iniArray['mapInfo.loadPlugins']);
        
        foreach ($plugins as $plugin) {
            $pluginsRequest .= 
                '          <element name="' . $plugin . 'Request" type="types:' .
                ucfirst($plugin) . 'Request" minOccurs="0"/>
                ';

            $pluginsResult .= 
                '          <element name="' . $plugin . 'Result" type="types:' .
                ucfirst($plugin) . 'Result" minOccurs="0"/>
                ';

            $pluginFile = 'plugins/' . $plugin . '/common/' . $plugin . '.wsdl.inc';
            $pluginFile = CARTOSERVER_HOME . $projectHandler->getPath(CARTOSERVER_HOME, $pluginFile);
            
            $pluginsSpecificWsdl .= file_get_contents($pluginFile);
        }
    }
}

$wsdlContent = str_replace('{PLUGINS_REQUEST}', $pluginsRequest, $wsdlContent);
$wsdlContent = str_replace('{PLUGINS_RESULT}', $pluginsResult, $wsdlContent);
$wsdlContent = str_replace('{PLUGINS_SPECIFIC_WSDL}', $pluginsSpecificWsdl, $wsdlContent);

print $wsdlContent;

?>