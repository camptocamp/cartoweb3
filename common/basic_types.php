<?php

require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

class Dimension extends Serializable {
    public $width;
    public $height;

    function __construct($width, $height) {
        parent::__construct();
        $this->width = $width;
        $this->height = $height;
    }

    function unserialize ($struct) {
        $this->width = $struct->width;
        $this->height = $struct->height;
    }
}

abstract class Shape extends Serializable {
    /*
    const TYPE_POINT = 1;
    const TYPE_RECTANGLE = 2;
    const TYPE_POLYGON = 3;
    
    public $type;
    */
        
    abstract  function getCenter();
}

class Point extends Shape {
    public $x;
    public $y;

    function __construct($x, $y) {
        parent::__construct();
        $this->x = $x;
        $this->y = $y;
    }
    
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
        return clone $this;
    }
    
    function toBbox($margin = 0) {
        $bbox = new Bbox();
        if ($margin > 0) {
        
        	// TODO
            $bbox->setFromBbox($this->x, $this->y, $this->x, $this->y);
        } else {
            $bbox->setFromBbox($this->x, $this->y, $this->x, $this->y);
        }
        return $bbox;
    }
}

class Bbox extends Shape {
    public $minx, $miny, $maxx, $maxy;

    function unserialize($struct) {
        if (is_string($struct)) {
            $struct = $this->setFromString($struct);
        } else {
            $this->setFromBbox ($struct->minx, $struct->miny,
                                $struct->maxx, $struct->maxy);
        }
    }

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
            /*
            (int)$this->minx, (int)$this->miny, 
            (int)$this->maxx, (int)$this->maxy,
            (int)$this->getWidth(), (int)$this->getHeight()); */
    }
}

class Rectangle extends Bbox {
    function unserialize ($struct) {
    }
}

class Polygon extends Shape {
    /* todo: store points */
    function unserialize ($struct) {
    }
    function getCenter() {
        /* todo */
    }
}

?>