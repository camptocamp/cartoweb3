<?php
/**
 * Routing plugin, server
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
 * Server routing plugin. This class must be subclassed by plugins wishing to 
 * perform routing
 * 
 * @package Plugins
 */
class ServerRouting extends ClientResponderAdapter {

    /**
     * @var Logger
     */
    private $log;

    /**
     * The current graph object to be rendered
     * @var Object
     */    
    private $graph;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Plugins may extent this method to convert stop identifiers sent by 
     * client to nodes identifiers usable by the computePath method
     * @param string Client node identifier
     * @return object Internal node identifier (used by computePath()) or null
     *         if the nodeId can't be converted (path will not be computated)
     */
    protected function convertNodeId($nodeId) {
        return $nodeId;
    }
    
    /**
     * Plugins should override this method to serialize the graph model object
     * into a string, sent back to the client. The default implementation returns
     * the object directly.
     * @param object graph model object
     * @return string The string representation of the object
     */
    protected function serializeGraph($graph) {
        return $graph;   
    }
    
    /**
     * Plugins should override this method to unserialize the graph as a string 
     * sent by the client to the graph model object.
     * @param string the graph in string representation
     * @return object The unserialized graph
     */
    protected function unserializeGraph($serializedGraph) {
        return $serializedGraph;   
    }

    
    /**
     * Computes the shortest path between to nodes
     * @param string The source node identifier
     * @param string The target node identifier
     * @param array array of key-value parameters
     * @return RoutingResult a partial routing result object, containing the 
     *  unserialized graph model object, and the list of steps. 
     */
    protected function computePath($node1, $node2, $parameters) {

        throw new CartoserverException('computePath method needs to be implemented');
    }
    
    /**
     * Implementors may override this method to add routing attributes to the
     * routing result once computated by {@link ServerRouting::computePath()}
     * @param RoutingResult
     * @return RoutingResult
     */
    protected function addRoutingResultAttributes(RoutingResult $routingResult) {
    
        return $routingResult;   
    }
    
    /**
     * When computing a path made of several stops, one graph is generated for
     * each (startNode, endNode) pairs of the path. This method should merge
     * two graphs into another one.
     * @param object The first graph to merge
     * @param object The second graph to merge
     * @return object The merged graph object
     */
    protected function mergeGraph($oldGraph, $newGraph) {

        return $newGraph;
    }
    
    /**
     * Internal merging of the routing results
     */
    private function mergeRoutingResultGraph(RoutingResult $routingResult, 
                                               RoutingResult $newRoutingResult) {

        $routingResult->steps = array_merge($routingResult->steps, $newRoutingResult->steps);
        $routingResult->graph = $this->mergeGraph($routingResult->graph, $newRoutingResult->graph);
        return $routingResult;
    }
    
    /**
     * Compute a RoutingResult for a path described as an array of stops. This
     * will call {@link ServerRouting::computePath()} repeatedly for each
     * (source node, target node) pairs of the path, and merge the resulting
     * RoutingResults togeter. 
     */
    protected function computeRoutingResult($stops, $parameters) {

            $convertedStsop = array();
            foreach ($stops as $stop) {
                $newStop = $this->convertNodeId($stop);
                $this->log->debug("Converted stop $stop = $newStop");
                if (is_null($newStop)) {
                    $this->getServerContext()->addMessage($this, 'nodeIdNotFound',
                             I18nNoop::gt("Node identifier $stop not found"));        
                    return null;
                }
                $convertedStops[] = $newStop;
            }
            $this->log->debug("Converted stops:");
            $this->log->debug($convertedStops);
            $routingResult = null;

            for ($i = 0; $i < count($convertedStops) - 1; $i++) {
                $newRoutingResult = $this->computePath($convertedStops[$i],
                                                    $convertedStops[$i+1],
                                                    $parameters);

                if (!is_null($routingResult))
                    $routingResult = $this->mergeRoutingResult($routingResult, $newRoutingResult);
                else
                    $routingResult = $newRoutingResult;
            }
            return $routingResult;        
    }
    
    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) {

        $routingResult = new RoutingResult();
        
        if (count($requ->stops) > 0) {

            $routingResult = $this->computeRoutingResult($requ->stops, $requ->parameters);

            if (!is_null($routingResult)) {
                $this->graph = $routingResult->graph;
                $this->initializeGraph($routingResult->graph);

                $routingResult = $this->addRoutingResultAttributes($routingResult);

                $routingResult->graph = $this->serializeGraph($routingResult->graph);
            }
            
        } else {
            if (!is_null($requ->graph)) {
                $this->graph = $this->unserializeGraph($requ->graph);
            }
        }

        return $routingResult;
    }

    /**
     * Plugins may override this method to do special treatment the very first
     * time a routing computation is done. This will only called the first 
     * time the graph is drawn.
     * @param object graph model object
     */
    protected function initializeGraph($graph) {

    }

    /**
     * Plugins should override this method to draw the computated graph object
     * on the map.
     * @param graph model object
     */
    protected function drawGraph($graph) {

    }

    /**
     * Result is set in initializeRequest but Outline must be called 
     * in handlePreDrawing 
     * @see ClientResponder::handlePreDrawing()
     */    
    public function handlePreDrawing($requ) {
        
        if (!is_null($this->graph)) {
            $this->drawGraph($this->graph);
        }
    }
}

