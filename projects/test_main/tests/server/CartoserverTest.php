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
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');

/**
 * Unit tests for class Cartoserver
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class projects_testMain_server_CartoserverTest extends PHPUnit2_Framework_TestCase {
    
    private $cartoserver;

    public function setUp() {
        $this->cartoserver = new Cartoserver();
    }

    public function testGetMapInfo() {
        $mapInfo = $this->cartoserver->getMapInfo('test_main.test');

        $this->assertNotNull($mapInfo);
        // TODO: more assertions     
    }

    /*
    public function testGetMap() {
        $mapRequest = new MapRequest();
        $mapRequest->mapId = 'test';
        
        $mapResult = $this->cartoserver->getMap($mapRequest);
        //var_dump($mapResult);
    }
    */
}
?>