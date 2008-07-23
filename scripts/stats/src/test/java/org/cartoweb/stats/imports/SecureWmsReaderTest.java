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

public class SecureWmsReaderTest extends BaseTestCase {
    public SecureWmsReaderTest(String name) {
        super(name);
    }

    public void testSimple() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new SecureWmsReader(sideTables, true, false);
        StatsRecord record = reader.parse("1216798089606 - local_addr=127.0.0.1;date_unix=1216798089605;date_readable=Wed Jul 23 09:28:09 CEST 2008;local_name=localhost;request=BBOX=-2750560.0,-936638.9999999995,3583870.0,4673120.0&VENDOR_ONLINE_RESOURCE=http://localhost:8888/&SERVICE=WMS&HEIGHT=330&REQUEST=GetMap&STYLES=default&WIDTH=372&EXCEPTIONS=application/vnd.ogc.se_xml&TRANSPARENT=TRUE&VERSION=1.1.1&FORMAT=image/png&LAYERS=grid&SRS=EPSG:42304;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.2");

        assertEquals("127.0.0.2", record.getGeneralIp());
        Timestamp time = new Timestamp(1216798089605L);
        assertEquals(time, record.getGeneralTime());
        final int mapId = record.getGeneralMapid();
        assertEquals("gmap", sideTables.generalMapId.getDescr(mapId));
        assertEquals(sideTables.user.get("tomcat", mapId), record.getGeneralSecurityUser());

        assertEquals(-2750560.0, record.getBboxMinx());
        assertEquals(-936638.9999999995, record.getBboxMiny());
        assertEquals(3583870.0, record.getBboxMaxx());
        assertEquals(4673120.0, record.getBboxMaxy());

        assertEquals(Integer.valueOf(372), record.getImagesMainmapWidth());
        assertEquals(Integer.valueOf(330), record.getImagesMainmapHeight());

        Integer layerGrid = sideTables.layer.get("grid", mapId);
        assertEquals(1, record.getLayerArray().size());
        assertEquals(layerGrid, record.getLayerArray().get(0));
    }

    /**
     * Check that requests other than getMap are ignore
     */
    public void testOther() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new SecureWmsReader(sideTables, true, false);

        StatsRecord record = reader.parse("1216798064664 - local_addr=127.0.0.1;date_unix=1216798064664;date_readable=Wed Jul 23 09:27:44 CEST 2008;local_name=localhost;request=STYLE=&LAYER=road&VENDOR_ONLINE_RESOURCE=http://localhost:8888/&VERSION=1.1.1&FORMAT=image/png&SERVICE=WMS&HEIGHT=16&REQUEST=GetLegendGraphic&WIDTH=16;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.1");
        assertNull(record);

        record = reader.parse("1216798059696 - local_addr=127.0.0.1;date_unix=1216798059695;date_readable=Wed Jul 23 09:27:39 CEST 2008;local_name=localhost;request=VENDOR_ONLINE_RESOURCE=http://localhost:8888/&VERSION=1.1.1&SERVICE=WMS&REQUEST=GetCapabilities;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.1");
        assertNull(record);
    }

    public void testError() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new SecureWmsReader(sideTables, true, false);
        try {
            reader.parse("1216798089606 - local_addr=127.0.0.1;date_unix=1216798089605;date_readable=Wed Jul 23 09:28:09 CEST 2008;local_name=localhost;request=BBOX=-2750560.0,-936r638.9999999995,3583870.0,4673120.0&VENDOR_ONLINE_RESOURCE=http://localhost:8888/&SERVICE=WMS&HEIGHT=330&REQUEST=GetMap&STYLES=default&WIDTH=372&EXCEPTIONS=application/vnd.ogc.se_xml&TRANSPARENT=TRUE&VERSION=1.1.1&FORMAT=image/png&LAYERS=grid&SRS=EPSG:42304;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.2");
            fail("No exception raised");
        } catch (RuntimeException ex) {
            //expected
        }

        try {
            reader.parse("1216798089606 - local_addr=127.0.0.1;date_unix=121679r8089605;date_readable=Wed Jul 23 09:28:09 CEST 2008;local_name=localhost;request=BBOX=-2750560.0,-936638.9999999995,3583870.0,4673120.0&VENDOR_ONLINE_RESOURCE=http://localhost:8888/&SERVICE=WMS&HEIGHT=330&REQUEST=GetMap&STYLES=default&WIDTH=372&EXCEPTIONS=application/vnd.ogc.se_xml&TRANSPARENT=TRUE&VERSION=1.1.1&FORMAT=image/png&LAYERS=grid&SRS=EPSG:42304;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.2");
            fail("No exception raised");
        } catch (RuntimeException ex) {
            //expected
        }
    }

    public void testErrorIgnored() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new SecureWmsReader(sideTables, true, true);

        StatsRecord record = reader.parse("1216798089606 - local_addr=127.0.0.1;date_unix=1216798089605;date_readable=Wed Jul 23 09:28:09 CEST 2008;local_name=localhost;request=BBOX=-2750560.0,-936r638.9999999995,3583870.0,4673120.0&VENDOR_ONLINE_RESOURCE=http://localhost:8888/&SERVICE=WMS&HEIGHT=330&REQUEST=GetMap&STYLES=default&WIDTH=372&EXCEPTIONS=application/vnd.ogc.se_xml&TRANSPARENT=TRUE&VERSION=1.1.1&FORMAT=image/png&LAYERS=grid&SRS=EPSG:42304;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.2");
        assertNull(record);

        record = reader.parse("1216798089606 - local_addr=127.0.0.1;date_unix=121679r8089605;date_readable=Wed Jul 23 09:28:09 CEST 2008;local_name=localhost;request=BBOX=-2750560.0,-936638.9999999995,3583870.0,4673120.0&VENDOR_ONLINE_RESOURCE=http://localhost:8888/&SERVICE=WMS&HEIGHT=330&REQUEST=GetMap&STYLES=default&WIDTH=372&EXCEPTIONS=application/vnd.ogc.se_xml&TRANSPARENT=TRUE&VERSION=1.1.1&FORMAT=image/png&LAYERS=grid&SRS=EPSG:42304;requestURI=/owsproxyserver/gmap;user_principal=tomcat;local_port=8089;remote_host=127.0.0.2");
        assertNull(record);
    }
}