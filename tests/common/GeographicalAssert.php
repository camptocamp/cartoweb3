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
    
    protected function assertInsideBbox(Bbox $expected, Bbox $bbox) {
        $this->assertTrue(($expected->minx <= $bbox->minx) &&
                          ($expected->miny <= $bbox->miny) &&
                          ($expected->maxx >= $bbox->maxx) &&
                          ($expected->maxy >= $bbox->maxy),
                           "Expected bboxes: " . $expected->__toString() . 
                            "  does not contain : " . $bbox->__toString());
    }
}
 