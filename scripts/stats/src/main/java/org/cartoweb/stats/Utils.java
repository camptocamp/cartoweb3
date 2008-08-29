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

package org.cartoweb.stats;

import org.postgresql.PGConnection;
import org.pvalsecc.jdbc.JdbcUtilities;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public abstract class Utils {
    public static void dropAllReportTables(Connection con, String tableName) throws SQLException {
        if (doesTableExist(con, tableName + "_reports")) {
            final ReportDeleter deleter = new ReportDeleter(null);
            JdbcUtilities.runSelectQuery("reading the existing tables for every reports",
                    "SELECT tables FROM " + tableName + "_reports",
                    con, deleter);
            deleter.delete(con);
            dropTable(con, tableName + "_reports", true);
        }
    }

    public static void dropReportTables(Connection con, String statsTableName, final String name) throws SQLException {
        final ReportDeleter deleter = new ReportDeleter(name);
        try {
            JdbcUtilities.runSelectQuery("reading the existing tables for report [" + name + "]",
                    "SELECT tables FROM " + statsTableName + "_reports WHERE name=?",
                    con, deleter);

            JdbcUtilities.runDeleteQuery("Remove the report ["+name+"] information from the reports", "DELETE FROM " + statsTableName + "_reports WHERE name=?", con, new JdbcUtilities.DeleteTask() {
                public void setupStatement(PreparedStatement stmt) throws SQLException {
                    stmt.setString(1, name);
                }
            });
        } catch (SQLException ex) {
            //OK, no tables...
            return;
        }
        deleter.delete(con);
    }

    public static void dropTable(Connection con, String tableName, boolean ignoreMissing) throws SQLException {
        if (!ignoreMissing || doesTableExist(con, tableName)) {
            JdbcUtilities.runDeleteQuery("deleting table " + tableName, "DROP TABLE " + tableName, con, null);
        }
    }

    public static boolean doesTableExist(Connection con, final String tableName) throws SQLException {
        if (!(con instanceof PGConnection)) {
            throw new SQLException("Data base not supported: " + con.getClass());
        }
        return JdbcUtilities.countTable(con, "pg_tables WHERE tablename=?", new JdbcUtilities.DeleteTask() {
            public void setupStatement(PreparedStatement preparedStatement) throws SQLException {
                preparedStatement.setString(1, tableName);
            }
        }) > 0;

    }

    public static boolean doesSequenceExist(Connection con, final String tableName) throws SQLException {
        if (!(con instanceof PGConnection)) {
            throw new SQLException("Data base not supported: " + con.getClass());
        }
        return JdbcUtilities.countTable(con, "pg_class WHERE relname=? and relkind='S'", new JdbcUtilities.DeleteTask() {
            public void setupStatement(PreparedStatement preparedStatement) throws SQLException {
                preparedStatement.setString(1, tableName);
            }
        }) > 0;

    }

    public static void dropSequence(Connection con, String sequenceName, boolean ignoreMissing) throws SQLException {
        if (!(con instanceof PGConnection)) {
            throw new SQLException("Data base not supported: " + con.getClass());
        }
        if (!ignoreMissing || doesSequenceExist(con, sequenceName)) {
            JdbcUtilities.runDeleteQuery("deleting sequence " + sequenceName, "DROP SEQUENCE " + sequenceName, con, null);
        }
    }

    public static int getIndirectValue(Connection con, String tableName, String fieldName, final String descr) throws SQLException {
        final String query = "SELECT id FROM " + tableName + "_" + fieldName + " WHERE descr=?";
        final int[] result = new int[]{-1};
        JdbcUtilities.runSelectQuery("searching id for '" + descr + "'", query, con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setString(1, descr);
            }

            public void run(ResultSet rs) throws SQLException {
                if (rs.next()) {
                    result[0] = rs.getInt(1);
                }
            }
        });
        return result[0];
    }

    private static class ReportDeleter implements JdbcUtilities.SelectTask {
        private final String name;
        private final List<String> tablesToDrop = new ArrayList<String>();

        public ReportDeleter(String name) {
            this.name = name;
        }

        public void setupStatement(PreparedStatement stmt) throws SQLException {
            if (name != null) {
                stmt.setString(1, name);
            }
        }

        public void run(ResultSet rs) throws SQLException {
            while (rs.next()) {
                String tables = rs.getString(1);
                final String[] array = tables.split(",");
                for (int i = 0; i < array.length; ++i) {
                    String cur = array[i];
                    tablesToDrop.add(cur);
                }
            }
        }

        public void delete(Connection con) throws SQLException {
            for (int i = 0; i < tablesToDrop.size(); i++) {
                String table = tablesToDrop.get(i);
                dropTable(con, table, true);
            }
        }
    }
}
