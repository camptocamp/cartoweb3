<?php
/**
 * @package Htdocs
 * @version $Id$
 */

/**
 * For debugging purpose only
 */
@include_once('client_debug.php');

define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOWEB_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array('client' => true, 'apd' => true));

require_once(CARTOWEB_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();

$cartoclient->main();

?>