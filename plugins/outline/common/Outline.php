<?
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class OutlineRequest {
    public $shapes;
}

/**
 * @package Plugins
 */
class Shape {
    const SHAPE_RECTANGLE = 1;
    const SHAPE_POLYGON = 2;
}

/**
 * @package Plugins
 */
class Polygon extends Shape {
    public $type = Shape::SHAPE_POLYGON;
}

/**
 * @package Plugins
 */
class Rectangle extends Shape {
    public $type = Shape::SHAPE_RECTANGLE;
    public $bbox;
}

?>

