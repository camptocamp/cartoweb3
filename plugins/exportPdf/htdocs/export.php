<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Dir path to Cartoclient home
 */
define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/../../..') . '/');

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'include/');

require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();

$plugin = $cartoclient->getPluginManager()->getCurrentPlugin();
$plugin->handleHttpPostRequest($_REQUEST);

$pdfbuffer = $plugin->getExport()->getContents();

$plugin->outputPdf($pdfbuffer);

?>
