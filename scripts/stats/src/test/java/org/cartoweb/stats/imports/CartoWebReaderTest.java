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

public class CartoWebReaderTest extends BaseTestCase {
    public CartoWebReaderTest(String name) {
        super(name);
    }

    public void testSimple() {
        SideTables sideTables = new SideTables("test");
        StatsReader reader = new CartoWebReader(sideTables, true);
        StatsRecord record = reader.parse("general.client_version=\"1\";general.mapid=\"Sitn.Sitn\";general.time=\"1192657761\";general.ua=\"Mozilla/4.0 (compatible; Win32; WinHttp.WinHttpRequest.5)\";general.ip=\"148.196.4.153\";general.sessid=\"ejfmo4v0d8l2djcfrp11ib0194\";general.direct_access=\"1\";general.cache_hit=\"37c8ad8d460de21a0cf80bd01f29374e\";layers.client_version=\"0\";layers.visible_layers=\"communes_Interrogation,cantons_interrogation\";location.bbox=\"567675,204354.17,575612.49,209645.83\";location.scale=\"50000\";images.mainmap.width=\"600\";images.mainmap.height=\"400\"");

        assertEquals(Integer.valueOf(1), record.getGeneralClientVersion());
        final int mapId = record.getGeneralMapid();
        assertEquals("sitn", sideTables.generalMapId.getDescr(mapId));
        assertEquals(new Timestamp(1192657761L * 1000), record.getGeneralTime());
        assertEquals("Mozilla/4.0 (compatible; Win32; WinHttp.WinHttpRequest.5)", sideTables.ua.getDescr(record.getGeneralUa()));
        assertEquals("148.196.4.153", record.getGeneralIp());
        assertEquals("ejfmo4v0d8l2djcfrp11ib0194", sideTables.session.getDescr(record.getGeneralSessid()));
        assertEquals("37c8ad8d460de21a0cf80bd01f29374e", record.getGeneralCacheHit());
        assertEquals(null, record.getGeneralSecurityUser());
        assertEquals(Integer.valueOf(1), record.getGeneralClientVersion());

        assertEquals(567675.0, record.getBboxMinx());
        assertEquals(204354.17, record.getBboxMiny());
        assertEquals(575612.49, record.getBboxMaxx());
        assertEquals(209645.83, record.getBboxMaxy());

        assertEquals(50000.0F, record.getLocationScale());
        assertEquals(Integer.valueOf(600), record.getImagesMainmapWidth());
        assertEquals(Integer.valueOf(400), record.getImagesMainmapHeight());

        Integer layerCom = sideTables.layer.get("communes_interrogation", mapId);
        Integer layerCan = sideTables.layer.get("cantons_interrogation", mapId);
        assertEquals(2, record.getLayerArray().size());
        assertEquals(layerCom, record.getLayerArray().get(0));
        assertEquals(layerCan, record.getLayerArray().get(1));
    }
}