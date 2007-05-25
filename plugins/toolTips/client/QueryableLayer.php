<?php
/**
 * QueryableLayer classes for toolTips plugin
 * Extensible classes to build a tooltip layer definition 
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
 * @package Plugins
 * @version $Id$
 */

require_once('ToolTipsLayerBase.php');

/**
 * @package Plugins
 */
class QueryableLayer extends ToolTipsLayerBase {

    /**
     * DSN for DB connection; if null, connection will not be established
     * @var string
     */    
    protected $dsn;
    
    /**
     * DB table name to query
     * @var string
     */    
    protected $dbTableName;

    /**
     * PEAR::DB connection object for queries
     * @var PEAR::DB
     */    
    protected $db;

    /**
     * Constructor
     */
    public function __construct() {}

   /**
     * Sets DSN.
     */
    public function setDsn($dsn) {
        $this->dsn = $dsn;
    }
    
    /**
     * Returns DSN.
     * @return string
     */
    public function getDsn() {
        return $this->dsn;
    }

    /**
     * Sets the PEAR::DB object for queries.
     * @param PEAR::DB PEAR::DB connection
     */
    public function setDb($db) {
        $this->db = $db;
    }    
    
    /**
     * @return PEAR::DB
     */
    public function getDb() {
        return $this->db;
    }    
    
    /**
     * Sets DB table name.
     * @param string
     */
    public function setDbTableName($dbTableName) {
        $this->dbTableName = $dbTableName;
    }

    /**
     * Stores the list of attributes to be returned (DB field names)
     * @param string
     */
    public function setReturnedAttributes($attributes) {
        $this->returnedAttributes = $attributes;
    }
    
    /**
     * Gets the list of attributes to be returned for the current layer.
     * @return string
     */
    protected function getReturnedAttributes() {
        return $this->returnedAttributes;
    }
    
    /**
     * This method is to be redefined to use a custom (extended)
     * ResultLayer object
     * @return LayerResult
     */
    protected function newLayerResult() {
        return new LayerResult();
    }

    /**
     * This is a hook to let child classes modify the LayerResult array
     * before it is added to the layer results to be rendered.
     * @param array array of LayerResult
     * @return array array of LayerResult
     */
    public function filterResults($layerResults) {
        return $layerResults;
    }
}

/*
 * Tooltips
 * Layer that can be queried with given coordinates (timeout_async)
 * @package Plugins
 */
class ByXyQueryableLayer extends QueryableLayer {

    /**
     * Geographic tolerance for queries (geographic unit)
     * @var float
     */    
    protected $tolerancePx = 0;

    /**
     * Geographic tolerance for queries (geographic unit)
     * @var float
     */    
    protected $toleranceGeo;

    /**
     * Name of the geometry column in database the table
     * @var string
     */    
    protected $dbGeomColumnName = 'the_geom';

    /**
     * PostGIS georef SRID value for the given table 
     * @var string
     */    
    protected $srid = -1;

    /**
     * Constructor
     */
    public function __construct() {}
    
    /**
     * Sets the tolerance.
     * @param integer tolerance in pixels 
     */
    public function setTolerance($tolerance) {
        $this->tolerance = $tolerance;
    }
    
    /**
     * Sets the srid of the geometry column in the DB table.
     * @param string
     */
    public function setSrid($srid) {
        $this->srid = $srid;
    }

    /**
     * Sets the name of the geometry column in the DB table.
     * @param string
     */
    public function setDbGeomColumnName($dbGeomColumnName) {
        $this->dbGeomColumnName = $dbGeomColumnName;
    }

    /**
     * converts a geographical point to a Box3D using tolerance
     * @param Dimension image size
     * @param float x geo coordinate
     * @param float y geo coordinate
     * @param Bbox current bbox in geographical coordinates
     * @param tolerance tolerance given in pixel
     * @return string
     */
    protected function pointToBox3D($x, $y, $width, $height, 
                                    Bbox $bbox, $tolerance = 3) {
        $deltax = ($bbox->maxx - $bbox->minx) / $width * $tolerance / 2;
        $deltay = ($bbox->maxy - $bbox->miny) / $height * $tolerance / 2;
        
        return sprintf('BOX3D(%s %s, %s %s)',
                       $x - $deltax, $y - $deltay,
                       $x + $deltax, $y + $deltay);
    }

    /**
     * This method is able to query attributes on a single table only;
     * if you want to perform a more complex query,
     * this method is to be redefined in a child class.
     * @param float geographic x coordinate for spatial condition
     * @param float geographic y coordinate for spatial condition
     * @param Dimension mainmap dimensions (width, height)
     * @param Bbox current mainmap extent
     * @return string an SQL query
     */
    protected function getXySqlQuery($geoX, $geoY,
                                     Dimension $dimension, Bbox $bbox) {
        
        $tolerance = (isset($this->tolerance)) ? $this->tolerance : 10;
        $bbox3D = $this->pointToBox3D($geoX, $geoY,
                                      $dimension->width,
                                      $dimension->height,
                                      $bbox, $tolerance);
        $toleranceGeo = ($bbox->maxx - $bbox->minx) / $dimension->width 
                        * $tolerance;
        
        return sprintf('SELECT %s FROM %s ' .
                       "WHERE %s && setSRID('%s'::box3d, %s) ".
                       "AND Distance(%s, GeometryFromText( '".
                       "POINT(%s %s)', %d ) ) < %s",
                       $this->getReturnedAttributes(),
                       $this->dbTableName,
                       $this->dbGeomColumnName,
                       $bbox3D,
                       $this->srid,
                       $this->dbGeomColumnName,
                       $geoX,
                       $geoY,
                       $this->srid,
                       $toleranceGeo);
    }

    /**
     * Returns the attributes to be returned specified in the given
     * QueryableLayer
     * @param QueryableLayer layer to query
     * @param float geographic x coordinate for spatial condition
     * @param float geographic y coordinate for spatial condition
     * @param Dimension mainmap dimensions (width, height)
     * @param Bbox current mainmap extent
     * @return array array of LayerResult
     */
    public function queryLayerByXy($geoX, $geoY, 
                                   Dimension $dimension, Bbox $bbox) {
        $db = $this->getDb();
        $dbResult = $db->query($this->getXySqlQuery($geoX, $geoY,
                                                    $dimension, $bbox));
        Utils::checkDbError($dbResult);

        $layerResults = array();
        $resultArray = array();
        while ($dbResult->fetchInto($resultArray, DB_FETCHMODE_ASSOC)) {
            $layerResult = $this->newLayerResult(); // new LayerResult();
            $layerResult->setId($this->getId());
            $layerResult->setLabel($this->getLabel());
            $layerResult->addAttributes($resultArray);
            $layerResult->setTemplate($this->getTemplate());
            $layerResults[] = $layerResult;
        }
        return $layerResults;
    }
}
?>
