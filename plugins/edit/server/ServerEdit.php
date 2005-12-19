<?php
/**
 * Edit plugin
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
 
/**
 * Edit plugin
 * @package Plugins
 */

/**
 * Server Edit class
 * @package Plugins
 */
 
class ServerEdit extends ClientResponderAdapter {

    /**
     * @var Logger
     */
    protected $log;
    
    /**
     * Database object
     * @var DB
     */
    protected $db;
    
    /**
     * Id attribute
     * @var string
     */
    protected $idAttribute;
    
    /**
     * List of attributes
     * @var array
     */
    protected $attributes;

    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
        require_once('DB.php');
    }

    /**
     * Returns the Pear::DB database connection.
     * @return DB
     */    
    protected function getDb($layerId) {
        if ($this->db)
            return $this->db;
            
        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        $dsn = $this->getMetadataValueString($layerId,'edit_dsn');
        $dsn = $this->getDsn($layerId);
        
        if (!$dsn)
            throw new CartoserverException('Wrong database connection paramater');
        
        $this->db = DB::connect($dsn);
        Utils::checkDbError($this->db, 'Unable to connect to edit database');
        return $this->db;        
    }
    
    /**
     * metadata string value
     * @var string
     */
    protected function getMetadataValueString($layerId,$metadataName) {
        
        $msMapObj = $this->serverContext->getMapObj();
        
        // TODO error handling
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);

        // retrieve from metadata
        $metadataValue = $msLayer->getMetaData($metadataName);

        if (!empty($metadataValue)) {
            return $metadataValue;
        }
        
        return NULL;
    }
    
    /**
     * Gets the "edit_attributes" string from a list of attributes and from metadata
     * @var string layer id
     */
    protected function getEditableAttributes($attributes) {
        $editableAttributes = array();
        
        foreach ($attributes as $key=>$val) {
            if ($val['editable'])
                $editableAttributes[] = $key;
        }
        return $editableAttributes;
    }
    
    /**
     * Returns list of attributes to be returned
     * @param string layer id
     * @return array
     */
    protected function getAttributes($layerId) {

        $msMapObj = $this->serverContext->getMapObj();

        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        if (empty($msLayer)) {
            return array();
        }
        
        $attributes = array();
        
        $editableAttributesList = $this->getMetaDataValueString($layerId, 'edit_attributes');
        if (empty($editableAttributesList)) {
            throw new CartoserverException("no edit attributes parameter set in the mapfile for layer :" .$layerId);
        }
        $array = explode(',', $editableAttributesList);
        foreach ($array as $key=>$val) {
            $array[$key] = explode('|', $val); 
        }
        
        foreach ($array as $val) {
            if (isset ($val[1])) {
                $attributes[$val[0]]['editable'] = true;
                $attributes[$val[0]]['type'] = $val[1];
            } else {
                $attributes[$val[0]]['editable'] = false;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Converts the mapfile connection to acceptable DSN
     * @param string layer id
     * @return string dsn
     */
    protected function getDsn($layerId) {
        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        $connectionType = $msLayer->connectiontype;
        $connectionString = $msLayer->connection;
        
        $values = split(' ', $connectionString);
        foreach ($values as $value) {
            $param = split('=', $value);
            $key = $param[0];
            $dsn[$key] = $param[1];
        }
        if ($connectionType != MS_POSTGIS)
            throw new CartoserverException('Wrong database connection type : POSTGIS needed');
        $dsnString = 'pgsql://';
        $dsnString .= $dsn['user'].':'.$dsn['password'];
        $dsnString .= '@'.$dsn['host'];
        $dsnString .= (isset($dsn['port']) && $dsn['port']) ? ':'.$dsn['port']  : '';
        $dsnString .= '/'.$dsn['dbname'];
        
        return $dsnString;
    }
    
    /**
     * Gets the srid from metadata
     * @return integer srid
     */
    protected function getSrid() {
        $srid = $this->getMetadataValueString($this->layer,'edit_srid');
        return ($srid) ? $srid : -1;
    }
    
    /**
     * Gets the geometry column from metadata
     * @return string geometry column
     */
    protected function getGeomColumn() {
        $geometryColumn = $this->getMetadataValueString($this->layer,'edit_geometry_column');
        
        if (!$geometryColumn)
            throw new CartoserverException("Geometry column not set in mapfile" .
                    "for layer : " . $this->layer);
        
        return  $geometryColumn;
    }
    
    /**
     * Gets the geometry column from metadata
     * @return string geometry column
     */
    protected function getGeometryType() {
        $geometryType = $this->getMetadataValueString($this->layer,'edit_geometry_type');
        
        if (!$geometryType)
            throw new CartoserverException("Geometry type not set in mapfile" .
                    "for layer : " . $this->layer);
        
        if (strtoupper($geometryType) == "LINE")
            return "LINESTRING";
        else
            return  strtoupper($geometryType);
    }
    
    /**
     * Handles shapes input
     * @param array array of shapes
     * @return queryId
     */
    protected function insertFeature($feature) {
        $db = $this->getDb($this->layer);

        $editableAttributes = $this->getEditableAttributes($this->attributes);
        
        if (isset($feature->attributes) && $feature->attributes) {
            $attributesFieldsSql = "";
            $attributesValuesSql = "";
            foreach ($feature->attributes as $key=>$val) {
                $val = ($val == "")? "NULL" : "'" . Encoder::decode($val, 'config') . "'";
                if (in_array($key, $editableAttributes))
                    $attributesFieldsSql .= ", ".$key;
                    $attributesValuesSql .= ", " . $val . "";
            }
            if ($attributesValuesSql == "")
                $attributesValuesSql = NULL;
        } else {
            $attributesFieldsSql = "";
            $attributesValuesSql = NULL;
        }
        
        //TODO check layer type and feature type
        $sql = sprintf("INSERT INTO %s.%s (%s %s) " .
                       "VALUES (GeometryFromText('%s', %s) %s) ",
                               $this->editSchema,
                               $this->editTable,
                               $this->geomColumn,
                               $attributesFieldsSql,
                               $feature->WKTString,
                               $this->getSrid(),
                               $attributesValuesSql
                    );
                
        $this->log->debug($sql);
        $queryId = $db->query($sql);
        
        Utils::checkDbError($queryId, 'Unable to insert feature in edit database');
        
        return $queryId;
    }    
    
    /**
     * Handles shapes update
     * @param id id of the object to update
     * @param array array of shapes
     * @return queryId
     */
    protected function updateFeature($feature) {
        $db = $this->getDb($this->layer);
        
        $editableAttributes = $this->getEditableAttributes($this->attributes);
        
        if ($feature->attributes) {
            $attributesSql = NULL;
            foreach ($feature->attributes as $key=>$val) {
                $val = ($val == "")? "NULL" : "'" . Encoder::decode($val, 'config') . "'";
                if (in_array($key, $editableAttributes))
                    $attributesSql .= sprintf(", %s = %s", $key, $val);
            }
        } else {
            $attributesSql = "";
        }

        $sql = sprintf("UPDATE %s.%s SET " .
                       "%s = GeometryFromText('%s', %s) " .
                       "%s " .
                       "WHERE %s = %s",
                               $this->editSchema,
                               $this->editTable,
                               $this->geomColumn,
                               $feature->WKTString,
                               $this->getSrid(),
                               $attributesSql,
                               $this->idField,
                               $feature->id
                    );
                         
        $this->log->debug($sql);
        $queryId = $db->query($sql);
        
        Utils::checkDbError($queryId, 'Unable to update feature in database');
        
        return $queryId;
    }
    
    /**
     * Handles shapes deletion
     * @param id id of the object to update
     * @return queryId
     */
    protected function deleteFeature($feature) {
        $db = $this->getDb($this->layer);
        $sql = sprintf("DELETE FROM %s.%s " .
                       "WHERE %s = %s",
                               $this->editSchema,
                               $this->editTable,
                               $this->idField,
                               $feature->id
                    );     
                         
        $this->log->debug($sql);
        $queryId = $db->query($sql);
        
        Utils::checkDbError($queryId, 'Unable to delete feature in database');
        
        return $queryId;
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
    protected function pointToBox3D($x, $y, $width, $height, Bbox $bbox, $tolerance = 3) {
        $deltax = ($bbox->maxx - $bbox->minx) / $width * $tolerance / 2;
        $deltay = ($bbox->maxy - $bbox->miny) / $height * $tolerance / 2;
        
        $bbox3D = sprintf("BOX3D(%s %s, %s %s)",
                            $x - $deltax,
                            $y - $deltay,
                            $x + $deltax,
                            $y + $deltay
                          );
        
        return $bbox3D;        
    }
    
    /**
     * Handles shapes input
     * @param array array of shapes
     * @return 
     */
    protected function selectFeaturesByShape($shapes) {
        foreach ($shapes as $shape) {
            switch (get_class($shape)) {
                case 'Point':
                    $mainmap = $this->serverContext->getMapRequest()->imagesRequest->mainmap;
                    $width = $mainmap->width;
                    $height = $mainmap->height;
                    
                    $bbox = $this->serverContext->getMapResult()->locationResult->bbox;
                    
                    // TODO get tolerance from mapfile
                    $tolerance = 10;
                    $bbox3D = $this->pointToBox3D($shape->x,$shape->y,$width, $height, $bbox, $tolerance);
                    $toleranceGeo = ($bbox->maxx - $bbox->minx) / $width * $tolerance;
                    
                    $sql = sprintf("SELECT *, astext(%s) as %s FROM %s.%s " .
                                   "WHERE %s && '%s'::box3d ".
                                   "AND distance (%s, GeometryFromText( '".
                                   "POINT(%s %s)', -1 ) ) < %s",
                               $this->geomColumn,
                               $this->geomColumn,
                               $this->editSchema,
                               $this->editTable,
                               $this->geomColumn,
                               $bbox3D,
                               $this->geomColumn,
                               $shape->x,
                               $shape->y,
                               $toleranceGeo
                    );
                    $this->log->debug($sql);
                    $db = $this->getDb($this->layer);
                    
                    $db->setFetchMode(DB_FETCHMODE_ASSOC);
                    $r = $db->getAll($sql);
                    
                    Utils::checkDbError($r, 'Unable to select feature(s) in database');
                    
                    $features = array();
                    foreach ($r as $row) {
                        $feature = new Feature();
                    
                        $feature->WKTString = $row[$this->geomColumn];
                        $feature->id = $row[$this->idField];
                        foreach ($row as $attribute=>$value) {
                            // get all but geometry column as attribute
                            if ($attribute != $this->geomColumn) {
                                $feature->attributes[$attribute] = Encoder::encode($value, 'config');
                            }
                        }
                        
                        $features[] = $feature;
                    }
                    
                    return $features;
                    
                    break;
                case 'Rectangle':
                    $sql = sprintf("SELECT *, astext(%s) as %s FROM %s.%s " .
                                   "WHERE intersects (%s, 'BOX3D(%s %s, %s %s)'::box3d)",
                               $this->geomColumn,
                               $this->geomColumn,
                               $this->editSchema,
                               $this->editTable,
                               $this->geomColumn,
                               $shape->minx,
                               $shape->miny,
                               $shape->maxx,
                               $shape->maxy
                    );
                    
                    $this->log->debug($sql);
                    $db = $this->getDb($this->layer);
                    
                    $db->setFetchMode(DB_FETCHMODE_ASSOC);
                    $r = $db->getAll($sql);
                    
                    Utils::checkDbError($r, 'Unable to select feature(s) in database');
                    
                    $features = array();
                    foreach ($r as $row) {
                        $feature = new Feature();
                    
                        $feature->WKTString = $row[$this->geomColumn];
                        $feature->id = $row[$this->idField];
                        foreach ($row as $attribute=>$value) {
                            // get all but geometry column as attribute
                            if ($attribute != $this->geomColumn) {
                                $feature->attributes[$attribute] = Encoder::encode($value, 'config');
                            }
                        }
                        
                        $features[] = $feature;
                    }
                    
                    return $features;
                    
                    break;
                default:
                    throw new CartoserverException("Selection mode not implemented : " . get_class($shape));
            }
        }
    }
    
    /**
     * Select shapes with given id's from database
     * @param string list of ids
     * @return array array of features 
     */
    protected function selectFeaturesById($featuresIds) {
        $shapes = split(',', $featuresIds);
        $features = array();
        foreach ($shapes as $shape) {
            $sql = sprintf("SELECT oid, *, astext(%s) as %s FROM %s.%s " .
                           "WHERE %s = '%s'",
                               $this->geomColumn,
                               $this->geomColumn,
                               $this->editSchema,
                               $this->editTable,
                               $this->idField,
                               $shape
                    );
            $this->log->debug($sql);
            $db = $this->getDb($this->layer);
            
            $db->setFetchMode(DB_FETCHMODE_ASSOC);
            $r = $db->getAll($sql);
            
            Utils::checkDbError($r);
            
            if (count($r) == 0) {
                return array();
            }
            
            $feature = new Feature();
                       
            $feature->WKTString = (isset ($r[0][$this->geomColumn]))?
                $r[0][$this->geomColumn] : $this->getGeometryType();
            $feature->id = $r[0][$this->idField];
            foreach ($r[0] as $attribute=>$value) {
                // get all but geometry column as attribute
                if ($attribute != $this->geomColumn) {
                    $feature->attributes[$attribute] = Encoder::encode($value, 'config');
                }
            }
            
            $features[] = $feature;
        }
        return $features;
        
    }

    /**
     * Check if layer's metadata are valid
     * @return
     */
    protected function checkLayerMetadata() {
        /* retrieve the metadata values */
        $this->editTable = $this->getMetadataValueString($this->layer,'edit_table');
        if (!isset($this->editTable)) throw new CartoserverException("edit_table not set in mapfile");
        
        $editTableArray = split("\.", $this->editTable);
        if (count($editTableArray) > 1) {
            $this->editSchema = $editTableArray[0];
            $this->editTable = $editTableArray[1];
        } else {
            $this->editSchema = "public";
            $this->editTable = $this->editTable;
        }
        
        $this->idField = explode("|",$this->getMetadataValueString($this->layer,'id_attribute_string'));
        $this->idField = $this->idField[0];
        if (!isset($this->idField)) throw new CartoserverException("Id field for edition not set in mapfile");
                
        return true;
    }
    
    /**
     * Handles shapes insert in the database
     * @param EditRequest
     * @return EditResult
     */
    public function handlePreDrawing($requ) {
        if (!$requ->layer) return; // shape, or layer, or object id not defined
        
        $this->layer = $requ->layer;
        $this->checkLayerMetadata();
        
        // retrieve selected edit layer metadata
        $this->idAttribute = $this->serverContext
                                ->getIdAttribute($requ->layer);
        $this->attributes = $this->getAttributes($requ->layer);
        
        $this->geomColumn = $this->getGeomColumn();
        
        
        if (isset($requ->shapes) && $requ->shapes) {
            return;
        }
        
        if (isset($requ->features) && $requ->validateAll) {
            foreach ($requ->features as $feature) {
                if (isset($feature->operation)) {
                    switch ($feature->operation) {
                        case 'insert' :
                            $this->insertFeature($feature);
                            break;
                        case 'update' :
                            $this->updateFeature($feature);
                            break;
                        case 'delete' :
                            $this->deleteFeature($feature);
                            break;
                        case '':
                            break;
                    }
                }
            }
            $requ->features = array();
        }
    }
    
    /**
     * Handles shapes drawing
     * @param EditRequest
     * @return EditResult
     */
    public function handleDrawing($requ) {
        $result = new EditResult();
        
        if (!isset($requ->layer)) return $result;
        
        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $requ->layer);
        $result->shapeType = $this->getGeometryType();

        // newly selected objects
        if (isset($requ->shapes) && $requ->shapes) {
            $selectedFeatures = $this->selectFeaturesByShape($requ->shapes);
            if (count($selectedFeatures) != 0)
                foreach ($selectedFeatures as $feature)
                    $result->features[] = $feature;
        }
        
        // shapes with ids from given list (get parameter)
        if (isset($requ->featuresIds) && $requ->featuresIds) {
            $selectedFeatures = $this->selectFeaturesById($requ->featuresIds);
            if (count($selectedFeatures) != 0)
                foreach ($selectedFeatures as $feature)
                    $result->features[] = $feature;
        }
        
        if (isset($result->features)) {
            foreach ($result->features as $feature)
                $requ->features[$feature->id] = $feature;
        }

        $result->attributeNames = array_keys($this->attributes);
        
        $result->attributeTypes = array();
        foreach ($this->attributes as $attribute) {
            $result->attributeTypes[] = (isset ($attribute['type'])) ? $attribute['type'] : "";
        }
        
        return $result;
    }
}

?>
