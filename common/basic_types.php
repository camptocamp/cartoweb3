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

    public $width;
    public $height;

    function __construct($width=0, $height=0) {
        parent::__construct();
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param stdclass structure to deserialize
     */
    function unserialize ($struct) {
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

    public $dimension;
    public $bbox;

    function __construct() {
        parent::__construct();
    }

    /**
     * @param stdclass structure to deserialize
     */
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
}

/**
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Point extends Shape {

    public $x;
    public $y;

    function __construct($x=0, $y=0) {
        parent::__construct();
        $this->x = $x;
        $this->y = $y;
    }
    
    /**
     * @param stdclass structure to deserialize
     */
    function unserialize ($struct) {
        $this->x = $struct->x;
        $this->y = $struct->y;
    }
    
    function getX() {
        return $this->x;
    }
    
    function getY() {
        return $this->y;
    }
    
    function setXY($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
    
    function getCenter() {
    
        // A point's center is the point itself
        return clone $this;
    }
    
    /**
     * Converts the Point to a Bbox.
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
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Bbox extends Shape {
    public $minx, $miny, $maxx, $maxy;

    /**
     * Unserializes a Bbox.
     *
     * Value passed can be either a string (format "11, 22, 33, 44") or
     * a structure.
     * @param ? a string or stdclass
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

    function setFromBbox($minx, $miny, $maxx, $maxy) {
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    function setFromMsExtent($ms_extent) {
        $this->setFromBbox($ms_extent->minx, $ms_extent->miny,
                           $ms_extent->maxx, $ms_extent->maxy);
    }

    function setFrom2Points(Point $point0, Point $point1) {
        $this->setFromBbox(min($point0->getX(), $point1->getX()),
                           min($point0->getY(), $point1->getY()),
                           max($point0->getX(), $point1->getX()),
                           max($point0->getY(), $point1->getY()));
    }

    function getWidth() {
        return $this->maxx - $this->minx;
    }

    function getHeight() {
        return $this->maxy - $this->miny;
    }

    function getCenter() {

        $width = $this->getWidth();
        $height = $this->getHeight();

        return new Point($this->minx + ($width / 2.0),
                         $this->miny + ($height / 2.0));
    }
    
    function __toString() {
        return sprintf("BBOX(%s %s;%s %s [%s %s])", 
            $this->minx, $this->miny, 
            $this->maxx, $this->maxy,
            $this->getWidth(), $this->getHeight());
    }
}

/**
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Rectangle extends Bbox {
}

/**
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class Polygon extends Shape {
    public $points;

    function unserialize($struct) {
        $this->points = self::unserializeObjectMap($struct, 'points', 'Point');
    }

    function getCenter() {
        /* todo */
    }
}

?>