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

public class IntFieldMetaData implements DimensionMetaData<IntField> {
    private final String fieldName;
    private final String type;
    private final boolean foreignKey;

    public IntFieldMetaData(String fieldName, String type, boolean isForeignKey) {
        this.fieldName = fieldName;
        this.type = type;
        foreignKey = isForeignKey;
    }

    public String getStatsFieldNames() {
        return fieldName;
    }

    public int getNbStatsFieldNames() {
        return 1;
    }

    public IntField[] buildFromStatResultSet(ResultSet rs, int pos) throws SQLException {
        return new IntField[]{buildFromReportResultSet(rs, pos)};
    }

    public IntField buildFromReportResultSet(ResultSet rs, int pos) throws SQLException {
        final int value = rs.getInt(pos);
        if (rs.wasNull()) {
            return new IntField(null);
        } else {
            return new IntField(value);
        }
    }

    public String getFieldDefinitions() {
        return fieldName + " int";
    }

    public String getReportFieldNames() {
        return fieldName;
    }

    public int getNbReportFields() {
        return 1;
    }

    public String getIniType() {
        return type;
    }

    public String getIniAdditionalParam() {
        return null;
    }

    public void createDbStructure(Connection con, String resultTableName, String statsTableName) throws SQLException {
        if (foreignKey) {
            JdbcUtilities.runDeleteQuery("creating the foreign key for " + resultTableName + " and " + fieldName,
                    "ALTER TABLE " + resultTableName + " ADD CONSTRAINT fk_" + resultTableName + "_" + fieldName +
                            " FOREIGN KEY (" + fieldName + ") REFERENCES " + statsTableName + "_" + fieldName + " (id)", con, null);
        }
    }
}
