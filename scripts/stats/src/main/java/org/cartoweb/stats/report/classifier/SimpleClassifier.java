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

package org.cartoweb.stats.report.classifier;

import org.cartoweb.stats.report.TimeScaleDefinition;
import org.cartoweb.stats.report.dimension.Dimension;
import org.cartoweb.stats.report.dimension.DimensionMetaData;
import org.cartoweb.stats.report.result.Result;
import org.pvalsecc.jdbc.JdbcUtilities;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.sql.Types;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

public class SimpleClassifier extends Classifier<long[]> {
    public SimpleClassifier(String resultTable, TimeScaleDefinition[] timeScales) {
        super(resultTable, timeScales);
    }

    public String getSQLFields() {
        return null;
    }

    public int updateFromResultSet(ResultSet rs, int pos) throws SQLException {
        return pos;
    }

    protected void save(Connection con, final DimensionMetaData<?>[] dimensionMetaDatas,
                        final Result[] results, final Map<List<Dimension>, long[]> curValues, TimeScaleDefinition timeScale,
                        final Timestamp curTime) throws SQLException {
        StringBuilder fields = new StringBuilder();
        StringBuilder values = new StringBuilder();
        if (dimensionMetaDatas != null) {
            for (int i = 0; i < dimensionMetaDatas.length; ++i) {
                DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
                if (dimensionMetaData.getReportFieldNames() != null) {
                    if (i > 0) {
                        fields.append(',');
                        values.append(',');
                    }
                    fields.append(dimensionMetaData.getReportFieldNames());
                    for (int j = 0; j < dimensionMetaData.getNbReportFields(); ++j)
                    {
                        if (j > 0) {
                            values.append(',');
                        }
                        values.append("?");
                    }
                }
            }
        }
        for (int i = 0; i < results.length; ++i) {
            Result result = results[i];
            if (fields.length() > 0) {
                fields.append(',');
                values.append(',');
            }
            fields.append(result.getType());
            values.append('?');
        }

        StringBuilder query = new StringBuilder();
        query.append("SELECT ");
        query.append(fields).append(" FROM ").append(timeScale.getTableName(resultTableName))
                .append(" WHERE general_time=?");
        final boolean[] hasPrevious = new boolean[]{false};
        JdbcUtilities.runSelectQuery("Reading previous results", query.toString(), con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setTimestamp(1, curTime);
            }

            public void run(ResultSet rs) throws SQLException {
                while (rs.next()) {
                    int pos = 0;
                    final List<Dimension> dimensions;
                    if (dimensionMetaDatas != null && dimensionMetaDatas.length > 0) {
                        dimensions = new ArrayList<Dimension>(dimensionMetaDatas.length);
                        for (int i = 0; i < dimensionMetaDatas.length; ++i) {
                            DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
                            dimensions.add(dimensionMetaData.buildFromReportResultSet(rs, pos + 1));
                            pos += dimensionMetaData.getNbStatsFieldNames();
                        }
                    } else {
                        dimensions = EMPTY_DIMENSIONS;
                    }
                    long[] cur = curValues.get(dimensions);
                    if (cur == null) {
                        cur = createValue(results.length);
                        curValues.put(dimensions, cur);
                    }
                    for (int i = 0; i < results.length; ++i) {
                        cur[i] += rs.getLong(++pos);
                    }
                    hasPrevious[0] = true;
                }
            }
        });

        if (hasPrevious[0]) {
            deletePrevResults(con, timeScale, curTime);
        }

        StringBuilder insertQuery = new StringBuilder();
        insertQuery.append("INSERT INTO ").append(timeScale.getTableName(resultTableName));
        insertQuery.append(" (general_time,").append(fields).append(") VALUES (?,").append(values).append(")");
        JdbcUtilities.runInsertQuery("Saving results", insertQuery.toString(), con, curValues.entrySet(), 500, new JdbcUtilities.InsertTask<Map.Entry<List<Dimension>, long[]>>() {
            public boolean marshall(PreparedStatement stmt, Map.Entry<List<Dimension>, long[]> item) throws SQLException {
                List<Dimension> dimensions = item.getKey();
                int pos = 0;
                stmt.setTimestamp(++pos, curTime);
                for (int i = 0; i < dimensions.size(); i++) {
                    Dimension dimension = dimensions.get(i);
                    if (dimension != null) {
                        pos = dimension.fillStatement(stmt, pos);
                    } else {
                        DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
                        for (int j = 0; j < dimensionMetaData.getNbReportFields(); ++j)
                        {
                            stmt.setNull(++pos, Types.INTEGER);
                        }
                    }
                }
                for (int i = 0; i < results.length; ++i) {
                    stmt.setLong(++pos, item.getValue()[i]);
                }
                return true;
            }
        });
    }

    protected long[] createValue(int nbResults) {
        return new long[nbResults];
    }

    protected void updateValue(long[] cur, Result[] results) {
        for (int i = 0; i < results.length; ++i) {
            cur[i] = results[i].updateResult(cur[i]);
        }
    }

    protected String getTableFieldsDefinition(Result[] results) {
        StringBuilder query = new StringBuilder();
        for (int j = 0; j < results.length; ++j) {
            Result result = results[j];
            if (j > 0) {
                query.append(',');
            }
            query.append(result.getType()).append(" bigint");
        }
        return query.toString();
    }

    public void getIniFile(StringBuilder result) {
        super.getIniFile(result);
        result.append("type=simple\n");
    }
}
