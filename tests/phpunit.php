<?php
/**
 * Unit tests launcher
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Tests
 * @version $Id$
 */

/**
 * Root directory for client scripts
 */
define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');

// clears include_path, to prevent side effects
ini_set('include_path', '');

require_once(CARTOWEB_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array());

// This global tell the cartoclient not to output header or start sesssion. 
//  Otherwise it would cause problems because of already sent output.
$GLOBALS['headless']=true;
 
set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOWEB_HOME . 'tests/');

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

require 'PHPUnit/TextUI/Command.php';
?>
