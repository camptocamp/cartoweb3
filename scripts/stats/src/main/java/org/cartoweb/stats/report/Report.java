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

package org.cartoweb.stats.report;

import org.apache.log4j.Logger;
import org.cartoweb.stats.Utils;
import org.cartoweb.stats.report.classifier.Classifier;
import org.cartoweb.stats.report.dimension.Dimension;
import org.cartoweb.stats.report.dimension.DimensionMetaData;
import org.cartoweb.stats.report.filter.Filter;
import org.cartoweb.stats.report.result.Result;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.misc.UnitUtilities;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.ArrayList;
import java.util.List;

public class Report {
    private static final Logger LOGGER = Logger.getLogger(Report.class);

    private final DimensionMetaData<?>[] dimensionMetaDatas;
    private final Filter[] filters;
    private final Classifier<?> classifier;
    private final Result[] results;
    private final String name;
    private final String label;
    private long lastIdSeen;
    private Timestamp lastTimeSeen;
    private boolean firstRun;

    public Report(DimensionMetaData<?>[] dimensionMetaDatas, Filter[] filters, Classifier<?> classifier, Result[] results, String name, String label) {
        this.dimensionMetaDatas = dimensionMetaDatas;
        this.filters = filters;
        this.classifier = classifier;
        this.results = results;
        this.name = name;
        this.label = label;
    }

