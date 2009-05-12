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

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.cartoweb.stats.BaseStats;
import org.cartoweb.stats.Utils;
import org.pvalsecc.jdbc.BeanDbMapper;
import org.pvalsecc.jdbc.BeansDbMapper;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.jdbc.StatementUtils;
import org.pvalsecc.log.Progress;
import org.pvalsecc.misc.UnitUtilities;
import org.pvalsecc.opts.Option;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.security.MessageDigest;
import java.sql.*;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Convert the log files into DB tables usable by the statistics system.
 */
public class Import extends BaseStats {
    public static final Log LOGGER = LogFactory.getLog(Import.class);

    public static final BeanDbMapper<StatsRecord> MAPPER = BeansDbMapper.getMapper(StatsRecord.class);

    private static final boolean DB_SOLVE_HITS = false;

    @Option(desc = "Directory where the log files are stored", mandatory = true,
            environment = "STATS_LOG_DIR")
    private String logDir = "";

    @Option(desc = "Regular expression to be matched (on the absolute path) for a log file to be taken",
            environment = "STATS_LOG_REGEXP")
    private String logRegexp = "\\.log(\\.gz)?$";

    @Option(desc = "If true, will delete everything and start from scratch",
            environment = "STATS_INITIALIZE")
    private boolean initialize = false;

    @Option(desc = "If true, double imports will be ignored",
            environment = "STATS_IGNORE")
    private boolean ignore = false;

    @Option(desc = "If true, a layers table is generated", environment = "STATS_WANT_LAYERS")
    private boolean wantLayers = false;

    @Option(desc = "Format of the log files ('WMS' or 'CartoWeb')", mandatory = true,
            environment = "STATS_FORMAT")
    private String format = "CartoWeb";

    @Option(desc = "Used only if format='WMS'. Regular expression to capture the mapId (project) from the log file.",
            environment = "STATS_FORMAT")
    private String mapIdRegExp = null;

    @Option(desc = "Used only if format='WMS'. Configuration file name (.ini) that contains the strings to search for the map IDs and the name to use.",
            environment = "STATS_FORMAT")
    private String mapIdConfig = null;

    @Option(desc = "Continue the import in case of parsing error",
            environment = "STATS_SKIP_ERRORS")
    private boolean skipErrors = false;

    /**
     * List of all the side tables used to categorize some fields.
     */
    private final SideTables sideTables;

    /**
     * Used for the primary key of the main table.
     */
    private long curId = 0;

    /**
     * Cache hits to solve.
     */
    private final Set<String> hits = new HashSet<String>();

    public Import(String[] args) {
        super();
        parseArgs(args);

        if (format.equalsIgnoreCase("WMS")) {
            if (mapIdRegExp == null && mapIdConfig == null) {
                printUsage("Missing parameter 'mapIdRegExp' or 'mapIdConfig'");
            }
            if (mapIdRegExp != null && mapIdConfig != null) {
                printUsage("You cannot set both 'mapIdRegExp' and 'mapIdConfig'");
            }
        }
        sideTables = new SideTables(tableName);
    }

    private void findLastId(Connection con) throws SQLException {
        JdbcUtilities.runSelectQuery("finding the last id in table " + tableName,
                "SELECT max(id) FROM " + tableName, con, new JdbcUtilities.SelectTask() {
                    public void setupStatement(PreparedStatement stmt) throws SQLException {
                    }

                    public void run(ResultSet rs) throws SQLException {
                        while (rs.next()) {
                            curId = rs.getLong(1) + 1;
                        }
                    }
                });
    }

    protected void runImpl() throws ClassNotFoundException, SQLException, IOException {
        long startTime = System.currentTimeMillis();
        Connection con = getConnection();
        if (initialize) {
            dropStructure(con);
            createStructure(con);
            con.commit();
        }

        //get the list of files to load
        List<File> files = getFiles(new File(logDir));
        files = checkFiles(con, files);

        if (!files.isEmpty()) {
            if (!initialize) {
                findLastId(con);
                sideTables.load(con);
                sideTables.dropForeignKeys(con, tableName);
            }
            Progress progress = new Progress(10 * 1000, files.size(), "Files reading", LOGGER);
            for (int i = 0; i < files.size(); i++) {
                File file = files.get(i);
                progress.update(i);
                if (LOGGER.isDebugEnabled()) {
                    LOGGER.debug("Reading file " + (i + 1) + "/" + files.size() + ": " + file);
                }
                convertFile(con, file);
            }

            sideTables.save(con);

            LOGGER.info("Time to import " + files.size() + " files: " + UnitUtilities.toElapsedTime(System.currentTimeMillis() - startTime));
            if (initialize) {
                createIndexes(con);
            } else {
                Progress progressI = new Progress(10 * 1000, sideTables.size(), "Foreign key creation", LOGGER);
                sideTables.createForeignKeys(con, progressI, 0, tableName);
            }
            fillCacheHits(con);
            vacuum(con);
        } else {
            LOGGER.info("No new file to import");
        }

        con.close();
    }

