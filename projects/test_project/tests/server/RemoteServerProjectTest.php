<?php
/**
 * Test project remote tests
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
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOWEB_HOME
    . 'projects/test_project/coreplugins/projectLocation/common/ProjectLocation.php');
require_once(CARTOWEB_HOME
    . 'projects/test_project/plugins/projectplugin/common/Projectplugin.php');
require_once(CARTOWEB_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server test_project project via webservice. 
 *
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class projects_testProject_server_RemoteServerProjectTest
                    extends client_CartoserverServiceWrapper {

    private $testDirect = true;

    protected function getMapId() {
        return 'test_project.projectmap';
    }
    
    public function testProjectIniFile($direct = false) {
        
        $mapRequest = $this->createRequest();
        $mapRequest->imagesRequest->mainmap->width = 800;
        $mapRequest->imagesRequest->mainmap->height = 900;
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals(500, $mapResult->imagesResult->mainmap->width);
        $this->assertEquals(900, $mapResult->imagesResult->mainmap->height);
        
        $this->redoDirect($direct, __METHOD__);
    }
    
    public function testProjectPlugin($direct = false) {
        
        $mapRequest = $this->createRequest();
        $projectRequest = new ProjectpluginRequest();
        $projectRequest->message = 'abcdefghijklmnopqrstuvwxyz';
        $mapRequest->projectpluginRequest = $projectRequest;
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals('nopqrstuvwxyzabcdefghijklm', 
                            $mapResult->projectpluginResult->shuffledMessage);
        
        $this->redoDirect($direct, __METHOD__);
    }
    
    // FIXME: projectLocation is not activated because of
    //  http://bugzilla.maptools.org/show_bug.cgi?id=1286
    //  Once fixed, uncomment the following.
    /*
    public function testProjectLocation($direct = false) {
        
        $mapRequest = $this->createRequest();
        $projectLocationRequest = new ProjectLocationRequest();
        $projectLocationRequest->projectRequest = 'abcdefghijklmnopqrstuvwxyz';

        $locationRequest = new LocationRequest();
        $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;
        
        $bboxLocationRequest = new BboxLocationRequest();
        $bboxLocationRequest->bbox = new Bbox(-1, 50, 1, 52);

        $locationRequest->bboxLocationRequest = $bboxLocationRequest;
        $projectLocationRequest->locationRequest = $locationRequest;
        
        $mapRequest->locationRequest = $projectLocationRequest;
        $mapRequest->imagesRequest->mainmap->width = 200;
        $mapRequest->imagesRequest->mainmap->height = 200;

        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertTrue($mapResult->locationResult
                          instanceof ProjectLocationResult);
        $this->assertEquals('nopqrstuvwxyzabcdefghijklm', 
                            $mapResult->locationResult->projectResult);
        $this->assertSameBbox(new Bbox(-1, 50, 1, 52),
                              $mapResult->locationResult->locationResult->bbox);
        
        $this->redoDirect($direct, __METHOD__);        
    }
    */
}

?>