    public void init(final Connection con, final String tableName, final boolean purgeOnConfigurationChange) throws SQLException {
        firstRun = true;
        lastIdSeen = -1;
        lastTimeSeen = null;
        JdbcUtilities.runSelectQuery("reading status of report '" + name + "'", "SELECT last_id, last_time, config FROM " + tableName + "_reports WHERE name=?", con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setString(1, name);
            }

            public void run(ResultSet rs) throws SQLException {
                if (rs.next()) {
                    StringBuilder config = new StringBuilder();
                    getIniFile(config);
                    if (!config.toString().equals(rs.getString(3))) {
                        //The config has changed.
                        if (!purgeOnConfigurationChange) {
                            throw new ConfigurationChangeException("Configuration changed for report '" + name + "'");
                        } else {
                            LOGGER.warn("Configuration changed for report [" + name + "] deleting the old results.");
                        }
                        dropStructure(con, tableName);
                    } else {
                        //OK, same config.
                        firstRun = false;
                        lastIdSeen = rs.getInt(1);
                        lastTimeSeen = rs.getTimestamp(2);
                        LOGGER.debug("Doing a differential run for report '" + name + "'");
                    }
                }
            }
        });
        if (firstRun) {
            classifier.createTables(con, dimensionMetaDatas, results, tableName);
        }
    }

    public void compute(final Connection con, String tableName) throws SQLException {
        LOGGER.info("Starting to compute report [" + name + "] for id>" + lastIdSeen);
        long startTime = System.currentTimeMillis();
        final String query = buildSelectQuery(tableName);

        JdbcUtilities.runSelectQuery("reading stats for " + name, query, con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                int pos = 0;
                stmt.setLong(++pos, lastIdSeen);
                for (int i = 0; i < filters.length; ++i) {
                    Filter filter = filters[i];
                    pos = filter.setSelectWhereParams(stmt, pos);
                }
            }

            public void run(ResultSet rs) throws SQLException {
                while (rs.next()) {
                    //deal with one hit....

                    int pos = 0;
                    long id = rs.getLong(++pos);
                    lastIdSeen = Math.max(id, lastIdSeen);
                    Timestamp generalTime = rs.getTimestamp(++pos);
                    if (lastTimeSeen == null || lastTimeSeen.before(generalTime)) {
                        lastTimeSeen = generalTime;
                    }
                    pos = classifier.updateFromResultSet(rs, pos);

                    boolean passed = true;
                    for (int i = 0; i < filters.length && passed; ++i) {
                        Filter filter = filters[i];
                        pos = filter.updateFromResultSet(rs, pos);
                        passed = filter.softCheck();
                    }

                    if (passed) {
                        Dimension[][] dimensions = null;
                        if (dimensionMetaDatas.length > 0) {
                            dimensions = new Dimension[dimensionMetaDatas.length][];
                            for (int i = 0; i < dimensionMetaDatas.length; ++i)
                            {
                                DimensionMetaData<?> meta = dimensionMetaDatas[i];
                                dimensions[i] = meta.buildFromStatResultSet(rs, pos + 1);
                                pos += meta.getNbStatsFieldNames();
                            }
                        }

                        for (int i = 0; i < results.length; ++i) {
                            Result result = results[i];
                            pos = result.updateFromResultSet(rs, pos);
                        }

                        List<Dimension> temp = new ArrayList<Dimension>(dimensionMetaDatas.length);
                        for (int i = 0; i < dimensionMetaDatas.length; i++) {
                            temp.add(null);
                        }
                        updateResults(generalTime, con, dimensions, 0, temp);
                    }

                }
                classifier.done(con, dimensionMetaDatas, results);
            }
        });

        purgeOld(con);
        saveStatus(con, tableName);

        con.commit();
        LOGGER.info("Time to run [" + name + "]: " + UnitUtilities.toElapsedTime(System.currentTimeMillis() - startTime) + " lastId=" + lastIdSeen + " lastTime=" + lastTimeSeen);
    }

    private void purgeOld(Connection con) throws SQLException {
        for (int i = 0; i < classifier.getTimeScales().length; ++i) {
            TimeScaleDefinition timeScaleDefinition = classifier.getTimeScales()[i];
            final Timestamp time = new Timestamp(timeScaleDefinition.getMinTime());
            String tableName = timeScaleDefinition.getTableName(classifier.getResultTableName());
            int nb = JdbcUtilities.runDeleteQuery("Purging old (<" + time + ") entries in " + tableName,
                    "DELETE FROM " + tableName + " WHERE general_time<?", con, new JdbcUtilities.DeleteTask() {
                public void setupStatement(PreparedStatement stmt) throws SQLException {
                    stmt.setTimestamp(1, time);
                }
            });
            LOGGER.debug(nb + " entries removed from " + tableName);
        }
    }

    private void saveStatus(Connection con, final String tableName) throws SQLException {
        JdbcUtilities.runDeleteQuery("deleting the old status for report '" + name + "'", "DELETE FROM " + tableName + "_reports WHERE name=?", con, new JdbcUtilities.DeleteTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setString(1, name);
            }
        });

        JdbcUtilities.runDeleteQuery("saving the status for report '" + name + "'",
                "INSERT INTO " + tableName + "_reports (name, last_id, last_time, config, tables, label) VALUES (?,?,?,?,?,?)", con, new JdbcUtilities.DeleteTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                StringBuilder config = new StringBuilder();
                getIniFile(config);
                stmt.setString(1, name);
                stmt.setLong(2, lastIdSeen);
                stmt.setTimestamp(3, lastTimeSeen);
                stmt.setString(4, config.toString());

                StringBuilder tables = new StringBuilder();
                for (int i = 0; i < classifier.getTimeScales().length; ++i) {
                    TimeScaleDefinition timeScaleDefinition = classifier.getTimeScales()[i];
                    if (i > 0) {
                        tables.append(',');
                    }
                    tables.append(timeScaleDefinition.getTableName(classifier.getResultTableName()));
                }
                stmt.setString(5, tables.toString());
                stmt.setString(6, label);
            }
        });
    }

    private void updateResults(Timestamp generalTime, Connection con, Dimension[][] dimensions, int depth, List<Dimension> temp) throws SQLException {
        if (dimensions != null && depth < dimensions.length) {
            Dimension[] cur = dimensions[depth];
            if (cur != null) {
                for (int i = 0; i < cur.length; ++i) {
                    temp.set(depth, cur[i]);
                    updateResults(generalTime, con, dimensions, depth + 1, temp);
                }
            } else {
                temp.set(depth, null);
                updateResults(generalTime, con, dimensions, depth + 1, temp);
            }
        } else {
            classifier.updateResult(generalTime, con, results, dimensionMetaDatas, temp);
        }
    }

    private String buildSelectQuery(String tableName) throws SQLException {
        final StringBuilder query = new StringBuilder();
        query.append("select id,general_time");
        if (classifier.getSQLFields() != null) {
            query.append(", ");
            query.append(classifier.getSQLFields());
        }
        for (int i = 0; i < filters.length; ++i) {
            Filter filter = filters[i];
            final String sqlFields = filter.getSQLFields();
            if (sqlFields != null) {
                query.append(", ");
                query.append(sqlFields);
            }
        }
        for (int i = 0; i < dimensionMetaDatas.length; ++i) {
            DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
            query.append(", ");
            query.append(dimensionMetaData.getStatsFieldNames());
        }
        for (int i = 0; i < results.length; ++i) {
            Result result = results[i];

            if (result.getSQLFields() != null) {
                query.append(", ");
                query.append(result.getSQLFields());
            }
        }
        query.append(" from ").append(tableName).append(" f").append(" where id>? ");
        for (int i = 0; i < filters.length; ++i) {
            Filter filter = filters[i];
            String whereClause = filter.getSelectWhereClause();
            if (whereClause != null) {
                query.append(" and (")
                        .append(whereClause)
                        .append(")");
            }
        }

        query.append(" order by general_time");
        return query.toString();
    }

    public void getIniFile(StringBuilder result) {
        result.append("[").append(name).append("]\n");
        classifier.getIniFile(result);
        result.append("values=");
        for (int i = 0; i < results.length; ++i) {
            Result cur = results[i];
            if (i > 0) {
                result.append(',');
            }
            result.append(cur.getType());
        }
        result.append('\n');

        result.append("dimensions=");
        for (int i = 0; i < dimensionMetaDatas.length; ++i) {
            DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
            if (i > 0) {
                result.append(',');
            }
            result.append(dimensionMetaData.getIniType());
        }
        result.append("\n");
        for (int i = 0; i < dimensionMetaDatas.length; ++i) {
            DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[i];
            String additional = dimensionMetaData.getIniAdditionalParam();
            if (additional != null) {
                result.append(additional).append('\n');
            }
        }

        for (int i = 0; i < filters.length; ++i) {
            Filter filter = filters[i];
            filter.getIniFile(result);
        }
    }

    public String toString() {
        StringBuilder result = new StringBuilder();
        getIniFile(result);
        return result.toString();
    }

    public void dropStructure(Connection con, String tableName) throws SQLException {
        Utils.dropReportTables(con, tableName, name);
    }
}