    private List<File> checkFiles(Connection con, List<File> files) throws SQLException {
        LOGGER.info("Checking files to import.");

        List<File> result;
        final Map<String, File> fileByMd5 = new HashMap<String, File>();
        final Map<File, String> md5ByFile = new HashMap<File, String>();
        final boolean hasNoHistory = initialize || JdbcUtilities.countTable(con, tableName + "_all_files", null) == 0;
        result = new ArrayList<File>(files.size());

        Progress progress = new Progress(10 * 1000, files.size(), "Files checking", LOGGER);
        for (int i = 0; i < files.size(); i++) {
            progress.update(i);
            final File file = files.get(i);
            final long fileSize = file.length();
            final boolean toImport = hasNoHistory || checkFileNeedsImport(con, file, fileSize);
            if (toImport) {
                if (checkHasNotSameFile(con, file, fileByMd5, md5ByFile, hasNoHistory)) {
                    result.add(file);
                }
            }
        }

        JdbcUtilities.runInsertQuery("inserting entries in " + tableName + "_all_files",
                "INSERT INTO " + tableName + "_all_files (imported_date, size, path, checksum) VALUES (now(),?,?,?)", con, result, 500, new JdbcUtilities.InsertTask<File>() {
                    public boolean marshall(PreparedStatement stmt, File item) throws SQLException {
                        stmt.setLong(1, item.length());
                        stmt.setString(2, item.getAbsolutePath());
                        stmt.setString(3, md5ByFile.get(item));
                        return true;
                    }
                });

        LOGGER.info("Files checking done.");

        return result;
    }

    private boolean checkHasNotSameFile(Connection con, final File file, Map<String, File> fileByMd5, Map<File, String> md5ByFile, boolean hasNoHistory) throws SQLException {
        final String md5 = getMD5(file);
        final File known = fileByMd5.get(md5);
        if (known != null) {
            LOGGER.warn("The same file is twice in the directory. It's name has now changed! (old path=" + known + ", new path=" + file + ")");
            if (!ignore) {
                throw new RuntimeException();
            }
            return false;
        }
        fileByMd5.put(md5, file);
        md5ByFile.put(file, md5);
        final boolean[] sameFileFound = new boolean[]{false};
        if (!hasNoHistory) {
            JdbcUtilities.runSelectQuery("checking no file has checksum=" + md5,
                    "SELECT imported_date, path FROM " + tableName + "_all_files WHERE checksum=?", con, new JdbcUtilities.SelectTask() {
                        public void setupStatement(PreparedStatement stmt) throws SQLException {
                            stmt.setString(1, md5);
                        }

                        public void run(ResultSet rs) throws SQLException {
                            while (rs.next()) {
                                Timestamp importedWhen = rs.getTimestamp(1);
                                String path = rs.getString(2);
                                LOGGER.warn("On " + importedWhen + ", same file was already imported. It's name has now changed! (old path=" + path + ", new path=" + file + ")");
                                if (!ignore) {
                                    throw new RuntimeException();
                                }
                                sameFileFound[0] = true;
                            }
                        }
                    });
        }
        return !sameFileFound[0];
    }

    private boolean checkFileNeedsImport(Connection con, final File file, final long fileSize) throws SQLException {
        final boolean[] toImport = new boolean[]{true};
        JdbcUtilities.runSelectQuery("checking file " + file.getAbsolutePath() + " is known",
                "SELECT imported_date, size FROM " + tableName + "_all_files WHERE path=?", con, new JdbcUtilities.SelectTask() {
                    public void setupStatement(PreparedStatement stmt) throws SQLException {
                        stmt.setString(1, file.getAbsolutePath());
                    }

                    public void run(ResultSet rs) throws SQLException {
                        while (rs.next()) {
                            Timestamp importedWhen = rs.getTimestamp(1);
                            long size = rs.getLong(2);
                            if (size != fileSize) {
                                LOGGER.warn("On " + importedWhen + " file " + file + " was already imported and it's size has changed");
                                if (!ignore) {
                                    throw new RuntimeException();
                                }
                            } else {
                                LOGGER.debug("On " + importedWhen + " file " + file + " was already imported");
                            }
                            toImport[0] = false;
                        }
                    }
                });
        return toImport[0];
    }

