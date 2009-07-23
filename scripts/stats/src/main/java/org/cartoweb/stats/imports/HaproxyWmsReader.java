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
import java.sql.Timestamp;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class HaproxyWmsReader extends BaseWmsReader {
    private static final Pattern LINE_PATTERN = Pattern.compile("([^ ]+)[ ]+([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) \\[([^\\]]+)\\] ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) \"GET [^\"?]*\\?([^\"]+) HTTP/[\\d\\.]+\"");
    private static final Pattern MAPID_PATTERN = Pattern.compile("([^/]+)/.*");
    
    private final BaseDateTimeParser dateTimeParser;
    
    public HaproxyWmsReader(File file, SideTables sideTables, boolean wantLayers, Integer resolution, boolean skipErrors) throws IOException {
        super(file, sideTables, wantLayers, resolution, skipErrors);
        this.dateTimeParser = new HaproxyDateTimeParser();
    }

    protected HaproxyWmsReader(SideTables sideTables, boolean wantLayers, Integer resolution, boolean skipErrors) {
        super(sideTables, wantLayers, resolution, skipErrors);
        this.dateTimeParser = new HaproxyDateTimeParser();
    }

    protected StatsRecord parse(String curLine) {
        if (curLine.toLowerCase().contains("request=getmap")) {

            Matcher matcher = LINE_PATTERN.matcher(curLine);
            if (matcher.matches()) {
                String params = matcher.group(18);
                try {
                    Map<String, String> fields = new HashMap<String, String>();

                    if (!parseUrl(params, fields)) {
                        parseError("Invalid input line", curLine);
                        return null;
                    }

                    Matcher mapIdMatcher = MAPID_PATTERN.matcher(matcher.group(9));
                    String mapId = null;
                    if (mapIdMatcher.matches()) {
                        mapId = mapIdMatcher.group(1);
                    }

                    return createRecord(matcher.group(6), matcher.group(13), matcher.group(7), mapId, fields);
                } catch (RuntimeException ex) {
                    parseError("Line with error (" + ex.getClass().getSimpleName() + " - " + ex.getMessage() + ")", curLine);
                    return null;
                }
            } else {
                parseError("Invalid input line", curLine);
                return null;
            }
        } else {
            //not a WMS request
            return null;
        }
    }

    private StatsRecord createRecord(String address, String user, String time, String mapId, Map<String, String> fields) {
        StatsRecord result = new StatsRecord();
        int generalMapId = sideTables.generalMapId.get(toLowerCase(mapId));
        result.setGeneralMapid(generalMapId);
        result.setGeneralIp(address);
        result.setGeneralSecurityUser(sideTables.user.get(user.equals("-") ? null : toLowerCase(user), generalMapId));
        result.setGeneralTime(dateTimeParser.parseTime(time));
        fillLayers(result, fields.get("layers"), generalMapId);
        fillBbox(result, fields.get("bbox"));
        result.setImagesMainmapWidth(getFloat(fields, "width") != null ? Math.round(getFloat(fields, "width")) : null);
        result.setImagesMainmapHeight(getFloat(fields, "height") != null ? Math.round(getFloat(fields, "height")) : null);
        result.setLocationScale(getScale(result));

        //not in WMS "by design":
        //result.setImagesMainmapSize(sideTables.imagesMainmapSize.get(String.format("%d x %d", width, height), generalMapid));

        return result;
    }
}