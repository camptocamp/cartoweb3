<?php

class Dimension {
    public $width;
    public $height;

    function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }
}

abstract class Shape {
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
        $this->x = $x;
        $this->y = $y;
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
        }

        $bbox->setFromBbox($this->x, $this->y, $this->x, $this->y);
        return $bbox;
    }
}

class Bbox extends Shape {
    public $minx, $miny, $maxx, $maxy;

    function setFromBbox($minx, $miny, $maxx, $maxy) {
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    function setFromMsExtent($ms_extent) {
        $this->minx = $ms_extent->minx;
        $this->miny = $ms_extent->miny;
        $this->maxx = $ms_extent->maxx;
        $this->maxy = $ms_extent->maxy;
    }

    function setFrom2Points(Point $point0, Point $point1) {
        $this->minx = min($point0->getX(), $point1->getX());
        $this->miny = min($point0->getY(), $point1->getY());
        $this->maxx = max($point0->getX(), $point1->getX());
        $this->maxy = max($point0->getY(), $point1->getY());
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

}

class Polygon extends Shape {
    /* todo: store points */
    function getCenter() {
        /* todo */
    }
}

?>