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

import java.sql.Timestamp;
import java.util.HashMap;
import java.util.Map;

/**
 * Logic to extract the MapId out of a WMS log file.
 */
public abstract class BaseDateTimeParser {
    
    protected static final Map<String, Integer> MONTH;

    protected abstract Timestamp parseTime(String time);
    
    static {
        MONTH = new HashMap<String, Integer>();
        MONTH.put("jan", 0);
        MONTH.put("feb", 1);
        MONTH.put("mar", 2);
        MONTH.put("apr", 3);
        MONTH.put("may", 4);
        MONTH.put("jun", 5);
        MONTH.put("jul", 6);
        MONTH.put("aug", 7);
        MONTH.put("sep", 8);
        MONTH.put("oct", 9);
        MONTH.put("nov", 10);
        MONTH.put("dec", 11);
    }  
}
