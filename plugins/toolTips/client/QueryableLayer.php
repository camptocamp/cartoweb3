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
 
class QueryableLayer {

    /**
     * Layer Id
     * @var string 
     */    
    protected $id;

    /**
     * Layer label
     * @var string 
     */    
    protected $label;

    /**
     * Returned attributes
     * @var array array of string
     */    
    protected $returnedAttributes = array();

    /**
     * DSN for DB connection; if null, connection will not be established
     * @var string
     */    
    protected $dsn = NULL;
    
    /**
     * Custom template; if null, default template is use to render tooltip
     * @var string
     */    
    protected $template = NULL;

    /**
     * DB table name to query
     * @var string
     */    
    protected $dbTableName = NULL;

    /**
     * PEAR::DB connection object for queries
     * @var PEAR::DB
     */    
    protected $db = NULL;

    /**
     * Constructor
     */
    public function __construct() {}
    
    /**
     * Sets the id of the layer
     * @param string Id of the layer
     */
    public function setId($id) {
        $this->id = $id;
    }    

    /**
     * Gets id
     */
    public function getId() {
        return $this->id;
    }    

    /**
     * Sets the label of the layer
     * @param string Label of the layer
     */
    public function setLabel($label) {
        $this->label = $label;
    }    

    /**
     * Gets label
     */
    public function getLabel() {
        return isset($this->label) ? $this->label : $this->getId();
    }    
    
    /**
     * Sets dsn
     */
    public function setDsn($dsn) {
        $this->dsn = $dsn;
    }
    
    /**
     * Returns dsn
     */
    public function getDsn() {
        return $this->dsn;
    }

    /**
     * Sets the PEAR::DB object for queries
     * @param PEAR::DB PEAR::DB connection
     */
    public function setDb($db) {
        $this->db = $db;
    }    
    public function getDb() {
        return $this->db;
    }    
// TODO : remove checks, checks are done before in ToolTipsService construct
    /**
     * Checks if id is set
     */
    public function checkId() {
        if (empty($this->id)) {
            throw new Exception ("Id is not set!");
        }
    }

    /**
     * Checks if a dsn is set
     */
    public function checkDsn() {
        if (empty($this->dsn)) {
            throw new Exception ("DSN is not set for layer id: $this->id");
        }
    }

    /**
     * Sets db table name
     * @param string
     */
    public function setDbTableName($dbTableName) {
        $this->dbTableName = $dbTableName;
    }

    /**
     * Checks if db table name is set
     */
    public function checkDbTableName() {
        if (empty($this->dbTableName)) {
            throw new Exception ("DB table name is not set for layer id: " .
                $this->id. "Please use QueryableLayer::setDbTableName().");
        }
    }

    public function checkReturnedAttributes() {
        if (empty($this->returnedAttributes)) {
            throw new Exception ("No 'returned attributes' set for layer id: " .
                $this->id. "Please use QueryableLayer::addReturnedAttributes().");
        }
    }
    
    /**
     * Sets the PEAR::DB object for queries
     * @param PEAR::DB PEAR::DB connection
     */
    public function setCustomTemplate($template) {
        $this->template = $template;
    }
    public function getCustomTemplate() {
        return $this->template;
    }

    /**
     * @param string attribute name to be returned (DB field name)
     */
    public function addReturnedAttribute($attributeName) {
        $this->returnedAttributes[] = $attributeName;
    }
    
