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

import org.apache.log4j.Logger;
import org.cartoweb.stats.report.Report;
import org.cartoweb.stats.report.TimeScaleDefinition;
import org.cartoweb.stats.report.dimension.Dimension;
import org.cartoweb.stats.report.dimension.DimensionMetaData;
import org.cartoweb.stats.report.result.Result;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.misc.StringUtils;

import java.sql.BatchUpdateException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

public class GridClassifier extends Classifier<long[][][]> {
    private static final Logger LOGGER = Logger.getLogger(Report.class);

    private final double minX;
    private final double deltaX;
    private final int nbX;
    private final double maxX;

    private final double minY;
    private final double deltaY;
    private final int nbY;
    private final double maxY;

    private final boolean bbox;

    private int startx;
    private int endx;
    private int starty;
    private int endy;

    public GridClassifier(double minX, double deltaX, int nbX, double minY, double deltaY, int nbY,
                          boolean bbox, String resultTable, TimeScaleDefinition[] timeScales) {
        super(resultTable, timeScales);
        this.minX = minX;
        this.deltaX = deltaX;
        this.nbX = nbX;
        this.minY = minY;
        this.deltaY = deltaY;
        this.nbY = nbY;
        this.bbox = bbox;

        maxX = this.minX + this.deltaX * this.nbX;
        maxY = this.minY + this.deltaY * this.nbY;

    }

    public String getSQLFields() {
        return "bbox_minx, bbox_miny, bbox_maxx, bbox_maxy";
    }

    public int updateFromResultSet(ResultSet rs, int pos) throws SQLException {
        double minx = rs.getDouble(++pos);
        double miny = rs.getDouble(++pos);
        double maxx = rs.getDouble(++pos);
        double maxy = rs.getDouble(++pos);
        if (bbox) {
            startx = Math.max((int) ((Math.max(minx, minX) - minX) / deltaX), 0);
            endx = Math.min((int) ((Math.min(maxx, maxX) - minX) / deltaX), nbX - 1);
            starty = Math.max((int) ((Math.max(miny, minY) - minY) / deltaY), 0);
            endy = Math.min((int) ((Math.min(maxy, maxY) - minY) / deltaY), nbY - 1);
        } else {
            startx = endx = (int) (((minx + maxx) / 2 - minX) / deltaX);
            starty = endy = (int) (((miny + maxy) / 2 - minY) / deltaY);
            if (startx >= nbX || startx < 0 || starty >= nbY || starty < 0) {
                //out of range => nothing
                endx = endy = -1;
                startx = starty = 0;
            }
        }

        return pos;
    }

    protected void save(Connection con, final DimensionMetaData<?>[] dimensionMetaDatas, final Result[] results, final Map<List<Dimension>, long[][][]> curValues, TimeScaleDefinition timeScale, final Timestamp curTime) throws SQLException {
        StringBuilder fields = new StringBuilder();
        StringBuilder values = new StringBuilder();
        if (dimensionMetaDatas != null) {
            for (int i = 0; i < dimensionMetaDatas.length; ++i) {
                DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
                fields.append(",").append(dimensionMetaData.getReportFieldNames());
                StringUtils.repeat(values, ",?", dimensionMetaData.getNbReportFields());
            }
        }
        for (int i = 0; i < results.length; ++i) {
            Result result = results[i];
            fields.append(',').append(result.getType());
        }
        StringUtils.repeat(values, ",?", results.length);

        final boolean hasPrevious = readPreviousResults(con, dimensionMetaDatas, results, curValues, timeScale, curTime, fields.substring(1));
        if (hasPrevious) {
            deletePrevResults(con, timeScale, curTime);
        }
        saveNewResults(con, curValues, timeScale, curTime, fields.toString(), values.toString());
    }

