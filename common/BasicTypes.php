<?php
/**
 * Basic data structures
 * @package Common
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * Represents an image's dimension
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Dimension extends Serializable {

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @param int
     * @param int
     */
    function __construct($width = 0, $height = 0) {
        parent::__construct();
        $this->width = $width;
        $this->height = $height;
    }

    function unserialize($struct) {
        $this->width = $struct->width;
        $this->height = $struct->height;
    }
}

/**
 * Represents an image's dimension and bbox
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class GeoDimension extends Serializable {

    /**
     * @var Dimension
     */
    public $dimension;
    
    /**
     * @var Bbox
     */
    public $bbox;

    function __construct() {
        parent::__construct();
    }

    function unserialize ($struct) {
        $this->dimension = self::unserializeObject($struct, 'dimension', 'Dimension');
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * Abstract class for shapes
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
abstract class Shape extends Serializable {
        
    /**
     * Computes the shape's center
     * @return Point center
     */
    abstract function getCenter();

    /**
     * Computes the shape's area
     * @return double surface
     */
    abstract function getArea();
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
     * @param double
     * @param double
     */
    function __construct($x = 0, $y = 0) {
        parent::__construct();
        $this->x = $x;
        $this->y = $y;
    }
    
    function unserialize ($struct) {
        $this->x = $struct->x;
        $this->y = $struct->y;
    }
    
    /**
     * @return double
     */
    function getX() {
        return $this->x;
    }
    
    /**
     * @return double
     */
    function getY() {
        return $this->y;
    }
    
    /**
     * @param double
     * @param double
     */
    function setXY($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
    
    function getCenter() {
    
        // A point's center is the point itself
        return clone $this;
    }
    
    function getArea() {
        return 0.0;
    }
    
    /**
     * Converts the Point to a Bbox
     *
     * Optional margin will create a square around the point.
     * @param double the margin
     * @return Bbox the Point converted to Bbox
     */
    function toBbox($margin = 0) {
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
     * @param double
     * @param double
     * @param double
     * @param double
     */     
    function __construct($minx = 0, $miny = 0, $maxx = 0, $maxy = 0) {
        parent::__construct();
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    /**
     * Unserializes a Bbox.
     *
     * Value passed can be either a string (format "11, 22, 33, 44") or
     * a structure.
     * @param mixed a string or stdclass
     */
    function unserialize($struct) {
        if (is_string($struct)) {
            $struct = $this->setFromString($struct);
        } else {
            $this->setFromBbox ($struct->minx, $struct->miny,
                                $struct->maxx, $struct->maxy);
        }
    }

    /**
     * Converts a string to the Bbox (format "11, 22, 33, 44")
     * @param string a string
     */
    function setFromString($value) {
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
    function setFromBbox($minx, $miny, $maxx, $maxy) {
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    /**
     * Sets Bbox from a Mapserver extent
     * @param msExtent a Mapserver extent
     */
    function setFromMsExtent($ms_extent) {
        $this->setFromBbox($ms_extent->minx, $ms_extent->miny,
                           $ms_extent->maxx, $ms_extent->maxy);
    }

    /**
     * Sets Bbox from two points
     * @param Point first point
     * @param Point second point
     */
    function setFrom2Points(Point $point0, Point $point1) {
        $this->setFromBbox(min($point0->getX(), $point1->getX()),
                           min($point0->getY(), $point1->getY()),
                           max($point0->getX(), $point1->getX()),
                           max($point0->getY(), $point1->getY()));
    }

    /**
     * Computes Bbox width
     * @return double width
     */
    function getWidth() {
        return $this->maxx - $this->minx;
    }

    /**
     * Computes Bbox height
     * @return double height
     */
    function getHeight() {
        return $this->maxy - $this->miny;
    }

    function getCenter() {

        $width = $this->getWidth();
        $height = $this->getHeight();

        return new Point($this->minx + ($width / 2.0),
                         $this->miny + ($height / 2.0));
    }
    
    function getArea() {
        return $this->getWidth() * $this->getHeight();
    }
    
    /**
     * Converts Bbox to string for display
     * @return string Bbox as a string
     */
    function __toString() {
        $args = array($this->minx, $this->miny, $this->maxx, $this->maxy,
                      $this->getWidth(), $this->getHeight());
        $round = 2;
        $roundArray = array_fill(0, count($args), $round);
        $args = array_map('round', $args, $roundArray);
        $args = array_merge(array('BBOX(%s %s;%s %s [%s %s])'), $args);
        return call_user_func_array('sprintf', $args);
    }
}

/**
 * A rectangle
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Rectangle extends Bbox {
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

    function unserialize($struct) {
        $this->points = self::unserializeObjectMap($struct, 'points', 'Point');
    }

    function getCenter() {
        /* todo */
    }
    
    function getArea() {
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

?>