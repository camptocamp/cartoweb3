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

package org.cartoweb.stats.purge;

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.cartoweb.stats.BaseStats;
import org.cartoweb.stats.report.TimeScaleDefinition;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.opts.Option;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;

public class Purge extends BaseStats {
    private static final Log LOGGER = LogFactory.getLog(Purge.class);

    @Option(desc = "Number of days to keep in the statistique source table", environment = "STATS_NB_DAYS")
    private int nbDays = 365;

    @Option(desc = "If true, will act as usual, but will not modify the DB", environment = "STATS_SIMULATE")
    private boolean simulate = false;

    public Purge(String[] args) {
        super();
        parseArgs(args);
    }

    protected void runImpl() throws SQLException, ClassNotFoundException {
        Connection con = getConnection();

        Timestamp lastRecordDate = getLastRecordDate(con);
        TimeScaleDefinition ts = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.DAY, 1, nbDays + 1, "");
        ts.init(lastRecordDate);
        Timestamp minTime = new Timestamp(ts.getMinTime());
        long lastDeleted = getLastIdSmaller(con, minTime);
        if (lastDeleted < 0) {
            LOGGER.info("Nothing to delete");
        } else {
            //if (areAllReportsPastId(lastDeleted)) {
            if (areAllReportsPastDate(con, minTime)) {
                //OK, we can delete
                deleteAllBefore(con, minTime);
            }
        }
        if (simulate) {
            LOGGER.warn("Simulation mode. No purge done.");
            con.rollback();
        } else {
            con.commit();
        }
        con.close();
        con = null;
    }

    private void deleteAllBefore(Connection con, final Timestamp minTime) throws SQLException {
        int nbDeleted = JdbcUtilities.runDeleteQuery("deleting records before " + minTime,
                "DELETE FROM " + tableName + " WHERE general_time<=?",
                con, new JdbcUtilities.DeleteTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setTimestamp(1, minTime);
            }
        });
        LOGGER.info("Deleted " + nbDeleted + " records");
    }

    private boolean areAllReportsPastDate(Connection con, final Timestamp minTime) throws SQLException {
        final boolean[] ok = new boolean[]{true};
        JdbcUtilities.runSelectQuery("searching reports with last_time<" + minTime,
                "SELECT name, last_time FROM " + tableName + "_reports WHERE last_time<?",
                con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setTimestamp(1, minTime);
            }

            public void run(ResultSet rs) throws SQLException {
                while (rs.next()) {
                    LOGGER.warn("Report [" + rs.getString(1) + "] does not yet include data that would be deleted. Last treated date: " + rs.getTimestamp(2));
                    ok[0] = false;
                }
            }
        });
        return ok[0];
    }

    private boolean areAllReportsPastId(Connection con, final long id) throws SQLException {
        final boolean[] ok = new boolean[]{true};
        JdbcUtilities.runSelectQuery("searching reports with last_id<" + id,
                "SELECT name FROM " + tableName + "_reports WHERE last_id<?",
                con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setLong(1, id);
            }

            public void run(ResultSet rs) throws SQLException {
                while (rs.next()) {
                    LOGGER.warn("Report [" + rs.getString(1) + "] does not yet include data that would be deleted");
                    ok[0] = false;
                }
            }
        });
        return ok[0];
    }

    private long getLastIdSmaller(Connection con, final Timestamp time) throws SQLException {
        final long[] result = new long[]{-1};
        JdbcUtilities.runSelectQuery("getting first id smaller than " + time,
                "SELECT id from " + tableName + " WHERE general_time<=? ORDER BY id DESC LIMIT 1",
                con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
                stmt.setTimestamp(1, time);
            }

            public void run(ResultSet rs) throws SQLException {
                if (rs.next()) {
                    result[0] = rs.getLong(1);
                }
            }
        });
        return result[0];
    }

    public static void main(String[] args) {
        Purge purge = new Purge(args);
        purge.run();
    }
}
