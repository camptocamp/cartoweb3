<?php
/**
 * @package Htdocs
 * @version $Id$
 */

/**
 * For debugging purpose only
 */
@include_once('client_debug.php');

define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);

require_once(CARTOCOMMON_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array('client' => true, 'apd' => true));

require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();

$cartoclient->main();

?>