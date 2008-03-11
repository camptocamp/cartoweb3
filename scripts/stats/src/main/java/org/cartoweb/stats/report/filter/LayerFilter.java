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

package org.cartoweb.stats.report.filter;

import org.pvalsecc.jdbc.JdbcUtilities;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;
import java.util.Set;

public class LayerFilter implements Filter {
    private final Set<String> ids;
    private final boolean or;
    private boolean ok = false;
    private String config = "";


    public LayerFilter(Connection con, String tableName, String value) throws SQLException {
        or = true;
        this.ids = new HashSet<String>();
        String[] values = value.split(",\\s*");
        for (int i = 0; i < values.length; ++i) {
            String cur = values[i];
            addLayers(con, tableName, cur);
            if (i > 0) {
                config += ",";
            }
            config += cur;
        }
    }

    private void addLayers(Connection con, String tableName, final String cur) throws SQLException {
        StringBuilder query = new StringBuilder();
        query.append("SELECT id FROM ").append(tableName).append("_layer WHERE descr LIKE ?");
        int nbBefore = ids.size();
        JdbcUtilities.runSelectQuery("Reading layers matching " + cur, query.toString(), con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setString(1, cur.replace('*', '%'));
            }

            public void run(ResultSet rs) throws SQLException {
                while (rs.next()) {
                    ids.add(Integer.toString(rs.getInt(1)));
                }
            }
        });
        if (nbBefore == ids.size()) {
            throw new RuntimeException("No layer found matching '" + cur + "'");
        }
    }

    public String getSelectWhereClause() {
        return null;
    }

    public int setSelectWhereParams(PreparedStatement stmt, int pos) throws SQLException {
        return pos;
    }

    public String getSQLFields() {
        return "layers";
    }

    public int updateFromResultSet(ResultSet rs, int pos) throws SQLException {
        ok = true;
        final String value = rs.getString(++pos);
        if (rs.wasNull()) {
            ok = false;
        } else {
            String[] layers = value.split(",");
            if (or) {
                ok = false;
                for (int i = 0; !ok && i < layers.length; ++i) {
                    String layer = layers[i];
                    if (ids.contains(layer)) {
                        ok = true;
                    }
                }
            } else {
                ok = true;
                for (int i = 0; ok && i < layers.length; ++i) {
                    String layer = layers[i];
                    if (!ids.contains(layer)) {
                        ok = false;
                    }
                }
            }
        }
        return pos;
    }

    public boolean softCheck() {
        return ok;
    }

    public void getIniFile(StringBuilder result) {
        result.append("filters.layer=").append(config).append("\n");
    }
}
