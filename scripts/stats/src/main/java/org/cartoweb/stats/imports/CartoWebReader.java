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

public class CartoWebReader extends StatsReader {
    private static final Pattern PATTERN = Pattern.compile("([^=]+)=\"([^\"]*)\";?");

    public CartoWebReader(File file, SideTables sideTables, boolean wantLayers, boolean skipErrors) throws IOException {
        super(file, sideTables, wantLayers, skipErrors);
    }

    /**
     * For tests only.
     */
    protected CartoWebReader(SideTables sideTables, boolean wantLayers, boolean skipErrors) {
        super(sideTables, wantLayers, skipErrors);
    }

    protected StatsRecord parse(String curLine) {
        Matcher matcher = PATTERN.matcher(curLine);
        if (matcher.find()) {
            Map<String, String> fields = new HashMap<String, String>();
            int prevEnd = 0;
            do {
                //check we didn't skip chars
                if (matcher.start() != prevEnd ||
                        (prevEnd != 0 && !curLine.substring(prevEnd - 1, prevEnd).equals(";"))) {
                    parseError("Invalid input line", curLine);
                    return null;
                }
                fields.put(matcher.group(1).toLowerCase(), matcher.group(2));
                prevEnd = matcher.end();
            } while (matcher.find());
            if (prevEnd != curLine.length()) {
                parseError("Invalid input line", curLine);
                return null;
            }

            try {
                return createRecord(fields);
            } catch (RuntimeException ex) {
                parseError("Line with error (" + ex.getClass().getSimpleName() + " - " + ex.getMessage() + ")", curLine);
                return null;
            }
        } else {
            parseError("Invalid input line", curLine);
            return null;
        }
    }

    private StatsRecord createRecord(Map<String, String> fields) {
        StatsRecord result = new StatsRecord();
        final int generalMapid = sideTables.generalMapId.get(convertGeneralMapId(fields.get("general.mapid")));

        result.setGeneralClientVersion(Integer.parseInt(fields.get("general.client_version")));
        result.setGeneralBrowserInfo(sideTables.browserInfo.get(fields.get("general.browser_info")));

        result.setExportpdfFormat(sideTables.exportPdfFormat.get(fields.get("exportpdf.format"), generalMapid));
        result.setLayersSwitchId(sideTables.layerSwitch.get(toLowerCase(fields.get("layers.switch_id")), generalMapid));
        result.setGeneralIp(fields.get("general.ip"));
        result.setGeneralMapid(generalMapid);
        final Integer height = getInt(fields, "images.mainmap.height");
        final Integer width = getInt(fields, "images.mainmap.width");
        result.setImagesMainmapHeight(height);
        result.setImagesMainmapWidth(width);
        if (height != null && width != null) {
            result.setImagesMainmapSize(sideTables.imagesMainmapSize.get(String.format("%d x %d", width, height), generalMapid));
        }
        result.setQueryResultsTableCount(fields.get("query.results_table_count"));
        result.setGeneralRequestId(fields.get("general.request_id"));
        result.setGeneralDirectAccess("1".equals(fields.get("general.direct_access")));
        result.setGeneralSecurityUser(sideTables.user.get(toLowerCase(fields.get("general.security_user")), generalMapid));
        result.setGeneralCacheId(fields.get("general.cache_id"));
        result.setGeneralElapsedTime(getFloat(fields, "general.elapsed_time"));
        result.setGeneralExportPlugin(sideTables.exportPlugin.get(fields.get("general.export_plugin")));
        result.setGeneralUa(sideTables.ua.get(fields.get("general.ua")));
        result.setQueryResultsCount(getInt(fields, "query.results_count"));
        result.setGeneralCacheHit(fields.get("general.cache_hit"));

        String layers = fields.get("layers.visible_layers");
        if (layers == null) {
            layers = fields.get("layers.layers");
        }
        fillLayers(result, layers, generalMapid);

        result.setLocationScale(getFloat(fields, "location.scale"));
        result.setGeneralSessid(sideTables.session.get(fields.get("general.sessid")));
        result.setGeneralTime(getTimestamp(fields));

        final Integer exportPdf = getInt(fields, "exportpdf.resolution");
        result.setExportpdfResolution(sideTables.exportPdfRes.get(exportPdf != null ? Integer.toString(exportPdf) : null, generalMapid));

        final String bbox = fields.get("location.bbox");
        fillBbox(result, bbox);
        return result;
    }

    private String convertGeneralMapId(String value) {
        if (value != null) {
            return value.substring(0, value.indexOf(".")).toLowerCase();
        } else {
            return null;
        }
    }

    private Timestamp getTimestamp(Map<String, String> fields) {
        final String val = fields.get("general.time");
        if (val != null) {
            return new Timestamp(Long.parseLong(val) * 1000L);
        } else {
            return null;
        }
    }
}