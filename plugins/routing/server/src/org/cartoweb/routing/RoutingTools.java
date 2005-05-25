package org.cartoweb.routing;

import java.util.HashMap;

import org.geotools.feature.Feature;

/**
 * @author yves
 */
public class RoutingTools {

    public static RoutingNode initializeNode(Feature f, HashMap parameters) {
        
        return new RoutingNode("", "");
    }

    public static RoutingEdge initializeEdge(Feature f, HashMap parameters, boolean reverse) {
        
        return new RoutingEdge("", "", 0);
    }
}
