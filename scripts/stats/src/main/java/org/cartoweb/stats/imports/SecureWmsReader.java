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
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class SecureWmsReader extends BaseWmsReader {
    private static final Pattern LINE_PATTERN = Pattern.compile("^\\d+ - (.*)$");
    private static final Pattern PARAM_PATTERN = Pattern.compile("([^=]+)=([^;]*);?");

    public SecureWmsReader(File file, SideTables sideTables, boolean wantLayers, boolean skipErrors) throws IOException {
        super(file, sideTables, wantLayers, skipErrors);
    }

    /**
     * For tests only.
     */
    protected SecureWmsReader(SideTables sideTables, boolean wantLayers, boolean skipErrors) {
        super(sideTables, wantLayers, skipErrors);
    }

    protected StatsRecord parse(String curLine) {
        if (curLine.toLowerCase().contains("request=getmap")) {
            Matcher matcher = LINE_PATTERN.matcher(curLine);
            if (matcher.matches()) {
                String params = matcher.group(1);

                try {
                    Map<String, String> fields = parseParams(params);
                    if (fields == null) {
                        parseError("Invalid input line", curLine);
                        return null;
                    }
                    return createRecord(fields);
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

    private Map<String, String> parseParams(String params) {
        Matcher matcher = PARAM_PATTERN.matcher(params);
        if (matcher.find()) {
            if (matcher.start() != 0) {
                return null;
            }
            Map<String, String> fields = new HashMap<String, String>();
            int prevEnd;
            do {
                final String key = decode(matcher.group(1));
                final String value = decode(matcher.group(2));
                if (key.equals("request")) {
                    if (!parseUrl(value, fields)) {
                        return null;
                    }
                } else {
                    fields.put(key, value);
                }
                prevEnd = matcher.end();
            } while (matcher.find());
            if (prevEnd != params.length()) {
                return null;
            }

            return fields;
        } else {
            return null;
        }
    }

    private StatsRecord createRecord(Map<String, String> fields) {
        StatsRecord result = new StatsRecord();
        final String[] mapIdPath = fields.get("requestURI").split("/");
        String mapId = mapIdPath[mapIdPath.length - 1];
        int generalMapId = sideTables.generalMapId.get(toLowerCase(mapId));
        result.setGeneralMapid(generalMapId);
        result.setGeneralIp(fields.get("remote_host"));
        result.setGeneralSecurityUser(sideTables.user.get(fields.get("user_principal"), generalMapId));
        result.setGeneralTime(new Timestamp(Long.parseLong(fields.get("date_unix"))));
        fillLayers(result, fields.get("layers"), generalMapId);
        fillBbox(result, fields.get("bbox"));
        result.setImagesMainmapWidth(getFloat(fields, "width") != null ? Math.round(getFloat(fields, "width")) : null);
        result.setImagesMainmapHeight(getFloat(fields, "height") != null ? Math.round(getFloat(fields, "height")) : null);
        result.setLocationScale(getScale(result));

        return result;
    }
}