    private String getMD5(File file) {
        try {
            MessageDigest md5 = MessageDigest.getInstance("MD5");
            InputStream fin = new FileInputStream(file);
            byte[] buffer = new byte[1024];
            int read;
            do {
                read = fin.read(buffer);
                if (read > 0) {
                    md5.update(buffer, 0, read);
                }
            } while (read != -1);
            fin.close();
            byte[] digest = md5.digest();
            if (digest == null) {
                return "";
            }
            String strDigest = "0x";
            for (int i = 0; i < digest.length; i++) {
                strDigest += Integer.toString((digest[i] & 0xff)
                        + 0x100, 16).substring(1).toUpperCase();
            }
            return strDigest;
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
    }

    private List<File> getFiles(File dir) {
        Pattern filter = Pattern.compile(logRegexp);
        if (!dir.exists() || !dir.isDirectory()) {
            throw new RuntimeException(logDir + " is not a valid directory");
        }
        final File[] list = dir.listFiles();
        final List<File> result = new ArrayList<File>(list.length);
        for (int i = 0; i < list.length; ++i) {
            File file = list[i];
            if (file.isDirectory()) {
                result.addAll(getFiles(file));
            } else {
                Matcher matcher = filter.matcher(file.getAbsolutePath());
                if (matcher.find()) {
                    result.add(file);
                }
            }
        }
        return result;
    }

    /**
     * Imports one file into the DB.
     */
    private void convertFile(final Connection con, File file) throws IOException, SQLException {
        try {
            final String query = "INSERT INTO " + tableName + " (" + MAPPER.getFieldNames() + ") VALUES (" + MAPPER.getInsertPlaceHolders() + ")";
            final PreparedStatement layerStmt = wantLayers ? con.prepareStatement("INSERT INTO " + tableName + "_layers (id, layer) VALUES (?,?)") : null;

            StatsReader reader = createReader(file);

            JdbcUtilities.runInsertQuery("inserting stats", query, con, reader, 500, new JdbcUtilities.InsertTask<StatsRecord>() {
                private int cptLayers = 0;

                public boolean marshall(PreparedStatement stmt, StatsRecord item) throws SQLException {
                    if (item != null) {
                        item.setId(curId++);
                        MAPPER.saveToDb(stmt, item, 1);

                        if (wantLayers && item.getLayerArray() != null) {
                            for (int i = 0; i < item.getLayerArray().size(); i++) {
                                Integer val = item.getLayerArray().get(i);
                                layerStmt.setLong(1, item.getId());
                                layerStmt.setInt(2, val);
                                layerStmt.addBatch();
                                if ((++cptLayers % 500) == 0) {
                                    layerStmt.executeBatch();
                                }
                            }
                        }
                        return true;
                    } else {
                        return false;
                    }
                }
            });

            if (layerStmt != null) {
                layerStmt.executeBatch();
                layerStmt.close();
            }
        } catch (BatchUpdateException ex) {
            ex.getNextException().printStackTrace();
            throw ex;
        }
    }

    private StatsReader createReader(File file) throws IOException {
        if (format.equalsIgnoreCase("WMS")) {
            MapIdExtractor mapIdExtractor = createMapIdExtractor();
            return new WmsReader(file, sideTables, wantLayers, mapIdExtractor, skipErrors);
        } else if (format.equalsIgnoreCase("SecureWMS")) {
            return new SecureWmsReader(file, sideTables, wantLayers, skipErrors);
        } else if (format.equalsIgnoreCase("CartoWeb")) {
            return new CartoWebReader(file, sideTables, wantLayers, skipErrors);
        } else {
            throw new RuntimeException("Format not supported: " + format);
        }
    }

    private MapIdExtractor createMapIdExtractor() throws IOException {
        if (mapIdRegExp != null) {
            return new RegExpMapIdExtractor(mapIdRegExp);
        } else {
            return new ConfigMapIdExtractor(mapIdConfig);
        }
    }

    public void dropStructure(Connection con) throws SQLException {
        Utils.dropAllReportTables(con, tableName);
        Utils.dropTable(con, tableName + "_all_files", true);
        Utils.dropTable(con, tableName, true);
        Utils.dropTable(con, tableName + "_layers", true);
        Utils.dropSequence(con, tableName + "_id_seq", true);

        sideTables.dropStructure(con);
    }

    public void createStructure(Connection con) throws SQLException {
        JdbcUtilities.runDeleteQuery("creating table " + tableName,
                "CREATE TABLE " + tableName + " (id bigint NOT NULL, general_browser_info integer, exportpdf_format integer, general_ip text, general_mapid integer, images_mainmap_height integer, images_mainmap_size integer, general_direct_access boolean, general_security_user integer, general_cache_id text, general_elapsed_time real, general_export_plugin integer, general_ua integer, query_results_count integer, general_cache_hit text, location_scale real, general_sessid int, images_mainmap_width integer, exportpdf_resolution integer, general_time timestamp without time zone, bbox_minx real, bbox_miny real, bbox_maxx real, bbox_maxy real, layers_switch_id integer, general_client_version integer, layers text, query_results_table_count text, general_request_id text)", con, null);
        if (wantLayers) {
            JdbcUtilities.runDeleteQuery("creating table " + tableName + "_layers",
                    "CREATE TABLE " + tableName + "_layers (id bigint NOT NULL, layer integer NOT NULL)", con, null);
        }
        JdbcUtilities.runDeleteQuery("creating table all_files",
                "CREATE TABLE " + tableName + "_all_files (imported_date timestamp NOT NULL, size bigint NOT NULL, path text NOT NULL, checksum text NOT NULL)", con, null);
        JdbcUtilities.runDeleteQuery("creating sequence " + tableName + "_id_seq",
                "CREATE SEQUENCE " + tableName + "_id_seq", con, null);

        sideTables.createStructure(con);
    }

    private void createIndexes(Connection con) throws SQLException {
        Progress progress = new Progress(10 * 1000, 6 + sideTables.size(), "Index creation", LOGGER);
        JdbcUtilities.runDeleteQuery("creating primary key", "ALTER TABLE " + tableName + " ADD PRIMARY KEY (id)", con, null);
        progress.update(1);
        JdbcUtilities.runDeleteQuery("indexing general_mapid", "CREATE INDEX i_" + tableName + "_general_mapid ON " + tableName + " (general_mapid)", con, null);
        progress.update(2);
        JdbcUtilities.runDeleteQuery("indexing general_time", "CREATE INDEX i_" + tableName + "_general_time ON " + tableName + " (general_time)", con, null);
        progress.update(3);
        JdbcUtilities.runDeleteQuery("indexing layers_switch_id", "CREATE INDEX i_" + tableName + "_layers_switch_id ON " + tableName + " (layers_switch_id)", con, null);
        progress.update(4);
        JdbcUtilities.runDeleteQuery("indexing location_scale", "CREATE INDEX i_" + tableName + "_location_scale ON " + tableName + " (location_scale)", con, null);
        progress.update(5);
        JdbcUtilities.runDeleteQuery("indexing general_cache_id", "CREATE INDEX i_" + tableName + "_general_cache_id ON " + tableName + " (general_cache_id)", con, null);
        if (!DB_SOLVE_HITS) {
            JdbcUtilities.runDeleteQuery("indexing general_cache_hit", "CREATE INDEX i_" + tableName + "_general_cache_hit ON " + tableName + " (general_cache_hit)", con, null);
        }
        progress.update(6);
        sideTables.createForeignKeys(con, progress, 7, tableName);
        con.commit();
        LOGGER.info("Indexes created");
    }

    private void fillCacheHits(Connection con) throws SQLException {
        con.commit();
        con.setAutoCommit(true);
        JdbcUtilities.runDeleteQuery("vacuuming " + tableName, "VACUUM ANALYZE " + tableName, con, null);
        con.setAutoCommit(false);

        if (DB_SOLVE_HITS) {
            //take around 55m for 4M records and is not greate for incremental updates...
            JdbcUtilities.runDeleteQuery("solving cache hits",
                    "UPDATE " + tableName + " f SET general_elapsed_time=s.general_elapsed_time, images_mainmap_width=s.images_mainmap_width, images_mainmap_height=s.images_mainmap_height, layers=s.layers, layers_switch_id=s.layers_switch_id, bbox_minx=s.bbox_minx, bbox_miny=s.bbox_miny, bbox_maxx=s.bbox_maxx, bbox_maxy=s.bbox_maxy, location_scale=s.location_scale, query_results_count=s.query_results_count, query_results_table_count=s.query_results_table_count FROM " + tableName + " s WHERE s.general_cache_id=f.general_cache_hit AND f.general_cache_hit IS NOT NULL AND f.general_elapsed_time IS NULL AND f.layers IS NULL",
                    con, null);
        } else {
            //takes around 21m for the same 4M records and is optimal for incremental updates...
            try {
                final PreparedStatement updateStmt = con.prepareStatement("UPDATE " + tableName + " SET general_elapsed_time=?, images_mainmap_width=?, images_mainmap_height=?, layers=?, layers_switch_id=?, bbox_minx=?, bbox_miny=?, bbox_maxx=?, bbox_maxy=?, location_scale=?, query_results_count=?, query_results_table_count=? WHERE general_cache_hit=?");
                if (hits.size() == 0) {
                    return;
                }

                JdbcUtilities.runSelectQuery("reading cached values",
                        "SELECT general_cache_id, general_elapsed_time, images_mainmap_width, images_mainmap_height, layers, layers_switch_id, bbox_minx, bbox_miny, bbox_maxx, bbox_maxy, location_scale, query_results_count, query_results_table_count FROM " + tableName + " WHERE general_cache_id IS NOT NULL",
                        con, new JdbcUtilities.SelectTask() {
                            private int cpt = 0;

                            public void setupStatement(PreparedStatement stmt) throws SQLException {
                            }

                            public void run(ResultSet rs) throws SQLException {
                                int count = 0;
                                final int todo = hits.size();
                                Progress progress = new Progress(10 * 1000, todo, "Cache hit record updating", LOGGER);
                                while (rs.next()) {
                                    String cacheId = rs.getString(1);
                                    //We can have the same general_cache_id multiple times.
                                    //So we have to remove it from the set.
                                    if (hits.remove(cacheId)) {
                                        StatementUtils.copyFloat(rs, 2, updateStmt, 1);
                                        StatementUtils.copyInt(rs, 3, updateStmt, 2);
                                        StatementUtils.copyInt(rs, 4, updateStmt, 3);
                                        StatementUtils.copyString(rs, 5, updateStmt, 4);
                                        StatementUtils.copyInt(rs, 6, updateStmt, 5);
                                        StatementUtils.copyFloat(rs, 7, updateStmt, 6);
                                        StatementUtils.copyFloat(rs, 8, updateStmt, 7);
                                        StatementUtils.copyFloat(rs, 9, updateStmt, 8);
                                        StatementUtils.copyFloat(rs, 10, updateStmt, 9);
                                        StatementUtils.copyFloat(rs, 11, updateStmt, 10);
                                        StatementUtils.copyInt(rs, 12, updateStmt, 11);
                                        StatementUtils.copyString(rs, 13, updateStmt, 12);
                                        updateStmt.setString(13, cacheId);
                                        updateStmt.addBatch();

                                        if (++cpt % 50 == 0) {
                                            int[] counts = updateStmt.executeBatch();
                                            for (int i = 0; i < counts.length; ++i) {
                                                count += counts[i];
                                            }
                                        }

                                        progress.update(todo - hits.size());
                                    }
                                }
                                ++cpt;
                                int[] counts = updateStmt.executeBatch();
                                for (int i = 0; i < counts.length; ++i) {
                                    count += counts[i];
                                }

                                LOGGER.info(count + " cache hit records updated from " + cpt + " cached values");
                            }
                        });

                updateStmt.close();
            } catch (BatchUpdateException ex) {
                LOGGER.error(ex.getNextException());
                throw ex;
            }
        }
        con.commit();
    }

    private void vacuum(Connection con) throws SQLException {
        con.setAutoCommit(true);
        LOGGER.info("Doing full vacuum.");
        JdbcUtilities.runDeleteQuery("vacuuming " + tableName, "VACUUM FULL ANALYZE " + tableName, con, null);
        sideTables.vacuum(con);
        LOGGER.info("Vacuum done.");
    }

    public SideTables getSideTables() {
        return sideTables;
    }

    public static void main(String[] args) {
        Import imports = new Import(args);
        imports.run();
    }
}
