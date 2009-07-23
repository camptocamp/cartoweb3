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

import java.io.File;
import java.io.IOException;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public abstract class BaseTilecacheReader extends StatsReader {
    private static final Pattern URL_PATTERN = Pattern.compile("http://([^/]+)/([^/]+)/(\\d{2})/(\\d{3})/(\\d{3})/(\\d{3})/(\\d{3})/(\\d{3})/(\\d{3}).*");

    public BaseTilecacheReader(File file, SideTables sideTables, boolean wantLayers, boolean skipErrors) throws IOException {
        super(file, sideTables, wantLayers, skipErrors);
    }

    protected BaseTilecacheReader(SideTables sideTables, boolean wantLayers, boolean skipErrors) {
        super(sideTables, wantLayers, skipErrors);
    }

    protected boolean parseUrl(String params, Map<String, String> fields) {

        Matcher matcher = URL_PATTERN.matcher(params);
        if (matcher.matches()) {

            fields.put("layer", matcher.group(2));
            fields.put("zoom", matcher.group(3));
            fields.put("x1", matcher.group(4));
            fields.put("x2", matcher.group(5));
            fields.put("x3", matcher.group(6));
            fields.put("y1", matcher.group(7));
            fields.put("y2", matcher.group(8));
            fields.put("y3", matcher.group(9));
            
            return true;
        } else {
            return false;
        }
    }
}
