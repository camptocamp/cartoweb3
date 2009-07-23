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
 * @copyright 2009 Camptocamp SA
 */

package org.cartoweb.stats.imports;

import org.cartoweb.stats.BaseTestCase;

import java.sql.Timestamp;
import java.util.Map;
import java.util.Set;
import java.util.HashMap;

public class SquidTilecacheReaderTest extends BaseTestCase {
    public SquidTilecacheReaderTest(String name) {
        super(name);
    }

    public void testSimple() {
        SideTables sideTables = new SideTables("test");
        HashMap<String, String> referers = new HashMap<String, String>();
        referers.put("my.referer", "toto");
        referers.put("my.other", "toto");
        HashMap<String, String> resolutions = new HashMap<String, String>();
        resolutions.put("1", "10000");
        resolutions.put("2", "1000");
        
        TilecacheExtractor tilecacheExtractor = new TilecacheExtractor(referers.entrySet(),
                                                                       20000, 30000, 40000, 60000,
                                                                       resolutions.entrySet(),
                                                                       256, 96);
        StatsReader reader = new SquidTilecacheReader(sideTables, true, tilecacheExtractor, false);
        StatsRecord record = reader.parse("Jul 14 10:05:35 ip-10-226-50-149.eu-west-1.compute.internal squid[793]:   79.125.49.224 - - [14/Jul/2009:10:05:35 +0200] \"GET http://tilecache.host/mylayer/01/000/000/137/000/000/132.png HTTP/1.1\" 200 26059 \"http://my.referer/?lang=de\" \"Jakarta Commons-HttpClient/3.1\" TCP_HIT:NONE");

        Timestamp time = createTimestamp(2, 2009, 6, 14, 10, 5, 35, 0);
        System.out.println(time.toString());
        assertEquals("79.125.49.224", record.getGeneralIp());
        assertEquals(time, record.getGeneralTime());
        assertEquals(null, record.getGeneralSecurityUser());
        final int mapId = record.getGeneralMapid();
        assertEquals("toto", sideTables.generalMapId.getDescr(mapId));

        assertEquals(3.5074E8,     record.getBboxMinx());
        assertEquals(3.37950016E8, record.getBboxMiny());
        assertEquals(3.533E8,      record.getBboxMaxx());
        assertEquals(3.40510016E8, record.getBboxMaxy());

        assertEquals(Integer.valueOf(256), record.getImagesMainmapWidth());
        assertEquals(Integer.valueOf(256), record.getImagesMainmapHeight());

        Integer layerMyLayer = sideTables.layer.get("mylayer", mapId);
        assertEquals(1, record.getLayerArray().size());
        assertEquals(layerMyLayer, record.getLayerArray().get(0));
    }
}