/**
 * ServerRouting implementation, which uses the GeoTools module for shortest
 * path computation
 */
class ServerGeotoolsRouting extends ServerRouting {

    /**
     * Converts Java array of steps into PHP
     * @param Java array
     * @return array
     */
    private function convertSteps($javaSteps) {
        
        $steps = array();
        if (is_null($javaSteps) || $javaSteps->size() == 0) {
            return $steps;
        }
        $javaIterator1 = $javaSteps->iterator();
        while ($javaIterator1->hasNext()) {
            
            $javaStep = $javaIterator1->next();
            $step = null;
            $attributes = array();
            if ($javaStep->getClass()->getName()
                == 'org.cartoweb.routing.RoutingNode') {
                $step = new Node();
                $attribute = new Attribute();
                $attribute->set('id', $javaStep->getId());
                $attributes = array($attribute);                
            } else if ($javaStep->getClass()->getName()
                       == 'org.cartoweb.routing.RoutingEdge') {
                $step = new Edge();
                $attribute = new Attribute();
                $attribute->set('id1', $javaStep->getNodeAId());
                $attributes[] = $attribute;                
                $attribute = new Attribute();
                $attribute->set('id2', $javaStep->getNodeBId());
                $attributes[] = $attribute;                
            }
            
            $javaIterator2 = $javaStep->getAttributes()->entrySet()->iterator();
            while ($javaIterator2->hasNext()) {
                $javaEntry = $javaIterator2->next();
                $attribute = new Attribute();
                $attribute->set($javaEntry->getKey(), $javaEntry->getValue());
                $attributes[] = $attribute;
            } 
            $step->attributes = $attributes;
            $steps[] = $step;
        }
        return $steps;
    }

    /**
     * @see RoutingModuleInterface::computePath()
     */
    protected function computePath($node1, $node2, $parameters) {

        $config = $this->getConfig();
        $projectHandler = $config->projectHandler;
        $projectRouting = "";
        if ($projectHandler->isProjectFile("plugins/routing/server/routing.jar")) {
            $projectRouting = CARTOWEB_HOME
                              . $projectHandler->getPath("plugins/routing/server/routing.jar")
                              . ";";
        }

        java_set_library_path($projectRouting . CARTOWEB_HOME . "plugins/routing/server/routing.jar;" .
                              CARTOWEB_HOME . "include/geotools/module/gt2-main.jar;" .
                              CARTOWEB_HOME . "include/geotools/plugin/shapefile/gt2-shapefile.jar;" .
                              CARTOWEB_HOME . "include/geotools/shared/JTS-1.4.jar;" .
                              CARTOWEB_HOME . "include/geotools/shared/geoapi-20050118.jar;" .
                              CARTOWEB_HOME . "include/geotools/extension/graph/gt2-graph.jar");

        try { 
            $javaParameters = new Java("java.util.HashMap");
            foreach ($parameters as $parameter) {
                $javaParameters->put($parameter->id, $parameter->value);
            }
            $external = new Java('org.cartoweb.routing.ExternalRoutingModule');         
            $javaPath = $external->computePath($node1,
                                               $node2,
                                               $javaParameters,
                                               $config->routingDataType,
                                               "file://" . CARTOWEB_HOME . $config->routingNodesSource,
                                               "file://" . CARTOWEB_HOME . $config->routingEdgesSource);

            $steps = $this->convertSteps($javaPath);
            $routingResult = new RoutingResult();
            $routingResult->steps = $steps;
            $routingResult->graph = $steps;
            return $routingResult;                                             
        } catch (Exception $e) {
            throw new CartoclientException($e->getMessage());
        }    
    }    
}

