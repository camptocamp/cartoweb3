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

import org.cartoweb.stats.Utils;
import org.pvalsecc.jdbc.JdbcUtilities;

import java.sql.Connection;
import java.sql.SQLException;

public abstract class BaseDbMap {
    protected final String tableName;
    private final String columnName;
    protected int sequence = 0;
    private final boolean foreignKey;

    public BaseDbMap(String statsTableName, String columnName, boolean foreignKey) {
        this.foreignKey = foreignKey;
        this.tableName = statsTableName + "_" + columnName;
        this.columnName = columnName;
    }

    public abstract void createStructure(Connection con) throws SQLException;

    public void dropStructure(Connection con) throws SQLException {
        Utils.dropTable(con, tableName, true);
    }

    public void vacuum(Connection con) throws SQLException {
        JdbcUtilities.runDeleteQuery("vacuuming " + tableName, "VACUUM FULL ANALYZE " + tableName, con, null);
    }

    public abstract void load(Connection con) throws SQLException;

    public abstract void save(Connection con) throws SQLException;

    public void createForeignKeys(Connection con, String statsTableName) throws SQLException {
        if (foreignKey) {
            JdbcUtilities.runDeleteQuery("creating the foreign key for " + tableName,
                    "ALTER TABLE " + statsTableName + " ADD CONSTRAINT fk_" + tableName +
                            " FOREIGN KEY (" + columnName + ") REFERENCES " + tableName + " (id)", con, null);
        }
    }

    public void dropForeignKeys(Connection con, String statsTableName) throws SQLException {
        if (foreignKey) {
            JdbcUtilities.runDeleteQuery("creating the foreign key for " + tableName,
                    "ALTER TABLE " + statsTableName + " DROP CONSTRAINT fk_" + tableName, con, null);
        }
    }

    protected static class Info {
        protected final int id;
        protected final boolean inDB;

        protected Info(int id, boolean inDB) {
            this.id = id;
            this.inDB = inDB;
        }
    }
}
