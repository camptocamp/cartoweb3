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

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'include/');


require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();

$cartoclient->main();

?>