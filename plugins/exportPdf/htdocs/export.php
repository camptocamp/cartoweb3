<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Dir path to Cartoclient home
 */

//print '<pre>'; print_r($_REQUEST); print '</pre>';die;

define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/../../..') . '/');

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'include/');

require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();

$plugin = $cartoclient->getPluginManager()->getCurrentPlugin();
$plugin->handleHttpRequest($_REQUEST);

$pdfbuffer = $plugin->getExport()->getContents();

header('Content-type: application/pdf');
header('Content-Length: ' . strlen($pdfbuffer));
header('Content-Disposition: inline; filename=map.pdf');

print $pdfbuffer;

?>