/**
 * Graph model object used in the Postgres Implementation of ServerRouting
 */
class PostgresGraph {
    /**
     * Array of strings of node identifier of the path
     * @var array
     */
    public $stops;

    /**
     * Key-value array of parameters
     * @var array
     */
    public $parameters;

    /**
     * Identifier of the results, for database retrieval of the path
     * @var int
     */
    public $resultsIds;     
}

/**
 * ServerRouting implementation, which uses Postgres dijsktra module for shortest
 * path computation
 */
class ServerPostgresRouting extends ServerRouting {

    /**
     * @var Logger
     */
    private $log;

    /**
     * Database object
     * @var DB
     */
    private $db;

    /** 
     * Constructor
     */
    public function __construct() {

        parent::__construct();
        require_once('DB.php');                
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Wrapper for PEAR::isError, which throws an exception in case of failure
     * @param object 
     */
    protected function checkDbError($db) {
        if (PEAR::isError($db)) {
            $msg = sprintf('Message: %s  Userinfo: %s', $db->getMessage(), $db->userinfo);
            throw new CartoserverException($msg);
        }
    }

    /**
     * Returns the Pear::DB database connection.
     * @return DB
     */    
    protected function getDb() {
        if ($this->db)
            return $this->db;
        
        if (!$this->getConfig()->routingDsn)
            throw new CartoserverException('Missing routingDsn parameter');
        $dsn = $this->getConfig()->routingDsn;
        
        $this->db = DB::connect($dsn);
        $this->checkDbError($this->db);
        return $this->db;        
    }

    /**
     * The default implementation will use the table format of the Pgdijkstra
     *  package, for converting node identifiers to internal ones. Plugins
     *  should override this method if not using the default Pgdijkstra format 
     * @see ServerRouting::convertNodeId()
     */
    protected function convertNodeId($nodeId) {
    
        $db = $this->getDb();
        
        $table = $this->getConfig()->postgresRoutingTable;
        $id = $db->getOne("SELECT id FROM {$table}_vertices WHERE geom_id ILIKE '$nodeId'");
        $this->checkDbError($db);
        
        return $id;
    }

    /**
     * @see RoutingModule::mergeGraph()
     */
    protected function mergeGraph($oldGraph, $newGraph) {
        // FIXME: not tested
        array_pop($oldGraph->stops);
        $oldGraph->stops[] = $newGraph->stops[1]; 
        array_push($oldGraph->resultsIds, $newGraph->resultIds[0]); 
        return $oldGraph;
    }

    /**
     * @return string The name of the table containing the path geometries
     */
    protected final function getRoutingResultsTable() {

        if ($this->getConfig()->postgresRoutingResultsTable)
            return $this->getConfig()->postgresRoutingResultsTable;
        return 'routing_results';
    }

    /**
     * @return string The name of the main table for pgdijkstra module
     */
    protected final function getRoutingTable() {

        if ($this->getConfig()->postgresRoutingTable)
            return $this->getConfig()->postgresRoutingTable;
        throw new CartoserverException("postgresRoutingTable parameter missing");            
        return '';
    }

    /**
     * Deletes the geometries on the results table which are too old 
     */
    private function deleteOldResults() {

        $routingResultsTable = $this->getRoutingResultsTable();

        $maxLifetime = $this->getConfig()->postgresRoutingResultsMaxLifetime;
        if (!$maxLifetime) {
            $maxLifetime = 60 * 60 * 24; // by default, one day    
        }
        
        $tod = gettimeofday();
        $stampLimit = $tod['sec'] - $maxLifetime;

        $r = $this->getDb()->query("DELETE FROM $routingResultsTable WHERE timestamp < $stampLimit");
        $this->checkDbError($r);        
    }

    /**
     * This methods performs a database query which will return the shortest
     * path (see Pgdijkstra documentation for the API).
     * Plugins may override this method to perform specific queries.
     * @param string The table containing the graph
     * @param string The source node identifier
     * @param string The target node identifier
     * @param array array of key-value parameters
     * @return DB_result A Pear::DB result object, which is the result of the 
     *  shortest_path function. It may contain additional columns, which will
     *  be used in the {@link getNodes()} function.
     */
    protected function shortestPathQuery($node1, $node2, $parameters) {

        $db = $this->getDb();
        $table = $this->getRoutingTable();
        $prepared = $db->prepare("SELECT edge_id, x(the_geom), y(the_geom) FROM shortest_path('SELECT id, source, target, cost FROM {$table}_edges', ?, ?, false, false) left join {$table}_vertices on vertex_id = id;");
        $this->checkDbError($prepared);        
        return $db->execute($prepared, array($node1, $node2));        
    }

    /**
     * This method iterates over the results returned by 
     * {@link ServerPostgresRouting::shortestPathQuery()} and fills the table
     * containing the path geometries.
     * @param string the table containing the geomeetries
     * @param DB_result the database results returned by shortestPathQuery()
     * @param int The identifier to use when storing the path geometries in the 
     * results table
     * @param int The timestamp to store in the table
     * @return array An array of Nodes
     */
    protected function getNodes(DB_result $result, $resultsId, $timestamp) {
    
        $nodes = array();
        $table = $this->getRoutingTable();
        $routingResultsTable = $this->getRoutingResultsTable();
        $db = $this->getDb();

        while ($result->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $node = new Node();
            
            $node->attributes = array();
            $attribute = new Attribute();
            $attribute->set('edge_id', $row['edge_id']);
            $node->attributes[] = $attribute;                
            $attribute = new Attribute();
            $attribute->set('x', $row['x']);
            $node->attributes[] = $attribute;                
            $attribute = new Attribute();
            $attribute->set('y', $row['y']);
            $node->attributes[] = $attribute;                

            // Warning: make sure index on edge_id is present
            
            $routingResultsTable = $this->getRoutingResultsTable();

            $edgeId = $row['edge_id'];
            $r = $db->query("INSERT INTO $routingResultsTable SELECT $resultsId, " .
                    "$timestamp, gid, the_geom FROM $table WHERE edge_id = $edgeId");
            $this->checkDbError($r);
            
            $nodes[] = $node;
        }
        
        return $nodes;    
    }

    /**
     * @see RoutingModuleInterface::computePath()
     */
    protected function computePath($node1, $node2, $parameters) {

        $db = $this->getDb();
        $this->checkDbError($db);
                
        $result = $this->shortestPathQuery($node1, $node2, $parameters);
        $this->checkDbError($result);

        $routingResult = new RoutingResult();

        $tod = gettimeofday();
        $resultsId = $db->nextId($this->getRoutingResultsTable());
        $timestamp = $tod['sec'];        
        $routingResult->steps = $this->getNodes($result, $resultsId, $timestamp);

        $this->deleteOldResults();
        
        $routingResult->graph = new PostgresGraph();
        $routingResult->graph->stops = array($node1, $node2);
        $routingResult->graph->resultsIds = array($resultsId);
         
        return $routingResult;
    }

    /**
     * @see ServerRouting::serializeGraph()
     */
    protected function serializeGraph($graph) {
        return serialize($graph);   
    }

    /**
     * @see ServerRouting::unserializeGraph()
     */
    protected function unserializeGraph($serializedGraph) {

        $graph = unserialize($serializedGraph);
        $db = $this->getDb();
        
        for ($i = 0; $i < count($graph->stops) - 1; $i++) {
            $resultsId = $graph->resultsIds[$i];

            $routingResultsTable = $this->getRoutingResultsTable();            
            $count = $db->getOne("SELECT count(results_id) FROM $routingResultsTable WHERE results_id = $resultsId");
            $this->checkDbError($count);
            if ($count == 0) {
                // compute path again
                $routingResult = $this->computePath($graph->stops[$i], $graph->stops[$i + 1], $graph->parameters);                                
                $graph->resultsIds[$i] = $routingResult->graph->resultsIds[0];
            }
        }
        return $graph;
    }

    /**
     * Recenter the map on the path identified by the given results id. This
     * method can be called by plugins for recentering.
     * WARNING: It must be used from the {@link ServerRouting::initializeGraph()} 
     * if wanted, otherwise the recentering will not be done. 
     * @param array An array of integer identifiers of the results to be used 
     * for recentering
     */
    protected function recenter($resultsIds) {

        $locationRequest = new LocationRequest();
        $locationRequest->bboxLocationRequest = new BboxLocationRequest();
        $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;

        $db = $this->getDb();    
        
        $routingResultsTable = $this->getRoutingResultsTable();

        $ids = '(' . implode(',', $resultsIds) . ')';

        $sql = "SELECT xmin(extent), ymin(extent), xmax(extent), ymax(extent) " .
        "FROM (SELECT extent( the_geom) FROM $routingResultsTable " .
        "WHERE results_id IN $ids) AS extent";

        $result = $this->getDb()->getAll($sql);
        $this->checkDbError($result);

        if (count($result) != 1) {
            throw new CartoserverException("Can't find bbox of results_id $resultsId");   
        }

        $extent = $result[0];
        $bbox = new Bbox($extent[0], $extent[1], $extent[2], $extent[3]);
            
        // Margin
        $percent = 5;
        $width = $bbox->getWidth();
        if ($width == 0) $width = 100;
        $height = $bbox->getHeight();
        if ($height == 0) $height = 100;

        $bbox->setFromBbox($bbox->minx - $width * $percent / 100,
                           $bbox->miny - $height * $percent / 100,
                           $bbox->maxx + $width * $percent / 100,
                           $bbox->maxy + $height * $percent / 100);
        $locationRequest->bboxLocationRequest->bbox = $bbox;

        $pluginManager = $this->serverContext->getPluginManager();
        $locationPlugin = $pluginManager->getPlugin('location');
        $pluginManager->callPluginImplementing($locationPlugin,
                                               'ClientResponder',
                                               'setRequest',
                                               $locationRequest);    
    }

    /**
     * @see ServerRouting::initializeGraph()
     */
    protected function initializeGraph($graph) {
        
        $this->recenter($graph->resultsIds);
    }

    /**
     * @see ServerRouting::drawGraph()
     */        
    protected function drawGraph($graph) {

        $routingResultsLayer = $this->getConfig()->postgresRoutingResultsLayer;
        if (!$routingResultsLayer)
            $routingResultsLayer = 'routing_results';

        $msMapObj = $this->getServerContext()->getMapObj();
        $msLayer = $msMapObj->getLayerByName($routingResultsLayer);
        if (!$msLayer)
            throw new CartoserverException("Routing results layer " .
                    "$routingResultsLayer not found");
        $msLayer->set('status', MS_ON);

        $routingResultsTable = $this->getRoutingResultsTable();

        $ids = '(' . implode(',', $graph->resultsIds) . ')';
        $routingResultsAttributes = $this->getConfig()->postgresRoutingResultsAttributes;
        if (strlen($routingResultsAttributes) > 0)
            $routingResultsAttributes = ", $routingResultsAttributes"; 
             
        $layerData = "the_geom from (SELECT the_geom, gid $routingResultsAttributes from $routingResultsTable where results_id IN $ids) as foo using unique gid using srid=-1";
        $msLayer->set('data', $layerData);
    }
}

?>