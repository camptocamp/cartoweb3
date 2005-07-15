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
class projects_testMain_coreplugins_location_server_ServerLocationTest 
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
        $this->assertSameBbox(new Bbox(0, 0, 2, 4), $merged);   

        $bboxes = array(new Bbox(-10, -1, 10, 1), new Bbox(-5, -.5, .5, 1));
        $merged = $recenterCalculator->mergeBboxes($bboxes);
        $this->assertSameBbox(new Bbox(-10, -1, 10, 1), $merged);   
    }
}
?>