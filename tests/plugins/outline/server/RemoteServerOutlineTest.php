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
    
    function addShape($shapeClass, &$array, &$area) {
        $shape = new $shapeClass;
        switch ($shapeClass) {
        case 'Point':
            $point = new Point();
            $point->setXY(1, 2);
            $array[] = $point;
            break;
        case 'Rectangle':
            $rect = new Rectangle();
            $rect->setFromBbox(1, 2, 3, 5);
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
            $array[] = $poly;
            $area += 8.0;
            break;
        }
    }
    
    function doOutlineRequestSimpleShape($shapeClass, $maskMode, $direct = false) {
        $outlineRequest = new OutlineRequest(); 

        $outlineRequest->shapes = array();
        $area = 0.0;
        $this->addShape($shapeClass, $outlineRequest->shapes, $area);
        $outlineRequest->maskMode = $maskMode;

        $mapRequest = $this->createRequest();
        $mapRequest->outlineRequest = $outlineRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals($mapResult->outlineResult->area, $area);
    }
    
    function doOutlineRequestPoint($maskMode, $direct = false) {
        $this->doOutlineRequestSimpleShape('Point', $maskMode, $direct);
    }
    
    function testOutlineRequestNoMaskPoint($direct = false) {
        $this->doOutlineRequestPoint(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskPoint($direct = false) {
        $this->doOutlineRequestPoint(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestRectangle($maskMode, $direct = false) {
        $this->doOutlineRequestSimpleShape('Rectangle', $maskMode, $direct);
    }
    
    function testOutlineRequestNoMaskRectangle($direct = false) {
        $this->doOutlineRequestRectangle(false, $direct);
        // FIXME: not working in direct mode., but why ?
        //$this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskRectangle($direct = false) {
        $this->doOutlineRequestRectangle(true, $direct);
        // FIXME: not working in direct mode. but why ?
        //$this->redoDirect($direct, __METHOD__);
    }

    function doOutlineRequestPolygon($maskMode, $direct = false) {
        $this->doOutlineRequestSimpleShape('Polygon', $maskMode, $direct);
    }
    
    function testOutlineRequestNoMaskPolygon($direct = false) {
        $this->doOutlineRequestPolygon(false, $direct);
        $this->redoDirect($direct, __METHOD__);
    }

    function testOutlineRequestMaskPolygon($direct = false) {
        $this->doOutlineRequestPolygon(true, $direct);
        $this->redoDirect($direct, __METHOD__);
    }
    
    function doOutlineRequestComplex($maskMode, $direct = false) {
        $outlineRequest = new OutlineRequest(); 

        $outlineRequest->shapes = array();
        $area = 0.0;
        $this->addShape('Point', $outlineRequest->shapes, $area);
        $this->addShape('Rectangle', $outlineRequest->shapes, $area);
        $this->addShape('Polygon', $outlineRequest->shapes, $area);
        $this->addShape('Point', $outlineRequest->shapes, $area);
        $this->addShape('Rectangle', $outlineRequest->shapes, $area);
        $this->addShape('Polygon', $outlineRequest->shapes, $area);
        $outlineRequest->maskMode = $maskMode;

        $mapRequest = $this->createRequest();
        $mapRequest->outlineRequest = $outlineRequest;
        
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertEquals($mapResult->outlineResult->area, $area);
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