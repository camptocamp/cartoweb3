<?php
/**
 * @package Tests
 * @version $Id$
 */
require_once 'PHPUnit2/Framework/TestCase.php';

/**
 * Class extending TestCase with additional geographical assertions
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class common_GeographicalAssert extends PHPUnit2_Framework_TestCase {
    
    protected function almostEq($val1, $val2) {
        return round($val1 - $val2, 3) == 0.0;
    }
    
    protected function assertSameBbox(Bbox $expected, Bbox $bbox) {
        $this->assertTrue($this->almostEq($expected->minx, $bbox->minx) &&
                          $this->almostEq($expected->miny, $bbox->miny) &&
                          $this->almostEq($expected->maxx, $bbox->maxx) &&
                          $this->almostEq($expected->maxy, $bbox->maxy),
                           "different bboxes: expected : " . $expected->__toString() . 
                            "  acual: " . $bbox->__toString());
    }

}
 