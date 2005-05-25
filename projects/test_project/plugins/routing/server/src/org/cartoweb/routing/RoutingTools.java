package org.cartoweb.routing;

import java.util.HashMap;
import java.util.Vector;

import org.geotools.feature.Feature;

import com.vividsolutions.jts.geom.MultiPoint;
import com.vividsolutions.jts.geom.Point;
import com.vividsolutions.jts.geom.Coordinate;
import com.vividsolutions.jts.geom.LineString;
import com.vividsolutions.jts.geom.MultiLineString;

/**
 * @author yves
 */
public class RoutingTools {

    private static double computeWeight(Feature f, HashMap parameters, boolean reverse) {

        LineString ls = (LineString) ((MultiLineString)f.getDefaultGeometry()).getGeometryN(0);
        double weight = ls.getLength();
        
        String type = "";
        if (reverse) {
            type = f.getAttribute("TYPE").toString();
        } else {
            type = f.getAttribute("TYPE_REV").toString();
        }
        
        if (type.equals("noentry")) {
            weight += 1000000;
        }
        if (type.equals("highway") &&
            parameters.containsKey("options") &&
            parameters.get("options").equals("0")) {
            // fastest, use highways if possible
            weight *= 0.1;
        }
        return weight;
    }

    public static RoutingNode initializeNode(Feature f, HashMap parameters) {

        String key = f.getAttribute("NODEID").toString();
        Coordinate c = ((Point)((MultiPoint)f.getDefaultGeometry()).getGeometryN(0)).getCoordinate();        
        
        RoutingNode node = new RoutingNode(key, key);
        node.setAttribute("x", Double.toString(c.x));
        node.setAttribute("y", Double.toString(c.y));

        return node;
    }

    public static RoutingEdge initializeEdge(Feature f, HashMap parameters, boolean reverse) {
        
        String keyA = f.getAttribute("NODE1").toString();
        String keyB = f.getAttribute("NODE2").toString();
        
        double weight = computeWeight(f, parameters, reverse);
        RoutingEdge edge = new RoutingEdge(keyA, keyB, weight);
        
        return edge;
    }
}
