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
define('CARTOCOMMON_HOME', CARTOSERVER_HOME);

require_once(CARTOSERVER_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array('client' => false));

require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');

/**
 * Returns base URL of Cartoserver SOAP service. 
 * @param ServerConfig
 * @return string
 */
function getSoapAddress(ServerConfig $serverConfig) {
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    
        if (is_null($serverConfig->reverseProxyUrl))
            die('Reverse proxy seems to be used, but no "reverseProxyUrl" ' .
                'parameter set in configuration');
    
        $soapAddress = $serverConfig->reverseProxyUrl;
    } else {
        $soapAddress = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                    $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); 
    }
    return $soapAddress;
}           

/**
 * Returns querystring parameters.
 * @param ServerConfig
 * @return array
 */
function getQueryString($serverConfig) {
    $queryString = array();
    
    if (!is_null($serverConfig->savePosts) &&
        $serverConfig->savePosts)
        $queryString['save_posts'] = '1';
    return $queryString;
}

/**
 * Returns content of given plugin WSDL file.
 * @param string coreplugin/plugin name
 * @param ProjectHandler
 * @return string
 */
function getWsdlFileContents($name, $projectHandler) {

    $pluginFile = 'coreplugins/' . $name . '/common/' . $name . '.wsdl.inc';
    $pluginFile = CARTOSERVER_HOME
                  . $projectHandler->getPath($pluginFile);           
    if (!file_exists($pluginFile)) {
        $pluginFile = 'plugins/' . $name . '/common/' . $name . '.wsdl.inc';
        $pluginFile = CARTOSERVER_HOME
                      . $projectHandler->getPath($pluginFile);            
        if (!file_exists($pluginFile)) {
            return NULL;        
        }
    }
    return file_get_contents($pluginFile);
}

if (array_key_exists('mapId', $_REQUEST) && $_REQUEST['mapId'] != '')
    $mapId = $_REQUEST['mapId'];

$cartoserver = new Cartoserver();
$serverContext = $cartoserver->getServerContext($mapId);
$serverContext->loadPlugins();

$soapAddress = getSoapAddress($serverContext->config);
$queryString = getQueryString($serverContext->config);
$queryString['mapId'] = $mapId;                

$soapAddress .= '/server.php';

$wsdlContent = file_get_contents(CARTOSERVER_HOME . 'server/cartoserver.wsdl');
$soapAddress .= '?' . htmlentities(http_build_query($queryString));
$wsdlContent = str_replace('{SOAP_ADDRESS}', $soapAddress, $wsdlContent);

$pluginsRequest = '';
$pluginsResult = '';
$pluginsInit = '';
$pluginsSpecificWsdl = '';
    
if (isset($mapId)) {

    $projectHandler = $serverContext->getProjectHandler();    
    $plugins = $serverContext->getPluginManager()->getPlugins();
   
    foreach ($plugins as $plugin) {    
                
        $name = $plugin->getName();
        $eName = $plugin->getExtendedName();

        $pluginsSpecificWsdl .= getWsdlFileContents($name, $projectHandler);
        if ($name != $eName) {
            $pluginsSpecificWsdl .= getWsdlFileContents($eName, $projectHandler);
        }
        if ($plugin instanceof ClientResponder ||
            $plugin instanceof CoreProvider) {
        
            $useERequest = $plugin->useExtendedRequest();
            $useEResult = $plugin->useExtendedResult();
            
            $requName = $name;
            $resuName = $name;

            if ($useERequest) {
                $requName = $eName;
            }
            if ($useEResult) {
                $resuName = $eName;
            }

            $pluginsRequest .= 
                '          <element name="' . 
                $plugin->getName() . 'Request" type="types:' .
                ucfirst($requName) . 'Request" minOccurs="0"/>
                ';

            $pluginsResult .= 
                '          <element name="' . 
                $plugin->getName() . 'Result" type="types:' .
                ucfirst($resuName) . 'Result" minOccurs="0"/>
                ';
        }
        if ($plugin instanceof InitProvider) {
            $useEInit = $plugin->useExtendedInit();

            $initName = $name;

            if ($useEInit) {
                $initName = $eName;
            }

            $pluginsInit .= 
                '          <element name="' . 
                $plugin->getName() . 'Init" type="types:' .
                ucfirst($initName) . 'Init" minOccurs="0"/>
                ';
        }
    }
}

$wsdlContent = str_replace('{PLUGINS_REQUEST}', $pluginsRequest, $wsdlContent);
$wsdlContent = str_replace('{PLUGINS_RESULT}', $pluginsResult, $wsdlContent);
$wsdlContent = str_replace('{PLUGINS_INIT}', $pluginsInit, $wsdlContent);
$wsdlContent = str_replace('{PLUGINS_SPECIFIC_WSDL}', $pluginsSpecificWsdl, $wsdlContent);

print $wsdlContent;

?>
