<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOSERVER_HOME
    . 'projects/testproject/coreplugins/projectLocation/common/ProjectLocation.php');
require_once(CARTOSERVER_HOME
    . 'projects/testproject/plugins/projectplugin/common/Projectplugin.php');
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server testproject project via webservice. 
 *
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class projects_testproject_server_RemoteServerProjectTest
                    extends client_CartoserverServiceWrapper {

    private $testDirect = true;

    protected function getMapId() {
        return 'testproject.projectmap';
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
}

?>