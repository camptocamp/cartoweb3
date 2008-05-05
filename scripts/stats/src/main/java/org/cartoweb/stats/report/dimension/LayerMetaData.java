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

package org.cartoweb.stats.report.dimension;

import org.pvalsecc.jdbc.JdbcUtilities;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;

public class LayerMetaData implements DimensionMetaData<Layer> {

    public LayerMetaData() {
    }

    public String getStatsFieldNames() {
        return "layers";
    }

    public int getNbStatsFieldNames() {
        return 1;
    }

    public Layer[] buildFromStatResultSet(ResultSet rs, int pos) throws SQLException {
        final String txt = rs.getString(pos);
        if (rs.wasNull()) {
            return new Layer[]{new Layer(null)};
        } else {
            String[] subs = txt.split(",");
            Layer[] ids = new Layer[subs.length];
            for (int i = 0; i < subs.length; ++i) {
                ids[i] = new Layer(Integer.parseInt(subs[i]));
            }
            return ids;
        }
    }

    public Layer buildFromReportResultSet(ResultSet rs, int pos) throws SQLException {
        final int id = rs.getInt(pos);
        return new Layer(rs.wasNull() ? null : id);
    }

    public String getFieldDefinitions() {
        return "layer int";
    }

    public String getReportFieldNames() {
        return "layer";
    }

    public int getNbReportFields() {
        return 1;
    }

    public String getIniType() {
        return "layer";
    }

    public String getIniAdditionalParam() {
        return null;
    }

    public void createDbStructure(Connection con, String resultTableName, String statsTableName) throws SQLException {
        JdbcUtilities.runDeleteQuery("creating the foreign key for " + resultTableName + " and layer",
                "ALTER TABLE " + resultTableName + " ADD CONSTRAINT fk_" + resultTableName + "_layer" +
                        " FOREIGN KEY (layer) REFERENCES " + statsTableName + "_layer (id)", con, null);
    }
}
