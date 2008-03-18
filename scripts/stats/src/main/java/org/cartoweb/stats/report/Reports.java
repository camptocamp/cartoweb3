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
import org.cartoweb.stats.BaseStats;
import org.cartoweb.stats.Utils;
import org.cartoweb.stats.report.classifier.Classifier;
import org.cartoweb.stats.report.classifier.GridClassifier;
import org.cartoweb.stats.report.classifier.SimpleClassifier;
import org.cartoweb.stats.report.dimension.DimensionMetaData;
import org.cartoweb.stats.report.dimension.IntFieldMetaData;
import org.cartoweb.stats.report.dimension.LayerMetaData;
import org.cartoweb.stats.report.dimension.RangeDoubleFieldMetaData;
import org.cartoweb.stats.report.filter.DoubleRangeFilter;
import org.cartoweb.stats.report.filter.Filter;
import org.cartoweb.stats.report.filter.IdSetFilter;
import org.cartoweb.stats.report.filter.IntegerRangeFilter;
import org.cartoweb.stats.report.filter.LayerFilter;
import org.cartoweb.stats.report.result.CounterResult;
import org.cartoweb.stats.report.result.Result;
import org.cartoweb.stats.report.result.SurfaceResult;
import org.ini4j.Ini;
import org.pvalsecc.jdbc.JdbcUtilities;
import org.pvalsecc.misc.UnitUtilities;
import org.pvalsecc.opts.Option;

