package org.cartoweb.routing;

import java.util.HashMap;

import org.geotools.graph.structure.basic.BasicDirectedEdge;
import org.geotools.graph.structure.DirectedNode;
import com.vividsolutions.jts.geom.LineSegment;

/**
 * @author yves
 */
public class RoutingEdge extends BasicDirectedEdge {

    private String m_nodeAId;
    private String m_nodeBId;
    
    private double m_weight;
    private HashMap m_attributes;
    
    public RoutingEdge(String nodeAId, String nodeBId, double weight) {
        super(null, null);
        m_nodeAId = nodeAId;
        m_nodeBId = nodeBId;        
        m_weight = weight;
        m_attributes = new HashMap();
    }
    
    public RoutingEdge(RoutingNode nodeA, RoutingNode nodeB, double weight) {
        super(nodeA, nodeB);
        m_nodeAId = nodeA.getId();
        m_nodeBId = nodeB.getId();
        m_weight = weight;
        m_attributes = new HashMap();
    }
    
    public String getNodeAId() {
        return m_nodeAId;
    }
    
    public String getNodeBId() {
        return m_nodeBId;
    }
    
    public double getWeight() {
        return m_weight;
    }
    
    public void setAttributes(HashMap attributes) {
        m_attributes = attributes;
    }
    
    public HashMap getAttributes() {
        return m_attributes;
    }
    
    public void setAttribute(String key, Object value) {
        m_attributes.put(key, value);
    }
    
    public Object getAttribute(String key) {
        return m_attributes.get(key);
    }
}
