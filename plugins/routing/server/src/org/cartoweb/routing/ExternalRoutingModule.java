package org.cartoweb.routing;

import java.net.URL;

import java.io.File;

import java.util.HashMap;
import java.util.Iterator;
import java.util.Vector;

import org.geotools.data.FeatureReader;
import org.geotools.data.FeatureResults;
import org.geotools.data.FeatureSource;
import org.geotools.data.FeatureWriter;
import org.geotools.data.Transaction;
import org.geotools.data.shapefile.ShapefileDataStore;
import org.geotools.feature.AttributeType;
import org.geotools.feature.AttributeTypeFactory;
import org.geotools.feature.FeatureType;
import org.geotools.feature.FeatureTypeFactory;
import org.geotools.feature.FeatureCollection;
import org.geotools.feature.FeatureCollections;
import org.geotools.feature.FeatureIterator;
import org.geotools.feature.Feature;
import org.geotools.graph.build.line.LineStringGraphGenerator;
import org.geotools.graph.structure.Graph;
import org.geotools.graph.structure.Edge;
import org.geotools.graph.structure.Node;
import org.geotools.graph.structure.basic.BasicDirectedNode;
import org.geotools.graph.structure.basic.BasicDirectedGraph;
import org.geotools.graph.path.DijkstraShortestPathFinder;
import org.geotools.graph.path.Path;
import org.geotools.graph.traverse.standard.DirectedDijkstraIterator;
import org.geotools.graph.traverse.standard.DijkstraIterator.EdgeWeighter;
import org.geotools.graph.traverse.GraphTraversal;

import com.vividsolutions.jts.geom.Coordinate;
import com.vividsolutions.jts.geom.GeometryFactory;
import com.vividsolutions.jts.geom.Point;
import com.vividsolutions.jts.geom.MultiPoint;
import com.vividsolutions.jts.geom.LineSegment;
import com.vividsolutions.jts.geom.LineString;
import com.vividsolutions.jts.geom.MultiLineString;

/**
 * @author yves
 */
public class ExternalRoutingModule {
        
    private static URL getResource(String path) {
        
        return ExternalRoutingModule.class.getClassLoader().getResource(path);
    }
    
    public Vector computePath(String node1,
                              String node2,
                              HashMap parameters,
                              String resourceType,
                              String nodesResource,
                              String edgesResource) {        

        try {
                                   
            HashMap nodes = new HashMap();
            Vector edges = new Vector();
            
            // TODO: Management of other source types

            // Nodes
//            URL shapeURL = getResource(nodesResource);
            URL shapeURL = new URL(nodesResource);
            ShapefileDataStore store = new ShapefileDataStore(shapeURL);

            String name = store.getTypeNames()[0];
            FeatureSource source = store.getFeatureSource(name);
            FeatureResults fsShape = source.getFeatures();

            RoutingNode nodeSrc = null;
            RoutingNode nodeDest = null;

            FeatureReader reader = fsShape.reader();
            Feature f = null;
            while (reader.hasNext()) {
                f = reader.next();

                // FIXME: It may be better to pass a more generic object than a Feature 
                RoutingNode node = RoutingTools.initializeNode(f, parameters);

                if (node.getStringId().equals(node1)) {
                    nodeSrc = node;
                }
                if (node.getStringId().equals(node2)) {
                    nodeDest = node;
                }
                nodes.put(node.getId(), node);
            }     
            reader.close();

            // Edges
            // shapeURL = getResource(edgesResource);
            shapeURL = new URL(edgesResource);
            store = new ShapefileDataStore(shapeURL);

            name = store.getTypeNames()[0];
            source = store.getFeatureSource(name);
            fsShape = source.getFeatures();
            
            reader = fsShape.reader();
            f = null;
            while (reader.hasNext()) {
                f = reader.next();

                // FIXME: It may be better to pass a more generic object than a Feature 

                RoutingNode nodeA, nodeB;
                RoutingEdge edge, newEdge;
                
                edge = RoutingTools.initializeEdge(f, parameters, false);
                if (edge.getWeight() < 100000) {
                    nodeA = (RoutingNode)nodes.get(edge.getNodeAId());
                    nodeB = (RoutingNode)nodes.get(edge.getNodeBId());

                    newEdge = new RoutingEdge(nodeA, nodeB, edge.getWeight());
                    newEdge.setAttributes(edge.getAttributes());
                    edges.add(newEdge);
                    nodeA.addOut(newEdge);
                    nodeB.addIn(newEdge);
                }

                edge = RoutingTools.initializeEdge(f, parameters, true);
                if (edge.getWeight() < 100000) {
                    nodeA = (RoutingNode)nodes.get(edge.getNodeAId());
                    nodeB = (RoutingNode)nodes.get(edge.getNodeBId());

                    newEdge = new RoutingEdge(nodeB, nodeA, edge.getWeight());
                    newEdge.setAttributes(edge.getAttributes());
                    edges.add(newEdge);
                    nodeA.addIn(newEdge);
                    nodeB.addOut(newEdge);
                }
            }          
            reader.close();

            BasicDirectedGraph network = new BasicDirectedGraph(nodes.values(), edges);
                      
            EdgeWeighter weighter = new 
            EdgeWeighter() {
               public double getWeight(Edge e) {
                 return(((RoutingEdge)e).getWeight());
             }
            };
            
            DirectedDijkstraIterator iter = new DirectedDijkstraIterator(weighter);
            iter.setSource(nodeSrc);

            DijkstraShortestPathFinder dijkstra =
                new DijkstraShortestPathFinder(network, iter);
            
            dijkstra.calculate();

            Path p = dijkstra.getPath(nodeDest);
            Vector result = new Vector();

            RoutingNode previous, node;
            previous = null;
            for (
                Iterator ritr = p.riterator();
                ritr.hasNext();
            ) {
                node = (RoutingNode)ritr.next();
                if (previous != null) {
                    // Adds edge in vector
                    result.add(node.getInEdge(previous));
                }
                result.add(node);
                previous = node;
            }

            return result;

        } catch (Exception e) {

            // TODO: Error management!
            // e.printStackTrace();
            return null;      
        }
    }
    
    public static void main(String[] args) {       

        ExternalRoutingModule obj = new ExternalRoutingModule();
        System.out.println(obj.computePath("INF0947", "BS128", new HashMap(),
                                           "shp", "tests/shapes/nodes2.shp",
                                           "tests/shapes/lines2.shp"));
        
    }
}
