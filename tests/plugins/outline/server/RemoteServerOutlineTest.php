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
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOCOMMON_HOME . 'plugins/outline/common/Outline.php');
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server outline plugin via webservice. 
 *
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class plugins_outline_server_RemoteServerOutlineTest
                    extends client_CartoserverServiceWrapper {

    function isTestDirect() {
        return true;   
    }
    
    function addShape($shapeClass, &$array, &$area, $labelText = '') {
        $shape = new $shapeClass;
        switch ($shapeClass) {
        case 'Point':
            $point = new Point();
            $point->setXY(1, 2);
            $point->label = $label;
            $array[] = $point;
            break;
        case 'Line':
            $line = new Line();
            $line->points[] = new Point(1, 2);
            $line->points[] = new Point(1, 6);
            $line->points[] = new Point(3, 2);
            $line->points[] = new Point(3, 6);
            $line->points[] = new Point(5, 2);
            $line->label = $label;
            $array[] = $line;
            break;
        case 'Rectangle':
            $rect = new Rectangle();
            $rect->setFromBbox(1, 2, 3, 5);
            $rect->label = $label;
            $array[] = $rect;
            $area += 6.0;
            break;
        case 'Polygon' :
            $poly = new Polygon();
            $poly->points[] = new Point(1, 2);
            $poly->points[] = new Point(1, 6);
            $poly->points[] = new Point(3, 2);
            $poly->points[] = new Point(3, 6);
            $poly->points[] = new Point(5, 2);
            $poly->label = $label;
            $array[] = $poly;
            $area += 8.0;
            break;
        }
    }
    
    function doOutlineRequestSimpleShape($shapeClass, $maskMode, $labelMode, $labelText = '', $direct = false) {
        $outlineRequest = new OutlineRequest(); 

        $outlineRequest->shapes = array();
        $area = 0.0;
        $this->addShape($shapeClass, $outlineRequest->shapes, $area, $labelText);
        $outlineRequest->maskMode = $maskMode;
        $outlineRequest->labelMode = $labelMode;

        $mapRequest = $this->createRequest();
        $mapRequest->outlineRequest = $outlineRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals($area, $mapResult->outlineResult->area);
    }
    
    function doOutlineRequestPoint($maskMode, $labelMode, $direct = false, $labelText = '') {
        $this->doOutlineRequestSimpleShape('Point', $maskMode, $labelMode, $labelText, $direct);
    }
    
    function testOutlineRequestNoMaskPoint($direct = false) {
        $this->doOutlineRequestPoint(false, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskPoint($direct = false) {
        $this->doOutlineRequestPoint(true, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestLabelPoint($direct = false) {
        $this->doOutlineRequestPoint(false, true, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestLine($maskMode, $direct = false) {
        $this->doOutlineRequestSimpleShape('Line', $maskMode, $labelMode, '', $direct);
    }
    
    function testOutlineRequestNoMaskLine($direct = false) {
        $this->doOutlineRequestLine(false, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskLine($direct = false) {
        $this->doOutlineRequestLine(true, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function testOutlineRequestLabelLine($direct = false) {
        $this->doOutlineRequestLine(false, true, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestRectangle($maskMode, $direct = false) {
        $this->doOutlineRequestSimpleShape('Rectangle', $maskMode, $labelMode, '', $direct);
    }
    
    function testOutlineRequestNoMaskRectangle($direct = false) {
        $this->doOutlineRequestRectangle(false, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskRectangle($direct = false) {
        $this->doOutlineRequestRectangle(true, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function testOutlineRequestLabelRectangle($direct = false) {
        $this->doOutlineRequestRectangle(false, true, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestPolygon($maskMode, $direct = false) {
        $this->doOutlineRequestSimpleShape('Polygon', $maskMode, $labelMode, '', $direct);
    }
    
    function testOutlineRequestNoMaskPolygon($direct = false) {
        $this->doOutlineRequestPolygon(false, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskPolygon($direct = false) {
        $this->doOutlineRequestPolygon(true, false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function testOutlineRequestLabelPolygon($direct = false) {
        $this->doOutlineRequestPolygon(false, true, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }
    
    function doOutlineRequestComplex($maskMode, $direct = false) {
        $outlineRequest = new OutlineRequest(); 

        $outlineRequest->shapes = array();
        $area = 0.0;
        $this->addShape('Point', $outlineRequest->shapes, $area, '');
        $this->addShape('Line', $outlineRequest->shapes, $area, '');
        $this->addShape('Rectangle', $outlineRequest->shapes, $area, '');
        $this->addShape('Polygon', $outlineRequest->shapes, $area, '');
        $this->addShape('Point', $outlineRequest->shapes, $area, '');
        $this->addShape('Line', $outlineRequest->shapes, $area, '');
        $this->addShape('Rectangle', $outlineRequest->shapes, $area, '');
        $this->addShape('Polygon', $outlineRequest->shapes, $area, '');
        $outlineRequest->maskMode = $maskMode;

        $mapRequest = $this->createRequest();
        $mapRequest->outlineRequest = $outlineRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals($area, $mapResult->outlineResult->area);
    }

    function testOutlineRequestNoMaskComplex($direct = false) {
        $this->doOutlineRequestComplex(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskComplex($direct = false) {
        $this->doOutlineRequestComplex(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
}

?>