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

public class WmsReaderTest extends BaseTestCase {
    public WmsReaderTest(String name) {
        super(name);
    }

    public void testSimple() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), false);
        StatsRecord record = reader.parse("148.196.1.37 - - [13/May/2007:23:46:30 +0200] \"GET /OGC-sitn/wms?REQUEST=GetMap&VERSION=1.1.1&BBOX=558100,202900,564900,207300&Width=600&Height=388.235294118&Layers=cN100,to%3Dt%C3%A9&Format=JPEG HTTP/1.1\" 200 100667");

        Timestamp time = createTimestamp(2, 2007, 4, 13, 23, 46, 30, 0);
        assertEquals("148.196.1.37", record.getGeneralIp());
        assertEquals(time, record.getGeneralTime());
        assertEquals(null, record.getGeneralSecurityUser());
        final int mapId = record.getGeneralMapid();
        assertEquals("ogc-sitn", sideTables.generalMapId.getDescr(mapId));

        assertEquals(558100.0, record.getBboxMinx());
        assertEquals(202900.0, record.getBboxMiny());
        assertEquals(564900.0, record.getBboxMaxx());
        assertEquals(207300.0, record.getBboxMaxy());

        assertEquals(Integer.valueOf(600), record.getImagesMainmapWidth());
        assertEquals(Integer.valueOf(388), record.getImagesMainmapHeight());

        Integer layerCn100 = sideTables.layer.get("cn100", mapId);
        Integer layerToto = sideTables.layer.get("to=té", mapId);
        assertEquals(2, record.getLayerArray().size());
        assertEquals(layerCn100, record.getLayerArray().get(0));
        assertEquals(layerToto, record.getLayerArray().get(1));
    }

    public void testError() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), false);
        try {
            reader.parse("148.196.1.37 - - [13/May/2007:23:446:30 +0200] \"GET /OGC-sitn/wms?REQUEST=GetMap&VERSION=1.1.1&BBOX=558100,202900,564900,207300&Width=600&Height=388.235294118&Layers=cN100,to%3Dt%C3%A9&Format=JPEG HTTP/1.1\" 200 100667");
            fail("No exception raised");
        } catch (RuntimeException ex) {
            //expected
        }
    }

    public void testErrorEncoding() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), true);
        StatsRecord record = reader.parse("148.196.1.37 - - [19/Feb/2008:17:43:49 +0100] \"GET /ogc-sitn/wms?VERSION=1.1.1&REQUEST=GetMap&LAYERS=cn25&SRS=EPSG:9814&BBOX=550000,214600,550900,215800&WIDTH=300&HEIGHT=400%22%20width=%22100% HTTP/1.1\" 200 78562");
        assertNull(record);
    }

    public void testErrorSize() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), true);

        StatsRecord record = reader.parse("148.196.1.37 - - [13/May/2007:23:46:30 +0200] \"GET /OGC-sitn/wms?REQUEST=GetMap&VERSION=1.1.1&BBOX=558100,202900,564900,207300&Width=600000&Height=388.235294118&Layers=cN100,to%3Dt%C3%A9&Format=JPEG HTTP/1.1\" 200 100667");
        assertNotNull(record);
        assertEquals("width too big", record.isConsistent());

        record = reader.parse("148.196.1.37 - - [13/May/2007:23:46:30 +0200] \"GET /OGC-sitn/wms?REQUEST=GetMap&VERSION=1.1.1&BBOX=558100,202900,564900,207300&Width=600&Height=600000&Layers=cN100,to%3Dt%C3%A9&Format=JPEG HTTP/1.1\" 200 100667");
        assertNotNull(record);
        assertEquals("height too big", record.isConsistent());
    }

    public void testSkipError() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new WmsReader(sideTables, true, new RegExpMapIdExtractor("GET /([^/]+)/wms\\?"), true);
        StatsRecord record = reader.parse("148.196.1.37 - - [13/May/2007:23:446:30 +0200] \"GET /OGC-sitn/wms?REQUEST=GetMap&VERSION=1.1.1&BBOX=558100,202900,564900,207300&Width=600&Height=388.235294118&Layers=cN100,to%3Dt%C3%A9&Format=JPEG HTTP/1.1\" 200 100667");
        assertNull(record);

        record = reader.parse("148.196.1.37 - - [13/May/2007:23:46:30 +0200] \"GET /OGC-sitn/wms?REQUEST=GetMap&VERSION=1.1.1&BBOX=558100,202900,5649a00,207300&Width=600&Height=388.235294118&Layers=cN100,to%3Dt%C3%A9&Format=JPEG HTTP/1.1\" 200 100667");
        assertNull(record);
    }
}
