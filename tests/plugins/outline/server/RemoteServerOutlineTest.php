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

require_once(CARTOWEB_HOME . 'plugins/outline/common/Outline.php');
require_once(CARTOWEB_HOME . 'common/BasicTypes.php');

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
            $shape->setXY(1, 2);
            break;
        case 'Line':
            $shape->points[] = new Point(1, 2);
            $shape->points[] = new Point(1, 6);
            $shape->points[] = new Point(3, 2);
            $shape->points[] = new Point(3, 6);
            $shape->points[] = new Point(5, 2);
            break;
        case 'Rectangle':
            $shape->setFromBbox(1, 2, 3, 5);
            $area += 6.0;
            break;
        case 'Polygon' :
            $shape->points[] = new Point(1, 2);
            $shape->points[] = new Point(1, 6);
            $shape->points[] = new Point(3, 2);
            $shape->points[] = new Point(3, 6);
            $shape->points[] = new Point(5, 2);
            $area += 8.0;
            break;
        }
        $styledShape = new StyledShape();
        $styledShape->shape = $shape;
        $styledShape->label = $labelText;
        $array[] = $styledShape;
    }
    
    function doOutlineRequestSimpleShape($shapeClass, $maskMode, $labelText = '', $direct = false) {
        $outlineRequest = new OutlineRequest(); 

        $outlineRequest->shapes = array();
        $area = 0.0;
        $this->addShape($shapeClass, $outlineRequest->shapes, $area, $labelText);
        $outlineRequest->maskMode = $maskMode;

        $mapRequest = $this->createRequest();
        $mapRequest->outlineRequest = $outlineRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals($area, $mapResult->outlineResult->area);
    }
    
    function doOutlineRequestPoint($maskMode, $direct = false, $labelText = '') {
        $this->doOutlineRequestSimpleShape('Point', $maskMode, $labelText, $direct);
    }
    
    function testOutlineRequestNoMaskPoint($direct = false) {
        $this->doOutlineRequestPoint(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskPoint($direct = false) {
        $this->doOutlineRequestPoint(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestLabelPoint($direct = false) {
        $this->doOutlineRequestPoint(false, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestLine($maskMode, $direct = false, $labelText = '') {
        $this->doOutlineRequestSimpleShape('Line', $maskMode, $labelText, $direct);
    }
    
    function testOutlineRequestNoMaskLine($direct = false) {
        $this->doOutlineRequestLine(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskLine($direct = false) {
        $this->doOutlineRequestLine(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function testOutlineRequestLabelLine($direct = false) {
        $this->doOutlineRequestLine(false, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestRectangle($maskMode, $direct = false, $labelText = '') {
        $this->doOutlineRequestSimpleShape('Rectangle', $maskMode, $labelText, $direct);
    }
    
    function testOutlineRequestNoMaskRectangle($direct = false) {
        $this->doOutlineRequestRectangle(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskRectangle($direct = false) {
        $this->doOutlineRequestRectangle(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function testOutlineRequestLabelRectangle($direct = false) {
        $this->doOutlineRequestRectangle(false, $direct, 'FooBar');
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestPolygon($maskMode, $direct = false, $labelText = '') {
        $this->doOutlineRequestSimpleShape('Polygon', $maskMode, $labelText, $direct);
    }
    
    function testOutlineRequestNoMaskPolygon($direct = false) {
        $this->doOutlineRequestPolygon(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskPolygon($direct = false) {
        $this->doOutlineRequestPolygon(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function testOutlineRequestLabelPolygon($direct = false) {
        $this->doOutlineRequestPolygon(false, $direct, 'FooBar');
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