<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Unit tests for basic types
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_BasicTypesTest extends PHPUnit2_Framework_TestCase {

    public function testDimensionConstruct() {
    
        $dimension = new Dimension(123, 456);

        $this->assertEquals(123, $dimension->width);
        $this->assertEquals(456, $dimension->height);
    }

    public function testDimensionUnserialize() {
        
        $struct = new stdclass();
        $struct->width = 123;
        $struct->height = 456;
        
        $dimension = new Dimension(111, 222);
        $dimension->unserialize($struct);

        $this->assertEquals(123, $dimension->width);
        $this->assertEquals(456, $dimension->height);
    }

    public function testPointConstruct() {
    
        $point = new Point(123, 456);

        $this->assertEquals(123, $point->x);
        $this->assertEquals(456, $point->y);
    }
    
    public function testPointUnserialize() {
        
        $struct = new stdclass();
        $struct->x = 123;
        $struct->y = 456;
        
        $point = new Point(111, 222);
        $point->unserialize($struct);
        
        $this->assertEquals(123, $point->x);
        $this->assertEquals(456, $point->y);
    }

    public function testPointSetGet() {
    
        $point = new Point(0, 1);
        $point->setXY(123, 456);
        
        $this->assertEquals(123, $point->getX());
        $this->assertEquals(456, $point->getY());
    }
    
    public function testPointCenter() {
     
        $point = new Point(123, 456);
        $center = $point->getCenter();
        
        // Point center is equal to point itself
        $this->assertEquals(123, $center->getX());
        $this->assertEquals(456, $center->getY());
    }

    public function testPointToBbox() {
     
        $point = new Point(123, 456);
        $bbox = $point->toBbox(0);
        
        $this->assertEquals(123, $bbox->minx);
        $this->assertEquals(456, $bbox->miny);
        $this->assertEquals(123, $bbox->maxx);
        $this->assertEquals(456, $bbox->maxy);

        $bbox = $point->toBbox(10);
        
        $this->assertEquals(113, $bbox->minx);
        $this->assertEquals(446, $bbox->miny);
        $this->assertEquals(133, $bbox->maxx);
        $this->assertEquals(466, $bbox->maxy);
    }
    
    public function testBboxConstruct() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 56, 78);
        
        $this->assertEquals(12, $bbox->minx);
        $this->assertEquals(34, $bbox->miny);
        $this->assertEquals(56, $bbox->maxx);
        $this->assertEquals(78, $bbox->maxy);
    }
    
    public function testBboxUnserialize() {
        
        $struct = new stdclass();
        $struct->minx = 12;
        $struct->miny = 34;
        $struct->maxx = 56;
        $struct->maxy = 78;
        
        $bbox = new Bbox();
        $bbox->unserialize($struct);

        $this->assertEquals(12, $bbox->minx);
        $this->assertEquals(34, $bbox->miny);
        $this->assertEquals(56, $bbox->maxx);
        $this->assertEquals(78, $bbox->maxy);
    }
    
    public function testBboxFromMsExtent() {
    
        $bbox = new Bbox();
        $ms_extent->minx = 12;
        $ms_extent->miny = 34;
        $ms_extent->maxx = 56;
        $ms_extent->maxy = 78;
        $bbox->SetFromMsExtent($ms_extent);
        
        $this->assertEquals(12, $bbox->minx);
        $this->assertEquals(34, $bbox->miny);
        $this->assertEquals(56, $bbox->maxx);
        $this->assertEquals(78, $bbox->maxy);
    }
    
    public function testBboxFrom2Points() {
    
        $bbox = new Bbox();
        $point1 = new Point(12, 34);
        $point2 = new Point(56, 78);
        $bbox->SetFrom2Points($point1, $point2);
        
        $this->assertEquals(12, $bbox->minx);
        $this->assertEquals(34, $bbox->miny);
        $this->assertEquals(56, $bbox->maxx);
        $this->assertEquals(78, $bbox->maxy);
    }
    
    public function testBboxWidthHeight() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 60, 70);
        
        $this->assertEquals(48, $bbox->getWidth());
        $this->assertEquals(36, $bbox->getHeight());
    }
    
    public function testBboxCenter() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 60, 70);
        $point = $bbox->getCenter();
        
        $this->assertEquals(36.0, $point->x);
        $this->assertEquals(52.0, $point->y);
    }
    
}

?>
