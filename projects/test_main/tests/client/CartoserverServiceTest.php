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
require_once 'PHPUnit/Framework/TestCase.php';

require_once('client/CartoserverServiceWrapper.php');
require_once(CARTOWEB_HOME . 'client/CartoserverService.php');
require_once(CARTOWEB_HOME . 'common/Request.php');
require_once(CARTOWEB_HOME . 'coreplugins/images/common/Images.php');
require_once(CARTOWEB_HOME . 'plugins/exportPdf/common/ExportPdf.php');

/**
 * Unit tests for CartoserverService 
 *  
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class projects_testMain_client_CartoserverServiceTest extends client_CartoserverServiceWrapper {

    protected function getMapid() {
        
        return 'test_main.test';
    }

    public function testGetMapInfo($direct = false) {

        $mapInfo = $this->getMapInfo('test_main.test', $direct);
        $this->assertNotNull($mapInfo);
        $this->assertTrue(is_array($mapInfo->initialMapStates));
     
        $initialMapState = $mapInfo->getInitialMapStateById('default');
        $this->assertType('Bbox', $initialMapState->location->bbox);
     
        $this->assertType('GeoDimension', $mapInfo->keymapGeoDimension);
        $this->assertEquals(100, $mapInfo->keymapGeoDimension->dimension->width);
        $this->assertEquals(-0.5, $mapInfo->keymapGeoDimension->bbox->minx);

        $this->redoDirect($direct, __METHOD__);
    }
    
    public function testGetMap($direct = false) {
    
        $mapRequest = $this->createRequest();
        $mapResult = $this->getMap($mapRequest, $direct);
        $this->assertNotNull($mapResult->imagesResult->mainmap->path);
        // TODO: more assertions
        $this->redoDirect($direct, __METHOD__);
    }
}

?>
