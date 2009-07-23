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

public abstract class BaseWmsReader extends StatsReader {
    private static final Pattern URL_PATTERN = Pattern.compile("([^=]+)=([^&]*)&?");

    private static final float METERS_PER_INCH = 0.0254f;

    private final Integer resolution;
    
    public BaseWmsReader(File file, SideTables sideTables, boolean wantLayers, Integer resolution, boolean skipErrors) throws IOException {
        super(file, sideTables, wantLayers, skipErrors);
        this.resolution = resolution;
    }

    protected BaseWmsReader(SideTables sideTables, boolean wantLayers, Integer resolution, boolean skipErrors) {
        super(sideTables, wantLayers, skipErrors);
        this.resolution = resolution;
    }

    protected boolean parseUrl(String params, Map<String, String> fields) {
        Matcher matcher = URL_PATTERN.matcher(params);
        if (matcher.find()) {
            if (matcher.start() != 0) {
                return false;
            }
            int prevEnd;
            do {
                fields.put(decode(matcher.group(1).toLowerCase()), decode(matcher.group(2)));
                prevEnd = matcher.end();
            } while (matcher.find());
            if (prevEnd != params.length()) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }

    protected Float getScale(StatsRecord result) {
        final double minx = result.getBboxMinx();
        final double maxx = result.getBboxMaxx();
        final Integer width = result.getImagesMainmapWidth();
        if (width != null && width != 0 && minx != 0 && maxx != 0 && minx != maxx) {
            return (float) ((maxx - minx) / (width / (this.resolution / METERS_PER_INCH)));
        } else {
            return null;
        }
    }
}
