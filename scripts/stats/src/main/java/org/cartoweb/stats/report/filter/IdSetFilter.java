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
import java.util.ArrayList;
import java.util.List;

public class IdSetFilter extends SQLOnlyFilter {
    private final String fieldName;
    private final List<Integer> ids = new ArrayList<Integer>();
    private String config;

    public IdSetFilter(Connection con, String tableName, String fieldName, String value, String type) throws SQLException {
        this.fieldName = fieldName;
        this.config = type + "=";
        String[] values = value.split(",\\s*");
        for (int i = 0; i < values.length; ++i) {
            String cur = values[i];
            addFilter(con, tableName, cur);
            if (i > 0) {
                config += ",";
            }
            config += cur;
        }
    }

    private void addFilter(Connection con, String tableName, final String cur) throws SQLException {
        StringBuilder query = new StringBuilder();
        query.append("SELECT id FROM ").append(tableName).append("_").append(fieldName).append(" WHERE descr LIKE ?");
        int nbBefore = ids.size();
        JdbcUtilities.runSelectQuery("Reading layers matching " + cur, query.toString(), con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setString(1, cur.replace('*', '%'));
            }

            public void run(ResultSet rs) throws SQLException {
                while (rs.next()) {
                    ids.add(rs.getInt(1));
                }
            }
        });
        if (nbBefore == ids.size()) {
            throw new RuntimeException("No " + fieldName + " found matching '" + cur + "'");
        }
    }


    public String getSelectWhereClause() {
        final StringBuilder result = new StringBuilder();
        result.append(fieldName).append(" in (");
        for (int i = 0; i < ids.size(); i++) {
            Integer cur = ids.get(i);
            if (i > 0) {
                result.append(",");
            }
            result.append(cur);
        }
        result.append(")");
        return result.toString();
    }

    public int setSelectWhereParams(PreparedStatement stmt, int pos) throws SQLException {
        return pos;
    }

    public void getIniFile(StringBuilder result) {
        result.append("filters.").append(config).append("\n");
    }
}