    private void saveNewResults(Connection con, Map<List<Dimension>, long[][][]> curValues, TimeScaleDefinition timeScale, Timestamp curTime, String fields, String values) throws SQLException {
        StringBuilder insertQuery = new StringBuilder();
        insertQuery.append("INSERT INTO ").append(timeScale.getTableName(resultTableName)).append(" (general_time");
        insertQuery.append(fields).append(") VALUES (?").append(values).append(")");
        PreparedStatement stmnt = con.prepareStatement(insertQuery.toString());

        try {
            int cpt = 0;
            for (Map.Entry<List<Dimension>, long[][][]> entry : curValues.entrySet()) {
                List<Dimension> dimensions = entry.getKey();
                long[][][] grid = entry.getValue();

                int pos = 0;
                stmnt.setTimestamp(++pos, curTime);
                if (dimensions != null) {
                    for (int i = 0; i < dimensions.size(); i++) {
                        Dimension dimension = dimensions.get(i);
                        pos = dimension.fillStatement(stmnt, pos);
                    }
                }
                for (int i = 0; i < grid[0][0].length; ++i) {
                    stmnt.setArray(++pos, new ArrayAdapter(grid, i));
                }

                stmnt.addBatch();

                if (++cpt % 100 == 0) {
                    stmnt.executeBatch();
                }
            }
            stmnt.executeBatch();
        } catch (BatchUpdateException ex) {
            if (ex.getNextException() != null) {
                LOGGER.error(ex.getNextException());
            }
            throw ex;
        } finally {
            stmnt.close();
        }
    }

    private boolean readPreviousResults(Connection con, final DimensionMetaData<?>[] dimensionMetaDatas, final Result[] results, final Map<List<Dimension>, long[][][]> curValues, TimeScaleDefinition timeScale, final Timestamp curTime, String fields) throws SQLException {
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
                    long[][][] cur = curValues.get(dimensions);
                    if (cur == null) {
                        cur = createValue(results.length);
                        curValues.put(dimensions, cur);
                    }
                    for (int i = 0; i < results.length; ++i) {
                        Long[] curResult = (Long[]) rs.getArray(++pos).getArray();
                        int cpt = 0;
                        for (int j = 0; j < cur.length; ++j) {
                            long[][] line = cur[j];
                            for (int k = 0; k < line.length; ++k) {
                                long[] cell = line[k];
                                cell[i] += curResult[cpt++];
                            }
                        }
                    }
                    hasPrevious[0] = true;
                }
            }
        });
        return hasPrevious[0];
    }

    protected long[][][] createValue(int nbResults) {
        long[][][] grid = new long[nbX][][];
        for (int i = 0; i < grid.length; ++i) {
            grid[i] = new long[nbY][];
            for (int j = 0; j < grid[i].length; ++j) {
                grid[i][j] = new long[nbResults];
            }
        }
        return grid;
    }

    protected void updateValue(long[][][] cur, Result[] results) {
        for (int x = startx; x <= endx; ++x) {
            final long[][] row = cur[x];
            for (int y = starty; y <= endy; ++y) {
                final long[] cell = row[y];
                for (int i = 0; i < cell.length; ++i) {
                    cell[i] = results[i].updateResult(cell[i]);
                }
            }
        }
    }

    protected String getTableFieldsDefinition(Result[] results) {
        StringBuilder query = new StringBuilder();
        for (int j = 0; j < results.length; ++j) {
            Result result = results[j];
            if (j > 0) {
                query.append(',');
            }
            query.append(result.getType()).append(" bigint[]");
        }
        return query.toString();
    }

    public void getIniFile(StringBuilder result) {
        super.getIniFile(result);
        if (bbox) {
            result.append("type=gridbbox\n");
        } else {
            result.append("type=gridcenter\n");
        }
        result.append("minx=").append(minX).append("\n");
        result.append("miny=").append(minY).append("\n");
        result.append("size=").append(deltaX).append("\n");
        result.append("nx=").append(nbX).append("\n");
        result.append("ny=").append(nbY).append("\n");
    }
}
