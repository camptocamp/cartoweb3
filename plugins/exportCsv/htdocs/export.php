<?php
/**
 * Script called by standard Cartoclient GUI to generate export outside of
 * current browser
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
$plugin->handleHttpRequest($_REQUEST);

header('Content-Type: application/csv-tab-delimited-table');
header('Content-disposition: filename=' . $plugin->fileName);

print $plugin->getExport()->getContents();

?>
