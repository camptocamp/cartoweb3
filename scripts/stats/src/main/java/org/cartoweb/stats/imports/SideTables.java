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

import org.pvalsecc.log.Progress;

import java.sql.Connection;
import java.sql.SQLException;

/**
 * Helper container that manages all the side tables that are linked to the
 * main stats table.
 */
public class SideTables {
    final DbMap browserInfo;
    final DbMapWithProject exportPdfFormat;
    final DbMap generalMapId;
    final DbMapWithProject user;
    final DbMap exportPlugin;
    final DbMap ua;
    final DbMapWithProject layerSwitch;
    public final DbMapWithProject layer;
    final DbMap session;
    final DbMapWithProject mapSize;
    final DbMapWithProject exportPdfRes;

    /**
     * List of all the side tables used to categorize some fields.
     */
    private final BaseDbMap[] sideTables;

    public SideTables(String tableName) {
        browserInfo = new DbMap(tableName, "general_browser_info", true);
        exportPdfFormat = new DbMapWithProject(tableName, "exportpdf_format", true);
        generalMapId = new DbMap(tableName, "general_mapid", true);
        user = new DbMapWithProject(tableName, "general_security_user", true);
        exportPlugin = new DbMap(tableName, "general_export_plugin", true);
        ua = new DbMap(tableName, "general_ua", true);
        layerSwitch = new DbMapWithProject(tableName, "layers_switch_id", true);
        layer = new DbMapWithProject(tableName, "layer", false);
        session = new DbMap(tableName, "general_sessid", true);
        mapSize = new DbMapWithProject(tableName, "map_size", false);
        exportPdfRes = new DbMapWithProject(tableName, "exportpdf_resolution", false);
        sideTables = new BaseDbMap[]{
                browserInfo,
                exportPdfFormat,
                generalMapId,
                user,
                exportPlugin,
                ua,
                layerSwitch,
                layer,
                session,
                mapSize,
                exportPdfRes
        };
    }

    public void load(Connection con) throws SQLException {
        for (int i = 0; i < sideTables.length; ++i) {
            BaseDbMap sideTable = sideTables[i];
            sideTable.load(con);
        }
    }

    public void save(Connection con) throws SQLException {
        for (int i = 0; i < sideTables.length; ++i) {
            BaseDbMap sideTable = sideTables[i];
            sideTable.save(con);
        }
    }


    public void createStructure(Connection con) throws SQLException {
        for (int i = 0; i < sideTables.length; ++i) {
            BaseDbMap sideTable = sideTables[i];
            sideTable.createStructure(con);
        }
    }

    public void dropStructure(Connection con) throws SQLException {
        for (int i = sideTables.length - 1; i >= 0; i--) {
            BaseDbMap sideTable = sideTables[i];
            sideTable.dropStructure(con);
        }
    }

    public int size() {
        return sideTables.length;
    }

    public void createIndexes(Connection con, Progress progress, String tableName) throws SQLException {
        for (int i = 0; i < sideTables.length; ++i) {
            BaseDbMap sideTable = sideTables[i];
            sideTable.createIndexes(con, tableName);
            progress.update(7 + i);
        }
    }

    public void vacuum(Connection con) throws SQLException {
        for (int i = 0; i < sideTables.length; ++i) {
            BaseDbMap sideTable = sideTables[i];
            sideTable.vacuum(con);
        }
    }
}
