<?

class OutlineRequest {
    public $shapes;
}

class Shape {
    const SHAPE_RECTANGLE = 1;
    const SHAPE_POLYGON = 2;
}

class Polygon extends Shape {
    public $type = Shape::SHAPE_POLYGON;
}

class Rectangle extends Shape {
    public $type = Shape::SHAPE_RECTANGLE;
    public $bbox;
}

?>

