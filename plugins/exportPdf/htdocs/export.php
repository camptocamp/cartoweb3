<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Dir path to Cartoclient home
 */
define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/../../..') . '/');
define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);
require_once(CARTOCOMMON_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array('client' => true));

require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();

$plugin = $cartoclient->getPluginManager()->getCurrentPlugin();
$plugin->handleHttpPostRequest($_REQUEST);

$pdfbuffer = $plugin->getExport()->getContents();

$plugin->outputPdf($pdfbuffer);

?>
