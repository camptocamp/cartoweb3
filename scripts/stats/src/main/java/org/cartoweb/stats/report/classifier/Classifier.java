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

import org.cartoweb.stats.Utils;
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
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Knows how to organize the results.
 */
public abstract class Classifier<RESULT> {
    public static final List<Dimension> EMPTY_DIMENSIONS = new ArrayList<Dimension>(0);

    public final String resultTableName;
    private final TimeScaleDefinition[] timeScales;
    private final Map<List<Dimension>, RESULT>[] values;
    private final Timestamp[] curTimes;

    public Classifier(String resultTableName, TimeScaleDefinition[] timeScales) {
        this.resultTableName = resultTableName;
        this.timeScales = timeScales;
        values = new Map[timeScales.length];
        for (int i = 0; i < values.length; ++i) {
            values[i] = new HashMap<List<Dimension>, RESULT>();
        }
        curTimes = new Timestamp[timeScales.length];
    }

    /**
     * The additional SQL fields needed when reading the stats or NULL.
     */
    public abstract String getSQLFields();

    /**
     * Get the additional fields from the SELECT ResultSet.
     */
    public abstract int updateFromResultSet(ResultSet rs, int pos) throws SQLException;

    /**
     * Called for each stats records. Update the results accordingly.
     * <p/>
     * Will save the results in the DB in case of period change.
     */
    public void updateResult(Timestamp generalTime, Connection con, Result[] results, DimensionMetaData<?>[] dimensionMetaDatas, List<Dimension> dimensions) throws SQLException {
        for (int i = 0; i < timeScales.length; ++i) {
            TimeScaleDefinition timeScale = timeScales[i];
            if (timeScale.isInRange(generalTime)) {
                Map<List<Dimension>, RESULT> curValues = values[i];
                long curTime = timeScale.getStartOfPeriod(generalTime);

                //check we didn't change for another time period
                final Timestamp prevTime = curTimes[i];
                if (prevTime != null && prevTime.getTime() != curTime) {
                    save(con, dimensionMetaDatas, results, curValues, timeScale, prevTime);
                    curTimes[i] = new Timestamp(curTime);
                    curValues.clear();
                } else if (prevTime == null) {
                    curTimes[i] = new Timestamp(curTime);
                }

                RESULT cur = curValues.get(dimensions);
                if (cur == null) {
                    cur = createValue(results.length);
                    curValues.put(new ArrayList<Dimension>(dimensions), cur);
                }
                updateValue(cur, results);
            }
        }
    }

    /**
     * Save/merge the given period in the DB.
     */
    protected abstract void save(Connection con, DimensionMetaData<?>[] dimensionMetaDatas, Result[] results, Map<List<Dimension>, RESULT> curValues, TimeScaleDefinition timeScale, Timestamp curTime) throws SQLException;

    protected abstract RESULT createValue(int nbResults);

    protected abstract void updateValue(RESULT cur, Result[] results);

    /**
     * Flush the unsaved results in the DB.
     */
    public void done(Connection con, DimensionMetaData<?>[] dimensionMetaDatas, Result[] results) throws SQLException {
        for (int i = 0; i < timeScales.length; ++i) {
            TimeScaleDefinition timeScale = timeScales[i];
            Map<List<Dimension>, RESULT> curValues = values[i];
            if (!curValues.isEmpty()) {
                save(con, dimensionMetaDatas, results, curValues, timeScale, curTimes[i]);
                curValues.clear();
            }
        }
    }

    /**
     * Create the tables for the report's results (one per timescale).
     */
    public void createTables(Connection con, DimensionMetaData<?>[] dimensionMetaDatas, Result[] results, String statsTableName) throws SQLException {
        for (int i = 0; i < timeScales.length; ++i) {
            TimeScaleDefinition timeScale = timeScales[i];
            StringBuilder query = new StringBuilder();
            final String resultTableName = timeScale.getTableName(this.resultTableName);
            Utils.dropTable(con, resultTableName, true);
            query.append("CREATE TABLE ").append(resultTableName).append(" (general_time timestamp without time zone");
            query.append(',').append(getTableFieldsDefinition(results));
            for (int j = 0; j < dimensionMetaDatas.length; ++j) {
                DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[j];
                String cur = dimensionMetaData.getFieldDefinitions();
                if (cur != null) {
                    query.append(',').append(cur);
                }
            }
            query.append(")");
            JdbcUtilities.runDeleteQuery("Create " + resultTableName,
                    query.toString(), con, null);

            for (int j = 0; j < dimensionMetaDatas.length; ++j) {
                DimensionMetaData<?> dimensionMetaData = dimensionMetaDatas[j];
                dimensionMetaData.createDbStructure(con, resultTableName, statsTableName);
            }
        }
    }

    protected abstract String getTableFieldsDefinition(Result[] results);

    /**
     * Delete the previous results for being able to replace them with the new values.
     */
    protected void deletePrevResults(Connection con, TimeScaleDefinition timeScale, final Timestamp curTime) throws SQLException {
        StringBuilder deleteQuery = new StringBuilder();
        deleteQuery.append("DELETE FROM ").append(timeScale.getTableName(resultTableName));
        deleteQuery.append(" WHERE general_time=?");
        JdbcUtilities.runDeleteQuery("Removing previous results", deleteQuery.toString(), con, new JdbcUtilities.DeleteTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setTimestamp(1, curTime);
            }
        });
    }

    public void getIniFile(StringBuilder result) {
        for (int i = 0; i < timeScales.length; ++i) {
            TimeScaleDefinition timeScale = timeScales[i];
            timeScale.getIniFile(result);
        }
    }

    public TimeScaleDefinition[] getTimeScales() {
        return timeScales;
    }

    public String getResultTableName() {
        return resultTableName;
    }

    /**
     * @return The name of the report table spanning on the biggest time.
     */
    public String getBiggestTimeScaleTableName() {
        return getBiggestTimeScale().getTableName(resultTableName);
    }

    /**
     * @return The timescale going the furthest in the past.
     */
    private TimeScaleDefinition getBiggestTimeScale() {
        TimeScaleDefinition result = null;
        for (int i = 0; i < timeScales.length; ++i) {
            TimeScaleDefinition timeScale = timeScales[i];
            if (result == null || result.getMinTime() > timeScale.getMinTime()) {
                result = timeScale;
            }
        }
        return result;
    }
}
