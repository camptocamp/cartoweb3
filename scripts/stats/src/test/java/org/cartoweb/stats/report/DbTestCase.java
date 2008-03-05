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

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.cartoweb.stats.BaseTestCase;
import org.cartoweb.stats.Utils;
import org.cartoweb.stats.imports.Import;
import org.cartoweb.stats.imports.StatsRecord;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.opts.GetOptions;
import org.pvalsecc.opts.Option;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

public abstract class DbTestCase extends BaseTestCase {
    public static final Log LOGGER = LogFactory.getLog(DbTestCase.class);
    public static final String TABLE_NAME = "test";
    public static final String RESULT_TABLE = TABLE_NAME + "_report";
    protected static final int NB_X = 10;
    protected static final int NB_Y = 10;

    public static final TimeScaleDefinition[] TIME_SCALES = {
            new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.DAY, 1, 7, "daily"),
            new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.WEEK, 1, 10, "weekly"),
            new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.MONTH, 1, 12, "monthly"),
            new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.YEAR, 1, 5, "yearly"),
    };

    /**
     * Taken from the environment variable "DB".
     */
    @Option(desc = "A database connection string like \"jdbc:postgresql://localhost/db_stats?user=pvalsecc&password=c2c\"",
            environment = "STATS_DB")
    protected String db = "jdbc:postgresql://localhost/db_stats?user=pvalsecc&password=c2c";

    protected Connection con;
    protected Import imports;

    public DbTestCase(String name) {
        super(name);

    }

    protected void setUp() throws Exception {
        super.setUp();

        try {
            GetOptions.parse(null, this);

            con = getConnection();
            Utils.dropAllReportTables(con, TABLE_NAME);
            cleanDB();
            String[] args = {
                    "--db=" + db,
                    "--tableName=" + TABLE_NAME,
                    "--logDir=whatever",
                    "--verbose=2",
                    "--format=CartoWeb",
            };
            imports = new Import(args);
            imports.dropStructure(con);
            imports.createStructure(con);
            Reports.checkDB(con, TABLE_NAME);
        } catch (Exception ex) {
            LOGGER.error(ex);
            throw ex;
        }
    }

    private void cleanDB() throws SQLException {
        Utils.dropTable(con, TABLE_NAME + "_reports", true);
    }

    private Connection getConnection() throws ClassNotFoundException, SQLException {
        Class.forName("org.postgresql.Driver");
        final Connection con = DriverManager.getConnection(db);
        con.setAutoCommit(false);  //for using cursors in the select
        return con;
    }

    protected void tearDown() throws Exception {
        super.tearDown();
        Report report = createReport();
        report.dropStructure(con, TABLE_NAME);
        imports.dropStructure(con);
        cleanDB();
        con.commit();
        con.close();
        con = null;
    }

    protected void addRecord(StatsRecord record) throws SQLException {
        final String query = "INSERT INTO " + TABLE_NAME + " (" + Import.MAPPER.getFieldNames() + ") VALUES (" + Import.MAPPER.getInsertPlaceHolders() + ")";
        PreparedStatement stmt = con.prepareStatement(query);

        Import.MAPPER.saveToDb(stmt, record, 1);
        stmt.addBatch();
        stmt.executeBatch();
        stmt.close();
        con.commit();
    }

    protected void checkValue(final long expected, String where, String valueName) throws SQLException {
        for (int i = 0; i < TIME_SCALES.length; ++i) {
            TimeScaleDefinition timeScale = TIME_SCALES[i];
            JdbcUtilities.runSelectQuery("checking report",
                    "SELECT " + valueName + " FROM " + timeScale.getTableName(RESULT_TABLE) + " " + where,
                    con, new JdbcUtilities.SelectTask() {
                public void setupStatement(PreparedStatement stmt) throws SQLException {
                }

                public void run(ResultSet rs) throws SQLException {
                    if (!rs.next()) {
                        fail("no record found");
                    }

                    long val = rs.getLong(1);
                    if (rs.wasNull()) {
                        fail("null value");
                    }

                    if (rs.next()) {
                        fail("more than one record found");
                    }

                    assertEquals(expected, val);
                }
            });
        }
    }

    protected void checkGridValue(long expected, int x, int y, String fieldName) throws SQLException {
        checkValue(expected, "", fieldName + "[" + (x + y * NB_X + 1) + "]");
    }

    protected void computeReport(boolean purgeOnConfigurationChange) throws SQLException, ClassNotFoundException {
        Report report = createReport();
        report.init(con, TABLE_NAME, purgeOnConfigurationChange);
        report.compute(con, TABLE_NAME);
    }

    protected abstract Report createReport();
}
