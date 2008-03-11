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

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;

import java.io.File;
import java.io.FileNotFoundException;
import java.sql.Timestamp;
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class CartoWebReader extends StatsReader {
    private static final Log LOGGER = LogFactory.getLog(CartoWebReader.class);
    private static final Pattern PATTERN = Pattern.compile("([^=]+)=\"([^\"]*)\";?");

    public CartoWebReader(File file, SideTables sideTables, boolean wantLayers) throws FileNotFoundException {
        super(file, sideTables, wantLayers);
    }

    /**
     * For tests only.
     */
    protected CartoWebReader(SideTables sideTables, boolean wantLayers) {
        super(sideTables, wantLayers);
    }

    protected StatsRecord parse(String curLine) {
        Matcher matcher = PATTERN.matcher(curLine);
        if (matcher.find()) {
            Map<String, String> fields = new HashMap<String, String>();
            int prevEnd;
            do {
                fields.put(matcher.group(1).toLowerCase(), matcher.group(2));
                prevEnd = matcher.end();
            } while (matcher.find());
            if (prevEnd != curLine.length()) {
                throw new RuntimeException("Invalid input line in [" + file + "]: [" + curLine + "]");
            }

            try {
                return createRecord(fields);
            } catch (RuntimeException ex) {
                LOGGER.error("Line with error in [" + file + "]: [" + curLine + "]");
                throw ex;
            }
        } else {
            throw new RuntimeException("Invalid input line in [" + file + "]: [" + curLine + "]");
        }
    }

    private StatsRecord createRecord(Map<String, String> fields) {
        StatsRecord result = new StatsRecord();
        final int generalMapid = sideTables.generalMapId.get(convertGeneralMapId(fields.get("general.mapid")));

        result.setGeneralClientVersion(Integer.parseInt(fields.get("general.client_version")));
        result.setGeneralBrowserInfo(sideTables.browserInfo.get(fields.get("general.browser_info")));

        result.setExportpdfFormat(sideTables.exportPdfFormat.get(fields.get("exportpdf.format"), generalMapid));
        result.setLayersSwitchId(sideTables.layerSwitch.get(fields.get("layers.switch_id"), generalMapid));
        result.setGeneralIp(fields.get("general.ip"));
        result.setGeneralMapid(generalMapid);

        final Integer height = getInt(fields, "images.mainmap.height");
        final Integer width = getInt(fields, "images.mainmap.width");
        result.setImagesMainmapHeight(height);
        result.setImagesMainmapWidth(width);
        if (height != null && width != null) {
            sideTables.mapSize.get(height + "x" + width, generalMapid);
        }

        result.setQueryResultsTableCount(fields.get("query.results_table_count"));
        result.setGeneralRequestId(fields.get("general.request_id"));
        result.setGeneralDirectAccess("1".equals(fields.get("general.direct_access")));
        result.setGeneralSecurityUser(sideTables.user.get(fields.get("general.security_user"), generalMapid));
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

        final Integer pdfRes = getInt(fields, "exportpdf.resolution");
        result.setExportpdfResolution(pdfRes);
        if (pdfRes != null) {
            sideTables.exportPdfRes.get(Integer.toString(pdfRes), generalMapid);
        }

        result.setGeneralTime(getTimestamp(fields));
        final String bbox = fields.get("location.bbox");
        fillBbox(result, bbox);
        return result;
    }

    private String convertGeneralMapId(String value) {
        return value.substring(0, value.indexOf("."));
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