import java.io.FileInputStream;
import java.io.IOException;
import java.sql.Connection;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class Reports extends BaseStats {
    private static final Log LOGGER = LogFactory.getLog(Reports.class);

    private static final Pattern TRANSFORM = Pattern.compile("[^\\w]", Pattern.CASE_INSENSITIVE);

    @Option(desc = "Location of the INI file describing the reports to generate", mandatory = true,
            environment = "STATS_INI_FILENAME")
    private String iniFilename = "";

    @Option(desc = "If true, the reports are purged in case of configuration change")
    private boolean purgeOnConfigurationChange = false;

    private final List<Report> reports = new ArrayList<Report>();

    private Timestamp lastRecordDate;
    private static final String FILTERS_PREFIX = "filters.";
    private static final String PERIODS_PREFIX = "periods.";

    public Reports(String[] args) {
        super();
        parseArgs(args);
    }

    public void parseIniFile(Connection con) throws IOException, SQLException {
        FileInputStream file = new FileInputStream(iniFilename);
        Ini ini = new Ini(file);
        for (Ini.Section section : ini.values()) {
            TimeScaleDefinition[] timeScales = parseTimeScales(section);
            final Classifier<?> classifier = parseType(section, timeScales);
            DimensionMetaData<?>[] dimensionMetaDatas = parseDimensions(section);
            Filter[] filters = parseFilters(con, section);
            final Result[] results = parseValues(con, section);
            final String label = getMandatoryField(section, "label");
            Report report = new Report(dimensionMetaDatas, filters, classifier, results, section.getName(), label);
            reports.add(report);
        }
    }

    private Result[] parseValues(Connection con, Ini.Section section) throws SQLException {
        String[] values = getMandatoryField(section, "values").split(",\\s*");
        final Result[] results = new Result[values.length];
        for (int i = 0; i < values.length; ++i) {
            String value = values[i];
            if (value.equalsIgnoreCase("count")) {
                results[i] = new CounterResult(null, "count");
            } else if (value.equalsIgnoreCase("countPDF")) {
                results[i] = new CounterResult(Utils.getIndirectValue(con, tableName, "general_export_plugin", "exportPdf"), "count_pdf");
            } else if (value.equalsIgnoreCase("countDXF")) {
                results[i] = new CounterResult(Utils.getIndirectValue(con, tableName, "general_export_plugin", "exportDxf"), "count_dxf");
            } else if (value.equalsIgnoreCase("pixel")) {
                results[i] = new SurfaceResult();
            } else {
                throw new RuntimeException("Unknown value type: " + value);
            }
        }
        return results;
    }

    private Filter[] parseFilters(Connection con, Ini.Section section) throws SQLException {
        List<Filter> filterList = new ArrayList<Filter>();
        for (Map.Entry<String, String> cur : section.entrySet()) {
            if (cur.getKey().startsWith(FILTERS_PREFIX)) {
                String type = cur.getKey().substring(FILTERS_PREFIX.length());
                if (type.equalsIgnoreCase("scale")) {
                    filterList.add(new DoubleRangeFilter("location_scale", cur.getValue(), type));
                } else if (type.equalsIgnoreCase("project")) {
                    filterList.add(new IdSetFilter(con, tableName, "general_mapid", cur.getValue().toLowerCase(), type));
                } else if (type.equalsIgnoreCase("layer")) {
                    filterList.add(new LayerFilter(con, tableName, cur.getValue().toLowerCase()));
                } else if (type.equalsIgnoreCase("width")) {
                    filterList.add(new IntegerRangeFilter("images_mainmap_width", cur.getValue(), type));
                } else if (type.equalsIgnoreCase("height")) {
                    filterList.add(new IntegerRangeFilter("images_mainmap_height", cur.getValue(), type));
                } else if (type.equalsIgnoreCase("theme")) {
                    filterList.add(new IdSetFilter(con, tableName, "layers_switch_id", cur.getValue().toLowerCase(), type));
                } else if (type.equalsIgnoreCase("user")) {
                    filterList.add(new IdSetFilter(con, tableName, "general_security_user", cur.getValue().toLowerCase(), type));
                } else if (type.equalsIgnoreCase("pdfFormat")) {
                    filterList.add(new IdSetFilter(con, tableName, "exportpdf_format", cur.getValue(), type));
                } else if (type.equalsIgnoreCase("pdfRes")) {
                    filterList.add(new IntegerRangeFilter("exportpdf_resolution", cur.getValue(), type));
                } else {
                    throw new RuntimeException("Unknown filter type: " + type);
                }
            }
        }
        Filter[] filters = new Filter[filterList.size()];
        filters = filterList.toArray(filters);
        return filters;
    }

    private TimeScaleDefinition[] parseTimeScales(Ini.Section section) {
        List<TimeScaleDefinition> timeScaleList = new ArrayList<TimeScaleDefinition>();
        for (Map.Entry<String, String> cur : section.entrySet()) {
            if (cur.getKey().startsWith(PERIODS_PREFIX)) {
                String type = cur.getKey().substring(PERIODS_PREFIX.length());
                timeScaleList.add(new TimeScaleDefinition(type, Integer.parseInt(cur.getValue())));
            }
        }
        TimeScaleDefinition[] timeScales = new TimeScaleDefinition[timeScaleList.size()];
        timeScales = timeScaleList.toArray(timeScales);
        initTimeScales(timeScales, lastRecordDate);
        return timeScales;
    }

    private Classifier<?> parseType(Ini.Section section, TimeScaleDefinition[] timeScales) {
        final String reportType = getMandatoryField(section, "type");
        final String resultTable = tableName + "_" + toDbName(section.getName());
        final Classifier<?> classifier;
        if (reportType.equalsIgnoreCase("simple")) {
            classifier = new SimpleClassifier(resultTable, timeScales);
        } else
        if (reportType.equalsIgnoreCase("gridbbox") || reportType.equalsIgnoreCase("gridcenter")) {
            classifier = new GridClassifier(getMandatoryDoubleField(section, "minx"), getMandatoryDoubleField(section, "size"), getMandatoryIntField(section, "nx"),
                    getMandatoryDoubleField(section, "miny"), getMandatoryDoubleField(section, "size"), getMandatoryIntField(section, "ny"),
                    reportType.equalsIgnoreCase("gridbbox"),
                    resultTable, timeScales);
        } else {
            throw new RuntimeException("Unknown report type: " + reportType);
        }
        return classifier;
    }

    private DimensionMetaData<?>[] parseDimensions(Ini.Section section) {
        final String conf = section.get("dimensions");
        if (conf != null) {
            String[] dimensions = conf.split(",\\s*");
            DimensionMetaData<?>[] dimensionMetaDatas = new DimensionMetaData<?>[dimensions.length];
            for (int i = 0; i < dimensions.length; ++i) {
                String dimension = dimensions[i];
                if (dimension.equalsIgnoreCase("project")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("general_mapid", dimension, true);
                } else if (dimension.equalsIgnoreCase("user")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("general_security_user", dimension, true);
                } else if (dimension.equalsIgnoreCase("scale")) {
                    final String scales = getMandatoryField(section, "scales");
                    dimensionMetaDatas[i] = new RangeDoubleFieldMetaData("location_scale", scales, dimension);
                } else if (dimension.equalsIgnoreCase("width")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("images_mainmap_width", dimension, false);
                } else if (dimension.equalsIgnoreCase("height")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("images_mainmap_height", dimension, false);
                } else if (dimension.equalsIgnoreCase("theme")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("layers_switch_id", dimension, true);
                } else if (dimension.equalsIgnoreCase("layer")) {
                    dimensionMetaDatas[i] = new LayerMetaData();
                } else if (dimension.equalsIgnoreCase("pdfFormat")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("exportpdf_format", dimension, true);
                } else if (dimension.equalsIgnoreCase("pdfRes")) {
                    dimensionMetaDatas[i] = new IntFieldMetaData("exportpdf_resolution", dimension, false);
                } else {
                    throw new RuntimeException("Unknown dimension type: " + dimension);
                }
            }
            return dimensionMetaDatas;
        } else {
            return new DimensionMetaData<?>[0];
        }
    }

    private String toDbName(String value) {
        Matcher matcher = TRANSFORM.matcher(value);
        return matcher.replaceAll("_");
    }

    private int getMandatoryIntField(Ini.Section section, String name) {
        final String result = getMandatoryField(section, name);
        return Integer.parseInt(result);
    }

    private double getMandatoryDoubleField(Ini.Section section, String name) {
        final String result = getMandatoryField(section, name);
        return Double.parseDouble(result);
    }

    private String getMandatoryField(Ini.Section section, String name) {
        final String result = section.get(name);
        if (result == null) {
            throw new RuntimeException("Missing mandatory field");
        }
        return result;
    }

    private static void initTimeScales(TimeScaleDefinition[] timeScales, Timestamp lastRecordDate) {
        for (int i = 0; i < timeScales.length; ++i) {
            TimeScaleDefinition timeScale = timeScales[i];
            timeScale.init(lastRecordDate);
        }
    }

    private void generate(Connection con) throws SQLException {
        long startTime = System.currentTimeMillis();
        for (int i = 0; i < reports.size(); i++) {
            Report report = reports.get(i);
            try {
                report.init(con, tableName, purgeOnConfigurationChange);
                report.compute(con, tableName);
            } catch (ConfigurationChangeException ex) {
                LOGGER.warn(ex.getMessage());
            }
        }
        con.close();
        LOGGER.info("Time to generate all the reports: " + UnitUtilities.toElapsedTime(System.currentTimeMillis() - startTime));
    }

    protected void runImpl() throws SQLException, IOException, ClassNotFoundException {
        Connection con = getConnection();
        lastRecordDate = getLastRecordDate(con);

        if (lastRecordDate != null) {
            checkDB(con);
            parseIniFile(con);
            generate(con);
        } else {
            LOGGER.warn("No data found in " + tableName);
        }
    }

    private void checkDB(Connection con) throws SQLException {
        checkDB(con, tableName);
    }

    public static void checkDB(Connection con, final String tableName) throws SQLException {
        if (!Utils.doesTableExist(con, tableName + "_reports")) {
            LOGGER.warn("Table " + tableName + "_reports missing. Creating it.");
            JdbcUtilities.runDeleteQuery("creating table " + tableName + "_reports",
                    "CREATE TABLE " + tableName + "_reports (name text, config text, last_id bigint, last_time timestamp without time zone, tables text, label text)",
                    con, null);
        }

        if (!Utils.doesTableExist(con, tableName + "_dimensions")) {
            LOGGER.warn("Table " + tableName + "_dimensions missing. Creating it.");
            JdbcUtilities.runDeleteQuery("creating table " + tableName + "_dimensions",
                    "CREATE TABLE " + tableName + "_dimensions (report_name text, field_name text, id int)",
                    con, null);
        }
    }

    public static void main(String[] args) {
        Reports reports = new Reports(args);
        reports.run();
    }
}
