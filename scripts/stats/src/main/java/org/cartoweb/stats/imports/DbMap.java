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

import org.pvalsecc.jdbc.JdbcUtilities;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

public class DbMap extends BaseDbMap {
    private final Map<String, Info> values = new HashMap<String, Info>(100);

    public DbMap(String statsTableName, String columnName, boolean foreignKey) {
        super(statsTableName, columnName, foreignKey);
    }

    public void createStructure(Connection con) throws SQLException {
        JdbcUtilities.runDeleteQuery("creating table " + tableName,
                "CREATE TABLE " + tableName + " (id integer PRIMARY KEY NOT NULL, descr text)", con, null);

    }

    public Integer get(String text) {
        if (text == null) {
            return null;
        }

        Info result = values.get(text);
        if (result == null) {
            result = new Info(++sequence, false);
            values.put(text, result);
        }
        return result.id;
    }

    public void load(Connection con) throws SQLException {
        JdbcUtilities.runSelectQuery("reading from " + tableName,
                "SELECT id, descr FROM " + tableName, con,
                new JdbcUtilities.SelectTask() {
                    public void setupStatement(PreparedStatement stmt) throws SQLException {
                    }

                    public void run(ResultSet rs) throws SQLException {
                        while (rs.next()) {
                            final int id = rs.getInt(1);
                            values.put(rs.getString(2), new Info(id, true));
                            sequence = Math.max(id + 1, sequence);
                        }
                    }
                });
    }

    public void save(Connection con) throws SQLException {
        JdbcUtilities.runInsertQuery("inserting for " + tableName, "INSERT INTO " + tableName + " (id, descr) VALUES (?,?)", con, values.keySet().iterator(), 500, new JdbcUtilities.InsertTask<String>() {
            public boolean marshall(PreparedStatement stmt, String item) throws SQLException {
                final Info info = values.get(item);
                if (!info.inDB) {
                    stmt.setInt(1, info.id);
                    stmt.setString(2, item);
                    return true;
                } else {
                    return false;
                }
            }
        });
    }

    /**
     * Slow, use only for tests.
     */
    public String getDescr(int id) {
        for (Map.Entry<String, Info> cur : values.entrySet()) {
            if (cur.getValue().id == id) {
                return cur.getKey();
            }
        }
        return null;
    }
}
