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

import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.NoSuchElementException;

public abstract class StatsReader implements Iterator<StatsRecord> {
    private static final Log LOGGER = LogFactory.getLog(StatsReader.class);
    private final BufferedReader reader;

    protected final SideTables sideTables;
    private final boolean wantLayers;

    private String curLine = null;

    private boolean hasNextCalled = false;
    protected final File file;
    private final boolean skipErrors;

    public StatsReader(File file, SideTables sideTables, boolean wantLayers, boolean skipErrors) throws FileNotFoundException {
        this.file = file;
        this.sideTables = sideTables;
        this.wantLayers = wantLayers;
        this.skipErrors = skipErrors;
        reader = new BufferedReader(new FileReader(file));
    }

    /**
     * For tests only.
     */
    protected StatsReader(SideTables sideTables, boolean wantLayers, boolean skipErrors) {
        this.file = null;
        this.sideTables = sideTables;
        this.wantLayers = wantLayers;
        this.skipErrors = skipErrors;
        reader = null;
    }

    public boolean hasNext() {
        if (!hasNextCalled) {
            try {
                curLine = reader.readLine();
            } catch (IOException e) {
                throw new RuntimeException(e);
            }
            hasNextCalled = true;
        }
        return curLine != null;
    }

    public StatsRecord next() {
        StatsRecord result;
        if (!hasNextCalled) {
            if (!hasNext()) {
                throw new NoSuchElementException();
            }
        }

        result = parse(curLine);
        hasNextCalled = false;
        curLine = null;
        return result;
    }

    protected abstract StatsRecord parse(String curLine);

    public void remove() {
        throw new UnsupportedOperationException();
    }

    protected void fillLayers(StatsRecord result, String value, int generalMapid) {
        String layersTxt = null;
        List<Integer> layerList = null;
        value = toLowerCase(value);
        if (value != null && value.length() != 0) {
            StringBuilder result11 = new StringBuilder();
            int curBegin = 0;
            if (wantLayers) {
                layerList = new ArrayList<Integer>(10);
            }
            while (curBegin < value.length()) {
                int curEnd = value.indexOf(',', curBegin);
                if (curEnd < 0) {
                    curEnd = value.length();
                }
                if (result11.length() > 0) {
                    result11.append(',');
                }
                final int id = sideTables.layer.get(value.substring(curBegin, curEnd), generalMapid);
                result11.append(id);
                curBegin = curEnd + 1;
                if (wantLayers) {
                    layerList.add(id);
                }
            }
            layersTxt = result11.toString();
        }

        result.setLayers(layersTxt);
        result.setLayerArray(layerList);
    }

    protected String toLowerCase(String value) {
        return value != null ? value.toLowerCase() : null;
    }

    protected void fillBbox(StatsRecord result, String bbox) {
        final double bboxMinX, bboxMinY, bboxMaxX, bboxMaxY;
        if (bbox != null && bbox.length() > 0) {
            String[] arr = bbox.split(",");
            bboxMinX = Double.parseDouble(arr[0]);
            bboxMinY = Double.parseDouble(arr[1]);
            bboxMaxX = Double.parseDouble(arr[2]);
            bboxMaxY = Double.parseDouble(arr[3]);
        } else {
            bboxMinX = 0;
            bboxMinY = 0;
            bboxMaxX = 0;
            bboxMaxY = 0;
        }
        result.setBboxMinx(bboxMinX);
        result.setBboxMiny(bboxMinY);
        result.setBboxMaxx(bboxMaxX);
        result.setBboxMaxy(bboxMaxY);
    }

    protected Float getFloat(Map<String, String> fields, String name) {
        final String val = fields.get(name);
        return val != null ? Float.parseFloat(val) : null;
    }

    protected Integer getInt(Map<String, String> fields, String name) {
        final String val = fields.get(name);
        return val != null ? Integer.parseInt(val) : null;
    }

    protected void parseError(String message, String curLine) {
        if (skipErrors) {
            LOGGER.warn(message + " in [" + file + "]:");
            LOGGER.warn("  " + curLine);
        } else {
            throw new RuntimeException(message + " in [" + file + "]: [" + curLine + "]");
        }
    }
}
