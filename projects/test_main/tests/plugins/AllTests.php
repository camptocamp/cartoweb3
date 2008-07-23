<?php
/**
 *
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
 * Abstract test suite
 */
require_once 'PHPUnit/Framework/TestSuite.php';

/**
 * All plugins tests
 */
require_once(CARTOWEB_HOME . 'projects/test_main/tests/plugins/outline/server/RemoteServerOutlineTest.php');
// Please convert the RemoteServerSelectionTest test to use the new query plugin
// require_once(CARTOWEB_HOME . 'projects/test_main/tests/plugins/selection/server/RemoteServerSelectionTest.php');

/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class projects_testMain_plugins_AllTests {
    
    public static function suite() {
    
        $suite = new PHPUnit_Framework_TestSuite;

        $suite->addTestSuite('projects_testMain_plugins_outline_server_RemoteServerOutlineTest');
        // Please convert the RemoteServerSelectionTest test to use the new query plugin
        // $suite->addTestSuite('projects_testMain_plugins_selection_server_RemoteServerSelectionTest');

        return $suite;
    }
}

?>
