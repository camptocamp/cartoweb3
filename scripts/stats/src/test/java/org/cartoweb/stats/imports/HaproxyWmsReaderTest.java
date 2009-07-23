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

import org.cartoweb.stats.BaseTestCase;

import java.sql.Timestamp;

public class HaproxyWmsReaderTest extends BaseTestCase {
    public HaproxyWmsReaderTest(String name) {
        super(name);
    }

    public void testSimple() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new HaproxyWmsReader(sideTables, true, 96, false);
        StatsRecord record = reader.parse("Jul 13 08:29:37 ip-10-226-51-210.eu-west-1.compute.internal haproxy[352]: 81.201.60.168:52680 [13/Jul/2009:08:29:33.368] default schweizmobil/ec2-79-125-56-149.eu-west-1.compute.amazonaws.com 0/0/0/699/3840 200 12361 - - --VN 129/129/127/12/0 0/0 \"GET /tilecache?FORMAT=image%2Fpng&LAYERS=suisse_smaller,to%3Dt%C3%A9&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&EXCEPTIONS=application%2Fvnd.ogc.se_inimage&SRS=EPSG%3A21781&BBOX=676000,222000,740000,286000&WIDTH=256&HEIGHT=256 HTTP/1.1\"");

        Timestamp time = createTimestamp(2, 2009, 6, 13, 8, 29, 33, 0);
        System.out.println(time.toString());
        assertEquals("81.201.60.168:52680", record.getGeneralIp());
        assertEquals(time, record.getGeneralTime());
        assertEquals(null, record.getGeneralSecurityUser());
        final int mapId = record.getGeneralMapid();
        assertEquals("schweizmobil", sideTables.generalMapId.getDescr(mapId));

        assertEquals(676000.0, record.getBboxMinx());
        assertEquals(222000.0, record.getBboxMiny());
        assertEquals(740000.0, record.getBboxMaxx());
        assertEquals(286000.0, record.getBboxMaxy());

        assertEquals(Integer.valueOf(256), record.getImagesMainmapWidth());
        assertEquals(Integer.valueOf(256), record.getImagesMainmapHeight());

        Integer layerCn100 = sideTables.layer.get("suisse_smaller", mapId);
        Integer layerToto = sideTables.layer.get("to=té", mapId);
        assertEquals(2, record.getLayerArray().size());
        assertEquals(layerCn100, record.getLayerArray().get(0));
        assertEquals(layerToto, record.getLayerArray().get(1));
    }

    public void testError() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, 96, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), false);
        try {
            reader.parse("Jul 13 08:29:37 ip-10-226-51-210.eu-west-1.compute.internal haproxy[352]: 81.201.60.168:52680 [13/Jul/2009:08:259:33.368] default schweizmobil/ec2-79-125-56-149.eu-west-1.compute.amazonaws.com 0/0/0/699/3840 200 12361 - - --VN 129/129/127/12/0 0/0 \"GET /tilecache?FORMAT=image%2Fpng&LAYERS=suisse_smaller,to%3Dt%C3%A9&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&EXCEPTIONS=application%2Fvnd.ogc.se_inimage&SRS=EPSG%3A21781&BBOX=676000,222000,740000,286000&WIDTH=256&HEIGHT=256 HTTP/1.1\"");
            fail("No exception raised");
        } catch (RuntimeException ex) {
            //expected
        }
    }

    public void testSkipError() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, 96, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), true);
        StatsRecord record = reader.parse("Jul 13 08:29:37 ip-10-226-51-210.eu-west-1.compute.internal haproxy[352]: 81.201.60.168:52680 [13/Jul/2009:08:296:33.368] default schweizmobil/ec2-79-125-56-149.eu-west-1.compute.amazonaws.com 0/0/0/699/3840 200 12361 - - --VN 129/129/127/12/0 0/0 \"GET /tilecache?FORMAT=image%2Fpng&LAYERS=suisse_smaller,to%3Dt%C3%A9&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&EXCEPTIONS=application%2Fvnd.ogc.se_inimage&SRS=EPSG%3A21781&BBOX=676000,222000,740000,286000&WIDTH=256&HEIGHT=256 HTTP/1.1\"");
        assertNull(record);

        record = reader.parse("Jul 13 08:29:37 ip-10-226-51-210.eu-west-1.compute.internal haproxy[352]: 81.201.60.168:52680 [13/Jul/2009:08:29:33.368] default schweizmobil/ec2-79-125-56-149.eu-west-1.compute.amazonaws.com 0/0/0/699/3840 200 12361 - - --VN 129/129/127/12/0 0/0 \"GET /tilecache?FORMAT=image%2Fpng&LAYERS=suisse_smaller,to%3Dt%C3%A9&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&EXCEPTIONS=application%2Fvnd.ogc.se_inimage&SRS=EPSG%3A21781&BBOX=676000,222000,7400e00,286000&WIDTH=256&HEIGHT=256 HTTP/1.1\"");
        assertNull(record);
    }
}