    /**
     * Returns a coma separated list of attributes
     */
    protected function getAttributesList() {
        $attributeList = '';
        foreach ($this->returnedAttributes as $attributeName) {
            $attributeList .= $attributeName . ',';
        }
        // Trims the last comma
        return substr($attributeList, 0, -1);
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
     * before it is added to the layer results to be rendered 
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
    
    public function __construct() {}

    /**
     * Sets the geographic tolerance
     * @param int tolerance in pixels 
     */
    public function setTolerancePx($tolerance) {
        // Unsets toleranceGeo if new tolerancePx is set
        if ($this->tolerancePx != $tolerance) {
            unset($this->toleranceGeo);
            $this->tolerancePx = $tolerance;
        }
    }

    /**
     * Sets the geographic tolerance from the set pixel tolerance using the
     * given scale
     * @param float scale (in geo unit per pixel)
     */
    public function convertTolerance($scale) {
        $this->toleranceGeo = $this->tolerancePx * $scale;
    }

    public function setDbGeomColumnName($dbGeomColumnName) {
        $this->dbGeomColumnName = $dbGeomColumnName;
    }

    /**
     * converts a geographical point to a Box3D using tolerance
     * @param Dimension image size
     * @param x geo coordinate
     * @param y geo coordinate
     * @param Bbox current bbox in geographical coordinates
     * @param tolerance tolerance given in pixel
     * @return box3D
     */
    protected function pointToBox3D($x, $y, $width, $height, 
                                    Bbox $bbox, $tolerance = 3) {
        $deltax = ($bbox->maxx - $bbox->minx) / $width * $tolerance / 2;
        $deltay = ($bbox->maxy - $bbox->miny) / $height * $tolerance / 2;
        
        $bbox3D = sprintf('BOX3D(%s %s, %s %s)',
                          $x - $deltax, $y - $deltay,
                          $x + $deltax, $y + $deltay
                          );
        
        return $bbox3D;        
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
    protected function getXySqlQuery($geoX, $geoY, Dimension $dimension,
        Bbox $bbox) {
        $this->checkDbTableName();
        $this->checkReturnedAttributes();

        $attributesList = $this->getAttributesList();
        $tableName = $this->dbTableName;
        $dbGeomColumnName = $this->dbGeomColumnName;        
                
        // TODO get tolerance from config
        $tolerance = 10;
        $bbox3D = $this->pointToBox3D($geoX, $geoY,
                                      $dimension->width,
                                      $dimension->height,
                                      $bbox, 
                                      $tolerance);
        $toleranceGeo = ($bbox->maxx - $bbox->minx) / $dimension->width 
                        * $tolerance;
        
        $sql = sprintf('SELECT %s FROM %s ' .
                       "WHERE %s && '%s'::box3d ".
                       "AND distance (%s, GeometryFromText( '".
                       "POINT(%s %s)', -1 ) ) < %s",
                       $attributesList,
                       $tableName,
                       $dbGeomColumnName,
                       $bbox3D,
                       $dbGeomColumnName,
                       $geoX,
                       $geoY,
                       $toleranceGeo
                       );
         return $sql;
        
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
    public function queryLayerByXy($geoX, $geoY, Dimension $dimension,
        Bbox $bbox) {
        $db = $this->getDb();
        $dbResult = $db->query($this->getXySqlQuery($geoX,
            $geoY, $dimension, $bbox));
        
        if (DB::isError($dbResult)) {
            throw new Exception("$dbResult->message, userinfo: " .
                    $dbResult->userinfo);
        }

        $layerResults = array();
        $resultArray = array();
        while ($dbResult->fetchInto($resultArray, DB_FETCHMODE_ASSOC)) {
            $layerResult = $this->newLayerResult(); // new LayerResult();
            $layerResult->setId($this->getId());
            $layerResult->setLabel($this->getLabel());
            $layerResult->addAttributes($resultArray);
            $layerResult->setCustomTemplate($this->getCustomTemplate());
            $layerResults[] = $layerResult;
        }
        return $layerResults;
    }
}

/*
 * Tooltips
 * Layer that can be queried with given id (timeout_async)
 * @package Plugins
 */
class ByIdQueryableLayer extends QueryableLayer {
    
    /**
     * Layer id attribute
     * @var string 
     */    
    protected $idAttribute = NULL;

    public function __construct() {}
    
    /**
     * Sets the id attribute of the layer
     * @param string id attribute of the layer
     */
    public function setIdAttribute($idAttribute) {
        $this->idAttribute = $idAttribute;
    }
    
    /**
     * This method is able to query attributes on a single table only;
     * if you want to perform a more complex query,
     * this method is to be redefined in a child class.
     * @param string id of the feature
     * @return string an SQL query
     */
    protected function getIdSqlQuery($id) {
        $this->checkDbTableName();
        $this->checkReturnedAttributes();

        $attributesList = $this->getAttributesList();
        $tableName = $this->dbTableName;
        
        $idAttributeName = $this->idAttribute;       
                
        // TODO get tolerance from config
        
        $sql = sprintf('SELECT %s FROM %s ' .
                       "WHERE %s = '%s'",
                       $attributesList,
                       $tableName,
                       $idAttributeName,
                       $id
                       );
         return $sql;
        
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
    public function queryLayerById($id) {
        $db = $this->getDb();
        $dbResult = $db->query($this->getIdSqlQuery($id));
        
        if (DB::isError($dbResult)) {
            throw new Exception("$dbResult->message, userinfo: " .
                    $dbResult->userinfo);
        }

        $layerResults = array();
        $resultArray = array();
        while ($dbResult->fetchInto($resultArray, DB_FETCHMODE_ASSOC)) {
            $layerResult = $this->newLayerResult(); // new LayerResult();
            $layerResult->setId($this->getId());
            $layerResult->setLabel($this->getLabel());
            $layerResult->addAttributes($resultArray);
            $layerResult->setCustomTemplate($this->getCustomTemplate());
            $layerResults[] = $layerResult;
        }
        return $layerResults;
    }
}

?>
