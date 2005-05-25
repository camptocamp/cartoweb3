package org.cartoweb.routing;

import java.util.HashMap;

import org.geotools.graph.structure.basic.BasicDirectedNode;
import com.vividsolutions.jts.geom.Coordinate;

/**
 * @author yves
 */
public class RoutingNode extends BasicDirectedNode {

    private String m_id;
    private String m_stringId;
    private Coordinate m_coord;
    private int m_level;
    private HashMap m_attributes;
    
    public RoutingNode(String id, String stringId) {
      super();
      m_id = id;
      m_stringId = stringId;
      m_attributes = new HashMap();
    }
    
    public String getId() {
        return m_id;
    }

    public String getStringId() {
        return m_stringId;
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
