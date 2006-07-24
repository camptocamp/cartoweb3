<?php
/**
 * Shape converter
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
 * ShapeConverter Facade
 * @package Server
 * @author Damien Corpataux <damien.corpataux@camptocamp.com>
 */
class ShapeConverterFacade {

    /**
     * Converts a shape coordinates
     * @param Shape
     * @param projIn PROJ.4 format (i.e: epsg:4326)
     * @param projOut PROJ.4 format (i.e: epsg:4326)
     */
    public function convertCoordinates(Shape $shape, $projEpsgIn, $projEpsgOut) {
        $shapeClass = get_class($shape);
        $converterClass = $shapeClass . 'Converter';            
        if (!class_exists($converterClass)) {
            throw new CartoserverException('No converter found ' .
                                    "for shape type: $shapeClass. " .
                                    "A $converterClass has to be implemented.");
        }
        $converter = new $converterClass;
        $converter->convertCoordinates($shape, $projEpsgIn, $projEpsgOut);
    }
}


/**
 * Abstract class for ShapeConverters
 * @package Server
 * @author Damien Corpataux <damien.corpataux@camptocamp.com>
 */
abstract class ShapeConverter {
    
    /**
     * Converts a shape coordinates
     * @param Shape
     * @param projIn PROJ.4 format (i.e: epsg:4326)
     * @param projOut PROJ.4 format (i.e: epsg:4326)
     */
    abstract public function convertCoordinates(Shape &$shape,
                                                $projEpsgIn, $projEpsgOut);
    
    public function convertPointCoordinates(Point $point, $projEpsgIn, $projEpsgOut) {
        $msProj = $this->buildMsProjections($projEpsgIn, $projEpsgOut);
        $msPoint = ms_newPointObj();
        $msPoint->setXY($point->x, $point->y);
        $msPoint->project($msProj['in'], $msProj['out']);
        return new Point($msPoint->x, $msPoint->y);
    }

    public function buildMsProjections($projEpsgIn, $projEpsgOut) {
        $msProjIn = ms_newProjectionObj("init=$projEpsgIn");        
        $msProjOut = ms_newProjectionObj("init=$projEpsgOut");
        return array('in' => $msProjIn, 'out' => $msProjOut);        
    }
}


class PointConverter extends ShapeConverter {
    
    /**
     * @see ShapeConverter::convertCoordinates()
     */
    public function convertCoordinates(Shape &$point, $projEpsgIn, $projEpsgOut) {
        $convertedPoint = $this->convertPointCoordinates($point, $projEpsgIn, $projEpsgOut);
        $point->x = $convertedPoint->x;
        $point->y = $convertedPoint->y;
    }    
}

class LineConverter extends ShapeConverter {
    
    /**
     * @see ShapeConverter::convertCoordinates()
     */
    public function convertCoordinates(Shape &$line, $projEpsgIn, $projEpsgOut) {
        foreach ($line->points as &$point) {
            $convertedPoint = $this->convertPointCoordinates($point, $projEpsgIn, $projEpsgOut);
            $point->x = $convertedPoint->x;
            $point->y = $convertedPoint->y;
        }
    }
    
}

class PolygonConverter extends LineConverter {}

class BboxConverter extends ShapeConverter {
    
    /**
     * @see ShapeConverter::convertCoordinates()
     */
    public function convertCoordinates(Shape &$bbox, $projEpsgIn, $projEpsgOut) {
        $minPoint = new Point($bbox->minx, $bbox->miny);
        $maxPoint = new Point($bbox->maxx, $bbox->maxy);
        $convertedMinPoint = $this->convertPointCoordinates($minPoint, $projEpsgIn, $projEpsgOut);
        $convertedMaxPoint = $this->convertPointCoordinates($maxPoint, $projEpsgIn, $projEpsgOut);
        $bbox->minx = $convertedMinPoint->x;
        $bbox->miny = $convertedMinPoint->y;
        $bbox->maxx = $convertedMaxPoint->x;
        $bbox->maxy = $convertedMaxPoint->y;
    }
    
}

class RectangleConverter extends BboxConverter {}

class CircleConverter extends ShapeConverter {
    
    /**
     * @see ShapeConverter::convertCoordinates()
     */
    public function convertCoordinates(Shape &$circle, $projEpsgIn, $projEpsgOut) {
        $center = new Point($circle->x, $circle->y);
        $convertedCenter = $this->convertPointCoordinates($center, $projEpsgIn, $projEpsgOut);
        $circle->x = $convertedCenter->x;
        $circle->y = $convertedCenter->y;
    }
    
}

?>
