<?php

class Dimension {
    public $width;
    public $height;

    function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }
}

class Point {
    public $x;
    public $y;

    function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
}


class Bbox {
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
}
?>