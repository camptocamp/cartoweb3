/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2008 Camptocamp SA
 */

package org.cartoweb.stats.imports;

import org.ini4j.Ini;

import java.io.FileInputStream;
import java.io.IOException;
import java.util.Map;
import java.util.Set;

/**
 * Logic to extract the MapId out of a WMS log file.
 */
public class TilecacheExtractor {
    private static final float METERS_PER_INCH = 0.0254f; 
    
    private final Set<Map.Entry<String, String>> referers;
    private final Integer xmin;
    private final Integer ymin;
    private final Integer xmax;
    private final Integer ymax;
    private final Set<Map.Entry<String, String>> resolutions;
    private final Integer size;
    private final Integer dpi;
    
    public TilecacheExtractor(String tilecacheConfig) throws IOException {
        FileInputStream file = new FileInputStream(tilecacheConfig);
        try {
            Ini ini = new Ini(file);
            Set<Map.Entry<String, String>> values;
            
            referers = ini.get("referers").entrySet();  
            
            values = ini.get("extent").entrySet();
            Integer x1 = null;
            Integer y1 = null;
            Integer x2 = null;
            Integer y2 = null;
            for (Map.Entry<String, String> entry : values) {
                if (entry.getKey().equals("xmin")) {
                    x1 = Integer.valueOf(entry.getValue());
                } else if (entry.getKey().equals("ymin")) {
                    y1 = Integer.valueOf(entry.getValue());                    
                } else if (entry.getKey().equals("xmax")) {
                    x2 = Integer.valueOf(entry.getValue());                    
                } else if (entry.getKey().equals("ymax")) {
                    y2 = Integer.valueOf(entry.getValue());                    
                }
            }
            xmin = x1;
            ymin = y1;
            xmax = x2;
            ymax = y2;
            
            resolutions = ini.get("resolutions").entrySet();   

            values = ini.get("tiles").entrySet();
            Integer s = null;
            Integer d = null;
            for (Map.Entry<String, String> entry : values) {
                if (entry.getKey().equals("size")) {
                    s = Integer.valueOf(entry.getValue());
                } else if (entry.getKey().equals("dpi")) {
                    d = Integer.valueOf(entry.getValue());                    
                }
            }
            size = s;
            dpi = d;
        } finally {
            file.close();
        }
    }    
    
    /**
     * For tests only
     */
    public TilecacheExtractor(Set<Map.Entry<String, String>> referers,
                              Integer xmin, Integer ymin, Integer xmax, Integer ymax,
                              Set<Map.Entry<String, String>> resolutions,
                              Integer size, Integer dpi) {
        this.referers = referers;
        this.xmin = xmin;
        this.ymin = ymin;
        this.xmax = xmax;
        this.ymax = ymax;
        this.resolutions = resolutions;
        this.size = size;
        this.dpi = dpi;
    }
        
    public String extractMapId(String referer) {
        
        for (Map.Entry<String, String> entry : referers) {
            if (referer.contains(entry.getKey())) {
                return entry.getValue();
            }
        }
        return "";
    }

    public String extractBbox(Map<String, String> urlFields) {

        Integer x = Integer.parseInt(urlFields.get("x1")) * 1000000 +
                    Integer.parseInt(urlFields.get("x2")) * 1000 +
                    Integer.parseInt(urlFields.get("x3"));
        Integer y = Integer.parseInt(urlFields.get("y1")) * 1000000 +
                    Integer.parseInt(urlFields.get("y2")) * 1000 +
                    Integer.parseInt(urlFields.get("y3"));
        Float res = extractResolution(urlFields);
        Float tilesize = res * size;
        Float x1 = x * tilesize + xmin;
        Float y1 = y * tilesize + ymin;
        Float x2 = x1 + tilesize;
        Float y2 = y1 + tilesize;
        return x1.toString() + "," + y1.toString() + "," + x2.toString() + "," + y2.toString();
    }
    
    public Integer extractWidth(Map<String, String> urlFields) {
        return size;
    }
    
    public Integer extractHeight(Map<String, String> urlFields) {
        return size;
    }

    private Float extractResolution(Map<String, String> urlFields) {
        
        for (Map.Entry<String, String> entry : resolutions) {
            if (Integer.parseInt(urlFields.get("zoom")) ==
                Integer.parseInt(entry.getKey())) {
                return Float.parseFloat(entry.getValue());
            }
        }
        return null;
    }
        
    public Float extractScale(Map<String, String> urlFields) {
        
        return extractResolution(urlFields) / METERS_PER_INCH * dpi;
    }
    
}
