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

require_once(CARTOWEB_HOME . 'common/BasicTypes.php');

/**
 * Unit tests for basic types
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_BasicTypesTest extends PHPUnit_Framework_TestCase {

    /**
     * Tests Dimension constructor
     */
    public function testDimensionConstruct() {
    
        $dimension = new Dimension(123, 456);

        $this->assertEquals(123, $dimension->width);
        $this->assertEquals(456, $dimension->height);
    }

    /**
     * Tests Dimension unserialization
     */
    public function testDimensionUnserialize() {
        
        $struct = new stdclass();
        $struct->width = 123;
        $struct->height = 456;
        
        $dimension = new Dimension(111, 222);
        $dimension->unserialize($struct);

        $this->assertEquals(123, $dimension->width);
        $this->assertEquals(456, $dimension->height);
    }

    /**
     * Tests Point constructor
     */
    public function testPointConstruct() {
    
        $point = new Point(123, 456);

        $this->assertEquals(123, $point->x);
        $this->assertEquals(456, $point->y);
    }
    
    /**
     * Tests Point unserialization
     */
    public function testPointUnserialize() {
        
        $struct = new stdclass();
        $struct->x = 123;
        $struct->y = 456;
        
        $point = new Point(111, 222);
        $point->unserialize($struct);
        
        $this->assertEquals(123, $point->x);
        $this->assertEquals(456, $point->y);
    }

    /**
     * Tests Point setting and getting attributes
     */
    public function testPointSetGet() {
    
        $point = new Point(0, 1);
        $point->setXY(123, 456);
        
        $this->assertEquals(123, $point->getX());
        $this->assertEquals(456, $point->getY());
    }
    
    /**
     * Tests Point center calculation
     */
    public function testPointCenter() {
     
        $point = new Point(123, 456);
        $center = $point->getCenter();
        
        // Point center is equal to point itself
        $this->assertEquals(123, $center->getX());
        $this->assertEquals(456, $center->getY());
    }

    /**
     * Tests Point to Bbox conversion
     */
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
    
    /**
     * Tests Bbox constructor
     */
    public function testBboxConstruct() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 56, 78);
        
        $this->assertEquals(12, $bbox->minx);
        $this->assertEquals(34, $bbox->miny);
        $this->assertEquals(56, $bbox->maxx);
        $this->assertEquals(78, $bbox->maxy);
    }
    
    /**
     * Tests Bbox unserialization
     */
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
    
    /**
     * Tests MsExtent to Bbox conversion
     */
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
    
    /**
     * Tests conversion from two points to Bbox
     */
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
    
    /**
     * Tests Bbox width and height calculation
     */
    public function testBboxWidthHeight() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 60, 70);
        
        $this->assertEquals(48, $bbox->getWidth());
        $this->assertEquals(36, $bbox->getHeight());
    }
    
    /**
     * Tests Bbox center calculation
     */
    public function testBboxCenter() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 60, 70);
        $point = $bbox->getCenter();
        
        $this->assertEquals(36.0, $point->x);
        $this->assertEquals(52.0, $point->y);
    }
    
    /**
     * Tests Bbox area calculation
     */
    public function testBboxArea() {
    
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 60, 70);
        $area = $bbox->getArea();
        
        $this->assertEquals(1728, $area);
    }
   
    /**
     * Tests Bbox string print
     */
    public function testBboxString() {
        $bbox = new Bbox();
        $bbox->setFromBbox(12, 34, 60, 70);
        $string = $bbox->toRemoteString();

        $this->assertEquals('12 34 60 70', $string);
    }
    
    /**
     * Tests Polygon unserialization
     */
    public function testPolygonUnserialize() {
        
        $structPoint1 = new stdclass();
        $structPoint1->x = 12;
        $structPoint1->y = 34;
        $structPoint2 = new stdclass();
        $structPoint2->x = 15;
        $structPoint2->y = 45;
        $structPoint3 = new stdclass();
        $structPoint3->x = 22;
        $structPoint3->y = 41;
        $pointArray = array($structPoint1, $structPoint2, $structPoint3);
        $struct = new stdclass();
        $struct->points = $pointArray;

        $polygon = new Polygon();
        $polygon->unserialize($struct);
        
        $this->assertEquals(12, $polygon->points[0]->x);
        $this->assertEquals(34, $polygon->points[0]->y);
        $this->assertEquals(15, $polygon->points[1]->x);
        $this->assertEquals(45, $polygon->points[1]->y);
        $this->assertEquals(22, $polygon->points[2]->x);
        $this->assertEquals(41, $polygon->points[2]->y);
    }

    /**
     * Tests Polygon area calculation
     */
    public function testPolygonArea() {
        
        $point1 = new Point();
        $point1->x = 12;
        $point1->y = 34;
        $point2 = new Point();
        $point2->x = 15;
        $point2->y = 45;
        $point3 = new Point();
        $point3->x = 18;
        $point3->y = 34;
        $pointArray = array($point1, $point2, $point3);
        $polygon = new Polygon();
        $polygon->points = $pointArray;

        $area = $polygon->getArea();
        
        $this->assertEquals(33.0, $area);
    }
    
    /**
     * Test Line center calculation
     */
    public function testLineCenter() {
        
        $line = new Line();
        $point1 = new Point();
        $point1->x = 12;
        $point1->y = 34;
        $point2 = new Point();
        $point2->x = 15;
        $point2->y = 45;
        $point3 = new Point();
        $point3->x = 18;
        $point3->y = 34;
        $pointArray = array($point1, $point2, $point3);
        $line->points = $pointArray;
        
        $center = $line->getCenter();
        $this->assertEquals(15, $center->x);
        $this->assertEquals(34, $center->y);
    }

    /**
     * Tests Circle unserialization
     */
    public function testCircleUnserialize() {
        
        $struct = new stdclass();
        $struct->x = 12;
        $struct->y = 34;
        $struct->radius = 7;

        $circle = new Circle();
        $circle->unserialize($struct);
        
        $this->assertEquals(12.0, $circle->x);
        $this->assertEquals(34.0, $circle->y);
        $this->assertEquals(7.0,  $circle->radius);
    }
}
?>
