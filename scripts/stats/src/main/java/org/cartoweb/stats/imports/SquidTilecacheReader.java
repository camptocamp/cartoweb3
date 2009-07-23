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
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class SquidTilecacheReader extends BaseTilecacheReader {
    private static final Pattern LINE_PATTERN = Pattern.compile("([^ ]+)[ ]+([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+)   ([^ ]+) ([^ ]+) ([^ ]+) \\[([^\\]]+)\\] \"GET ([^\"]+) HTTP/[\\d\\.]+\" ([^ ]+) ([^ ]+) \"([^\"]+)\" \"([^\"]+)\" ([^ ]+)");

    private final BaseDateTimeParser dateTimeParser;
    private final TilecacheExtractor tilecacheExtractor;

    public SquidTilecacheReader(File file, SideTables sideTables, boolean wantLayers, TilecacheExtractor tilecacheExtractor, boolean skipErrors) throws IOException {
        super(file, sideTables, wantLayers, skipErrors);
        dateTimeParser = new WmsDateTimeParser();
        this.tilecacheExtractor = tilecacheExtractor;
    }

    protected SquidTilecacheReader(SideTables sideTables, boolean wantLayers, TilecacheExtractor tilecacheExtractor, boolean skipErrors) {
        super(sideTables, wantLayers, skipErrors);
        dateTimeParser = new WmsDateTimeParser();
        this.tilecacheExtractor = tilecacheExtractor;
    }
    
    protected StatsRecord parse(String curLine) {

        Matcher matcher = LINE_PATTERN.matcher(curLine);
        if (matcher.matches()) {
            String params = matcher.group(10);
            try {
                Map<String, String> fields = new HashMap<String, String>();

                if (!parseUrl(params, fields)) {
                    parseError("Invalid input line", curLine);
                    return null;
                }
                String mapId = tilecacheExtractor.extractMapId(matcher.group(13));

                return createRecord(matcher.group(6), matcher.group(7), matcher.group(9), mapId, fields);
            } catch (RuntimeException ex) {
                parseError("Line with error (" + ex.getClass().getSimpleName() + " - " + ex.getMessage() + ")", curLine);
                return null;
            }
        } else {
            parseError("Invalid input line", curLine);
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
        fillLayers(result, fields.get("layer"), generalMapId);
        fillBbox(result, tilecacheExtractor.extractBbox(fields));
        result.setImagesMainmapWidth(tilecacheExtractor.extractWidth(fields));
        result.setImagesMainmapHeight(tilecacheExtractor.extractHeight(fields));
        result.setLocationScale(tilecacheExtractor.extractScale(fields));
        return result;
    }
}
