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

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Arrays;

public class RangeDoubleFieldMetaData implements DimensionMetaData<IntField> {
    private final String fieldName;
    private final String type;
    private final double[] limits;

    public RangeDoubleFieldMetaData(String fieldName, String scales, String type) {
        this.fieldName = fieldName;
        this.type = type;
        String[] limitsTxt = scales.split(",\\s*");
        limits = new double[limitsTxt.length];
        for (int i = 0; i < limitsTxt.length; ++i) {
            limits[i] = Double.parseDouble(limitsTxt[i]);
        }
        Arrays.sort(limits);
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
        final double value = rs.getDouble(pos);
        if (rs.wasNull()) {
            return new IntField(null);
        } else {
            int i;
            for (i = 0; i < limits.length; ++i) {
                double limit = limits[i];
                if (limit > value) {
                    break;
                }
            }
            return new IntField(i);
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
        final StringBuilder result = new StringBuilder(type);
        result.append("s=");
        for (int i = 0; i < limits.length; ++i) {
            double limit = limits[i];
            if (i > 0) {
                result.append(',');
            }
            result.append(limit);
        }
        return result.toString();
    }

    public void createDbStructure(Connection con, String resultTableName, String statsTableName) throws SQLException {
    }
}
