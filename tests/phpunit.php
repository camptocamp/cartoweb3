<?php
/**
 * Unit tests launcher
 *
 * This was originally a PHPUnit2 file called PHPUnit2/pear-phpunit
 * @package Tests
 * @version $Id$
 */

/**
 * Root directory for client scripts
 */
define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'include/' . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'include/pear/');

require 'PHPUnit2/TextUI/TestRunner.php';
?>
