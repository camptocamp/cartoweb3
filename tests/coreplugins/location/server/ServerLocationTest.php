<?php
/**
 * @package Tests
 * @version $Id$
 */

require_once 'common/GeographicalAssert.php';

require_once(CARTOCOMMON_HOME . 'coreplugins/location/common/Location.php');
require_once(CARTOSERVER_HOME . 'server/ServerPlugin.php');
require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');
require_once(CARTOSERVER_HOME . 'coreplugins/location/server/ServerLocation.php');
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server location plugin.
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class coreplugins_location_server_ServerLocationTest 
            extends common_GeographicalAssert {

    public function testRecenterLocationCalculator() {
      
        $recenterRequest = new RecenterLocationRequest();
        $recenterCalculator = new RecenterLocationCalculator(NULL, 
                                                            $recenterRequest);

        $raised = false;
        try {
            $merged = $recenterCalculator->mergeBboxes(array());
        } catch (CartoserverException $e) {
            $raised = true;
        } 
        $this->assertTrue($raised, 'Exception not raised');
        
        $bboxes = array(new Bbox(0, 0, 1, 1));
        $merged = $recenterCalculator->mergeBboxes($bboxes);
        $this->assertSameBbox(new Bbox(0, 0, 1, 1), $merged);   

        $bboxes = array(new Bbox(0, 0, 1, 1), new Bbox(0, 0, 2, 4));
        $merged = $recenterCalculator->mergeBboxes($bboxes);
        $this->assertSameBbox(new Bbox(0, 0, 1, 4), $merged);   

        $bboxes = array(new Bbox(-10, -1, 10, 1), new Bbox(-5, -.5, .5, 1));
        $merged = $recenterCalculator->mergeBboxes($bboxes);
        $this->assertSameBbox(new Bbox(-10, -1, 10, 1), $merged);   
    }
}
?>