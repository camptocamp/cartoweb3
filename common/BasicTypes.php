<?php
/**
 * Basic data structures
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
 * @package Common
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOWEB_HOME . 'common/CwSerializable.php');

/**
 * Represents an image's dimension
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Dimension extends CwSerializable {

    /**
     * Width of the image
     * @var int
     */
    public $width;

    /**
     * Height of the image
     * @var int
     */
    public $height;

    /**
     * Constructor
     * @param int
     * @param int
     */
    public function __construct($width = 0, $height = 0) {
        parent::__construct();
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->width = $struct->width;
        $this->height = $struct->height;
    }
}

/**
 * Represents an image's dimension and bbox
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class GeoDimension extends CwSerializable {

    /**
     * @var Dimension
     */
    public $dimension;
    
    /**
     * @var Bbox
     */
    public $bbox;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->dimension = self::unserializeObject($struct, 'dimension', 'Dimension');
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * Abstract class for shapes
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
abstract class Shape extends CwSerializable {
    /**
     * Computes the shape's center
     * @return Point center
     */
    abstract public function getCenter();

    /**
     * Computes the shape's area
     * @return double surface
     */
    abstract public function getArea();
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
    }
}

/**
 * A single point
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Point extends Shape {

    /**
     * @var double
     */
    public $x;
    
    /**
     * @var double
     */
    public $y;
    
    /**
     * Constructor
     * @param double
     * @param double
     * @param string
     */
    public function __construct($x = 0, $y = 0) {
        parent::__construct();
        $this->x = $x;
        $this->y = $y;
    }
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->x = $struct->x;
        $this->y = $struct->y;
        parent::unserialize($struct);
    }
    
    /**
     * @return double
     */
    public function getX() {
        return $this->x;
    }
    
    /**
     * @return double
     */
    public function getY() {
        return $this->y;
    }
    
    /**
     * @param double
     * @param double
     */
    public function setXY($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
   
    /**
     * @see Shape::getCenter()
     */
    public function getCenter() {
    
        // A point's center is the point itself
        return clone $this;
    }
    
    /**
     * @see Shape::getArea()
     */
    public function getArea() {
        return 0.0;
    }
    
    /**
     * Converts the Point to a Bbox
     *
     * Optional margin will create a square around the point.
     * @param double the margin
     * @return Bbox the Point converted to Bbox
     */
    public function toBbox($margin = 0) {
        $bbox = new Bbox();
        if ($margin > 0) {    
            $bbox->setFromBbox($this->x - $margin, $this->y - $margin,
                               $this->x + $margin, $this->y + $margin);
        } else {
            $bbox->setFromBbox($this->x, $this->y, $this->x, $this->y);
        }
        return $bbox;
    }
}

/**
 * A (poly)line
 * @package Common
 */
class Line extends Shape {

    /**
     * Array of points
     * @var array
     */
    public $points;
     
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->points = self::unserializeObjectMap($struct, 'points', 'Point');
        parent::unserialize($struct);
    }

    /**
     * @see Shape::getCenter()
     */
    public function getCenter() {
        // returns the middle point of the two extremities
        $points = $this->points;
        if (count($points) == 0)
            return new Point();
        $middleX = ($points[0]->x + $points[count($points) - 1]->x) / 2;
        $middleY = ($points[0]->y + $points[count($points) - 1]->y) / 2;
        return new Point($middleX, $middleY);
    }
     
    /**
     * @see Shape::getArea()
     * @return double
     */
    public function getArea() {
        return 0.0;
    }
}

