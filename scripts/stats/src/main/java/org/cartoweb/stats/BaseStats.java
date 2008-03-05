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

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.ConsoleAppender;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.apache.log4j.PatternLayout;
import org.pvalsecc.jdbc.ConnectionFactory;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.opts.GetOptions;
import org.pvalsecc.opts.InvalidOption;
import org.pvalsecc.opts.Option;

import java.io.PrintWriter;
import java.io.StringWriter;
import java.io.Writer;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.List;

/**
 * Base class for all the processes.
 */
public abstract class BaseStats {
    private static final Log LOGGER = LogFactory.getLog(BaseStats.class);

    @Option(desc = "A database connection string like \"jdbc:postgresql://localhost/db_stats?user=pvalsecc&password=c2c\"",
            mandatory = true, environment = "STATS_DB")
    private String db = "";

    @Option(desc = "2 if you want the debug information (stacktraces are shown), 1 for infos and 0 for only warnings and errors",
            environment = "STATS_VERBOSE")
    private int verbose = 1;

    @Option(desc = "Name of the DB table to use as a source for the stats",
            environment = "STATS_TABLE_NAME")
    protected String tableName = "stats";

    protected BaseStats() {
        if (!Logger.getRootLogger().getAllAppenders().hasMoreElements()) {
            //BasicConfigurator.configure(new ConsoleAppender(new PatternLayout("%d{HH:mm:ss,SSS} %-5p %25.25c - %m%n")));
            BasicConfigurator.configure(new ConsoleAppender(new PatternLayout("%d{HH:mm:ss,SSS} %-5p - %m%n")));
        }
    }

    protected void parseArgs(String[] args) {
        try {
            List<String> remaining = GetOptions.parse(args, this);

            if (!remaining.isEmpty()) {
                printUsage("unknown options");
            }
        } catch (InvalidOption invalidOption) {
            printUsage(invalidOption.getMessage());
        }
        switch (verbose) {
            case 0:
                Logger.getRootLogger().setLevel(Level.WARN);
                break;
            case 1:
                Logger.getRootLogger().setLevel(Level.INFO);
                break;
            default:
                Logger.getRootLogger().setLevel(Level.DEBUG);
                break;
        }
    }

    public void run() {
        try {
            runImpl();
        } catch (Throwable e) {
            LOGGER.error(e.toString());
            if (LOGGER.isDebugEnabled()) {
                final Writer result = new StringWriter();
                final PrintWriter printWriter = new PrintWriter(result);
                e.printStackTrace(printWriter);
                LOGGER.debug(result.toString());
            }
        }
    }

    protected abstract void runImpl() throws Exception;

    protected void printUsage(String message) {
        try {
            System.out.println(message);
            System.out.println();
            System.out.println("Usage:");
            System.out.println("  " + getClass().getName() + " " + GetOptions.getShortList(this));
            System.out.println();
            System.out.println("Options:");
            System.out.println(GetOptions.getLongList(this));
        } catch (IllegalAccessException e) {
            e.printStackTrace();
        }
        System.exit(-1);
    }

    protected Connection getConnection() throws SQLException {
        if (!db.startsWith("jdbc:postgres")) {
            throw new RuntimeException("We support only postgresql for the moment");
        }
        final Connection result = ConnectionFactory.getConnection(db);
        result.setAutoCommit(false);  //for using cursors in the select
        return result;
    }

    protected Timestamp getLastRecordDate(Connection con) throws SQLException {
        final Timestamp[] lastRecordDate = new Timestamp[1];
        JdbcUtilities.runSelectQuery("getting last record date", "SELECT max(general_time) from " + tableName, con, new JdbcUtilities.SelectTask() {
            public void setupStatement(PreparedStatement stmt) throws SQLException {
            }

            public void run(ResultSet rs) throws SQLException {
                if (!rs.next()) {
                    throw new RuntimeException("Returned no results");
                }
                lastRecordDate[0] = rs.getTimestamp(1);
                if (rs.next()) {
                    throw new RuntimeException("Returned more than one result");
                }
            }
        });
        LOGGER.info("Last record date found in the DB: " + lastRecordDate[0]);
        return lastRecordDate[0];
    }

}
