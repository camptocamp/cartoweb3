<?php
/**
 * Query service plugin
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
 * @package CorePlugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 * @version $Id$
 */

/**
 * A service plugin to perform queries based on a set of selected id's
 * @package CorePlugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ServerMapquery extends ServerPlugin {

    /**
     * @var Logger
     */
    private $log;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log = LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Returns an array of query strings (for use in queryByAttributes), from
     * a set of id's and an attribute name. This query string can be used
     * in most case for layers.
     * @param string
     * @param string
     * @param array
     * @return array
     */
    protected function genericQueryString($idAttribute, $idType, $selectedIds) {
        return array('("[' . $idAttribute . ']" IN "' . implode(',', $selectedIds) . '")');
    }
    
    /**
     * Returns an array of query strings (for use in queryByAttributes), from
     * a set of id's and an attribute name. This query string is to be used
     * on database kind of layers.
     * @param string
     * @param string
     * @param array
     * @return array
     */
    protected function databaseQueryString($idAttribute, $idType, $selectedIds) {
        if (count($selectedIds) == 0) {
            return array('false');
        }

        if ($idType == 'string') {
            $queryString  = "'";
            $queryString .= implode("', '", $selectedIds);
            $queryString .= "'";
        } else {
            $queryString = implode(', ', $selectedIds);
        }

        return array("$idAttribute IN ($queryString)");
    }

    /**
     * Returns true if layer is linked to a database
     * @param msLayer
     * @return boolean
     */ 
    protected function isDatabaseLayer($msLayer) {
        switch ($msLayer->connectiontype) {
            case MS_POSTGIS:
            case MS_ORACLESPATIAL:
                return true;
        }
        return false;
    }

    /**
     * Extracts all shapes in the given msLayer, and returns them in an array
     * @param msLayer the layer from which to retrieve shapes
     * @return array the array of result shapes in the given layer
     */
    protected function extractResults($layerId, $mayIgnore) {
        
        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        $msLayer->open();
        $results = array();

        $numResults = $msLayer->getNumResults();

        $ignoreQueryThreshold = $this->getConfig()->ignoreQueryThreshold;
        if ($mayIgnore && is_numeric($ignoreQueryThreshold) && 
                                    $numResults > $ignoreQueryThreshold) {
            $this->getServerContext()->addMessage($this, 'queryIgnored', 
                sprintf(I18nNoop::gt(
                        "Query spanned too many objects on layer '%s', it was ignored."),
                        $layersInit->getLayerById($layerId)->label));
            return array();
        }

        $maxResults = $this->getConfig()->maxResults;
        if (is_numeric($maxResults) && $numResults > $maxResults) {
            $this->getServerContext()->addMessage($this, 'maxResultsHit', 
                sprintf(I18nNoop::gt(
                        "This query hit the maximum number of results on '%s', truncating results."),
                        $layersInit->getLayerById($layerId)->label));
            $numResults = $maxResults;
        }
        
        for ($i = 0; $i < $numResults; $i++) {
            $result = $msLayer->getResult($i);
            $shape = $msLayer->getShape($result->tileindex, $result->shapeindex);

            $results[] = $shape;
        }
        $msLayer->close();
        return $results;        
    }

    /**
     * Performs a query on a layer using attributes
     * @param ServerContext Server context
     * @param msLayer Layer to query
     * @param string The attribute name used by the query
     * @param string The query string to perform
     * @param boolean If true, a failure in the query is not fatal (empy array 
     *                returned)
     * @return array an array of shapes
     */
    protected function queryLayerByAttributes(ServerContext $serverContext,
                                              $layerId, $idAttribute, $query,
                                              $mayFail = false) { 
        $log = LoggerManager::getLogger(__METHOD__);
        
        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);

        // Saves extent and sets it to max extent.
        $savedExtent = clone($msMapObj->extent); 
        $maxExtent = $serverContext->getMaxExtent();
        $msMapObj->setExtent($maxExtent->minx, $maxExtent->miny, 
                             $maxExtent->maxx, $maxExtent->maxy);
        
        $log->debug("queryLayerByAttributes layer: $msLayer->name " .
                    "idAttribute: $idAttribute query: $query");
        // Layer has to be activated for query.
        $msLayer->set('status', MS_ON);
        $ret = @$msLayer->queryByAttributes($idAttribute, $query, MS_MULTIPLE);

        $this->log->debug('Query on layer ' . $msLayer->name . 
                          ": queryByAttributes($idAttribute, $query)");
                
        if ($ret == MS_FAILURE) {
            if ($mayFail) {
                $serverContext->resetMsErrors();
                // restore extent
                $msMapObj->setExtent($savedExtent->minx, $savedExtent->miny, 
                                     $savedExtent->maxx, $savedExtent->maxy);
                return array();
            }
            throw new CartoserverException('Attribute query returned no ' .
                          "results. Layer: $msLayer->name, idAttribute: " .
                          "$idAttribute, query: $query"); 
        }

        $serverContext->checkMsErrors();
        // restore extent
        $msMapObj->setExtent($savedExtent->minx, $savedExtent->miny, 
                             $savedExtent->maxx, $savedExtent->maxy);
        
        return $this->extractResults($layerId, false);
    }

    /**
     * Checks if layer's connection type is implemented
     * @param msLayer
     */
    protected function checkImplementedConnectionTypes($msLayer) {
    
        $implementedConnectionTypes = array(MS_SHAPEFILE, MS_TILED_SHAPEFILE,
            MS_OGR, MS_POSTGIS, MS_ORACLESPATIAL);
        
        if (in_array($msLayer->connectiontype, $implementedConnectionTypes))
            return;
            
        throw new CartoserverException('Layer to center on has an unsupported '
                                . "connection type: $msLayer->connectiontype");
    }
    
    /**
     * Performs a query based on a set of selected id's on a given layer
     * @param IdSelection The selection to use for the query. It contains a
     *                    layer name and a set of id's
     * @param boolean If true, a failure in the query is not fatal (empy array 
     *                returned)
     * @return array an array of shapes
     */
    public function queryByIdSelection(IdSelection $idSelection, 
                                       $mayFail = false) {

        $serverContext = $this->getServerContext();
        $layersInit = $serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($serverContext->getMapObj(), 
                                            $idSelection->layerId);
        
        $idAttribute = $idSelection->idAttribute;
        if (is_null($idAttribute)) {
            $idAttribute = $serverContext->getIdAttribute($idSelection->layerId);
        }
        if (is_null($idAttribute)) {
            throw new CartoserverException("can't find idAttribute for layer " 
                                           . $idSelection->layerId);
        }
        $idType = $idSelection->idType;
        if (is_null($idType)) {
            $idType = $serverContext->getIdAttributeType($idSelection->layerId);
        }

        self::checkImplementedConnectionTypes($msLayer);

        $ids = Encoder::decode($idSelection->selectedIds, 'config');

        // FIXME: can shapefiles support queryString for multiple id's ?
        //  if yes, then improve this handling. 

        if (self::isDatabaseLayer($msLayer))
            $queryString = self::databaseQueryString($idAttribute, $idType, $ids);
        else
            $queryString = self::genericQueryString($idAttribute, $idType, $ids);

        $results = array();
        foreach($queryString as $query) {
            $new_results = self::queryLayerByAttributes($serverContext,
                                                        $idSelection->layerId,
                                                        $idAttribute,
                                                        $query, $mayFail);
            $results = array_merge($results, $new_results);
        }
        return $results;
    }      

    /**
     * Performs a query based on a {@link Shape} object on a given layer.
     * @param string layerId
     * @param Shape geographic selection
     * @return array an array of shapes
     */
    public function queryByShape($layerId, Shape $shape) {

        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        
        // layer has to be activated for query
        $msLayer->set('status', MS_ON);
        
        if ($shape instanceof Point) {
            $msPoint = ms_newPointObj();
            $msPoint->setXY($shape->x, $shape->y);
            
            // no tolerance set by default, must be set in mapfile
            $ret = @$msLayer->queryByPoint($msPoint, MS_MULTIPLE, -1);
            
            $this->log->debug("Query on layer $layerId: " .
                              "queryByPoint(msPoint, MS_MULTIPLE, -1)");
        } elseif ($shape instanceof Bbox || $shape instanceOf Rectangle) {
            $msRect = ms_newRectObj();
            $msRect->setextent($shape->minx, $shape->miny, 
                               $shape->maxx, $shape->maxy);
        
            $ret = @$msLayer->queryByRect($msRect);
            
            $this->log->debug("Query on layer $layerId: queryByRect(msRect)");        
        } elseif ($shape instanceof Polygon) {
            $msShape = ms_newShapeObj(MS_SHAPE_POLYGON);
            $msLine = ms_newLineObj();
            foreach ($shape->points as $point) {
                $msLine->addXY($point->x, $point->y);
            }
            $msShape->add($msLine);

            $ret = @$msLayer->queryByShape($msShape);
            
            $this->log->debug("Query on layer $layerId: queryByShape(msShape)");        
        } elseif ($shape instanceof Circle) {
            // force mapscript to consider radius units as geographic
            if ($msLayer->toleranceunits == MS_PIXELS)
                $msLayer->toleranceunits = $msMapObj->units;

            $msPoint = ms_newPointObj();
                   
            $msPoint->setXY($shape->x, $shape->y);
            
            $ret = @$msLayer->queryByPoint($msPoint, MS_MULTIPLE, $shape->radius);
            
            $this->log->debug("Query on layer $layerId: queryByPoint(" .
                              "msPoint, MS_MULTIPLE, $shape->radius)");
        } else {
            $this->CartoserverException(sprintf("Query can't be done on %s " .
                                        'type selection', get_class($shape)));
        }
        
        $this->serverContext->resetMsErrors();
        
        if ($ret != MS_SUCCESS || 
            $msLayer->getNumResults() == 0) 
            return array();

        return $this->extractResults($layerId, true);
    }

    /**
     * Performs a query based on a MapServer ms_shape_obj object on a given layer.
     * @param string layerId
     * @param ms_shape_obj geographic selection
     * @return array an array of shapes
     */
    public function queryByMsShape($layerId, ms_shape_obj $shape) {
        
        $msMapObj = $this->serverContext->getMapObj();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        
        // layer has to be activated for query
        $msLayer->set('status', MS_ON);
        
        // useful to avoid selection differences when scale varies
        $msLayer->set('tolerance', 0); 
        
        $ret = @$msLayer->queryByShape($shape);

        $this->serverContext->resetMsErrors();
        
        if ($ret != MS_SUCCESS || 
            $msLayer->getNumResults() == 0) 
            return array();

        return $this->extractResults($layerId, true);
    }

    /**
     * Performs a query based on a bbox on a given layer
     * DEPRECATED: this method should no more be used.
     * Use {@link ServerMapquery::queryByShape()} instead
     * @param string layerId
     * @param Bbox
     * @return array an array of shapes
     */
    public function queryByBbox($layerId, Bbox $bbox) {
        return $this->queryByShape($layerId, $bbox);
    } 
}