/**
 * A bounding box (bbox)
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Bbox extends Shape {

    /**
     * @var double
     */
    public $minx;

    /**
     * @var double
     */
    public $miny;

    /**
     * @var double
     */
    public $maxx;

    /**
     * @var double
     */
    public $maxy;

    /**
     * Constructor
     * @param double
     * @param double
     * @param double
     * @param double
     */     
    public function __construct($minx = 0, $miny = 0, $maxx = 0, $maxy = 0) {
        parent::__construct();
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    /**
     * Unserializes a Bbox
     *
     * Value passed can be either a string (format "11, 22, 33, 44") or
     * a structure.
     * @param mixed a string or stdclass
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        if (is_string($struct)) {
            $struct = $this->setFromString($struct);
        } else {
            $this->setFromBbox ($struct->minx, $struct->miny,
                                $struct->maxx, $struct->maxy);
        }
        parent::unserialize($struct);
    }

    /**
     * Converts a string to the Bbox (format "11, 22, 33, 44")
     * @param string a string
     */
    public function setFromString($value) {
        list($minx, $miny, $maxx, $maxy) = explode(',', $value);

        $this->setFromBbox((double)$minx, (double)$miny, 
                           (double)$maxx, (double)$maxy);
    }

    /**
     * Sets Bbox from min/max
     * @param double minimum X
     * @param double minimum Y
     * @param double maximum X
     * @param double maximum Y
     */
    public function setFromBbox($minx, $miny, $maxx, $maxy) {
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    /**
     * Sets Bbox from a Mapserver extent
     * @param msExtent a Mapserver extent
     */
    public function setFromMsExtent($ms_extent) {
        $this->setFromBbox($ms_extent->minx, $ms_extent->miny,
                           $ms_extent->maxx, $ms_extent->maxy);
    }

    /**
     * Sets Bbox from two points
     * @param Point first point
     * @param Point second point
     */
    public function setFrom2Points(Point $point0, Point $point1) {
        $this->setFromBbox(min($point0->getX(), $point1->getX()),
                           min($point0->getY(), $point1->getY()),
                           max($point0->getX(), $point1->getX()),
                           max($point0->getY(), $point1->getY()));
    }

    /**
     * Computes Bbox width
     * @return double width
     */
    public function getWidth() {
        return $this->maxx - $this->minx;
    }

    /**
     * Computes Bbox height
     * @return double height
     */
    public function getHeight() {
        return $this->maxy - $this->miny;
    }

    /**
     * @see Shape::getCenter()
     * @return Point
     */
    public function getCenter() {

        $width = $this->getWidth();
        $height = $this->getHeight();

        return new Point($this->minx + ($width / 2.0),
                         $this->miny + ($height / 2.0));
    }
    
    /**
     * @see Shape::getArea()
     */
    public function getArea() {
        return $this->getWidth() * $this->getHeight();
    }
   
    /**
     * Returns the rounded values of an array of values to specified precision
     * @param array array of values
     * @param int precision (number of digits after the decimal point)
     * @return array of rounded values
     */
    private function roundParams($args, $round = 2) {
        $roundArray = array_fill(0, count($args), $round);
        return array_map('round', $args, $roundArray);
    }
    
    /**
     * Converts Bbox to a character-separated string to request remote layer
     * @param string separating character
     * @return string character-separated string
     */
    public function toRemoteString($divider = ' ') {
        $args = array($this->minx, $this->miny, $this->maxx, $this->maxy);
        return implode($divider, $this->roundParams($args));
    }
    
    /**
     * Converts Bbox to string for display
     * @return string Bbox as a string
     */
    public function __toString() {
        $args = array($this->minx, $this->miny, $this->maxx, $this->maxy,
                      $this->getWidth(), $this->getHeight());
        $args = array_merge(array('BBOX(%s %s;%s %s [%s %s])'), 
                            $this->roundParams($args));
        
        return call_user_func_array('sprintf', $args);
    }
}

/**
 * A rectangle
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Rectangle extends Bbox {
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        if (is_string($struct)) {
            $struct = $this->setFromString($struct);
        } else {
            $this->setFromBbox ($struct->minx, $struct->miny,
                                $struct->maxx, $struct->maxy);
        }
        parent::unserialize($struct);
    }
}

/**
 * A closed polygon
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Polygon extends Shape {
    
    /**
     * Array of points
     *
     * First point isn't repeated at the end.
     * @var array
     */
    public $points;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->points = self::unserializeObjectMap($struct, 'points', 'Point');
        parent::unserialize($struct);
    }

    /**
     * @see Shape::getCenter()
     * @return Point center
     */
     // FIXME: need to be check !
    public function getCenter() {
        $x = 0;
        $y = 0;
  
        $lastPoint = $this->points[count($this->points) - 1];
        foreach ($this->points as $point) {
            $x += ($lastPoint->x + $point->x) * 
                  ($lastPoint->x * $point->y - $point->x * $lastPoint->y);
            $y += ($lastPoint->y + $point->y) * 
                  ($lastPoint->x * $point->y - $point->x * $lastPoint->y);
        }

        $x /= count($this->points);
        $y /= count($this->points);
            
        return new Point($x, $y);
    }
    
    /**
     * @see Shape::getArea()
     * @return double
     */
    public function getArea() {
        if (count($this->points) < 3) {
            return 0.0;
        } else {
            $area = 0.0;
            $lastPoint = $this->points[count($this->points) - 1];
            foreach ($this->points as $point) {
                $area += ($lastPoint->x * $point->y
                          - $lastPoint->y * $point->x);
                $lastPoint = $point;
            }
            return abs($area / 2.0);
        }        
    }
}

/**
 * A circle
 * @package Common
 * @author Florent Giraud <florent.giraud@camptocamp.com>
 */
class Circle extends Shape {
    
    /**
     * Center x
     * @var object
     */
    public $x;

    /**
     * Center y
     * @var object
     */
    public $y;

    /**
     * Radius
     * @var double
     */
    public $radius;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->x = self::unserializeValue($struct, 'x', 'double');
        $this->y = self::unserializeValue($struct, 'y', 'double');
        $this->radius = self::unserializeValue($struct, 'radius', 'double');
        parent::unserialize($struct);
    }

    /**
     * @see Shape::getCenter()
     * @return Point
     */
    public function getCenter() {

        $center = new Point();
        $center->x = $this->x;
        $center->y = $this->y;
        
        return $this->center;
    }
    
    /**
     * @see Shape::getArea()
     * @return double
     */
    public function getArea() {
        
        return M_PI * $radius * $radius;
    }
}

/**
 * A feature
 * @package Common
 * @author Pierre GIRAUD <pierre.giraud@camptocamp.com>
 */
class Feature {

    /**
     * WKT string of the feature
     * @var string
     */
    public $WKTString;
    
    /**
     * feature id
     * @var string
     */
    public $id;

}

?>
