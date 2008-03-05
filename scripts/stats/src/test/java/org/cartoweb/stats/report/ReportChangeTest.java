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

import org.cartoweb.stats.imports.StatsRecord;
import org.cartoweb.stats.report.classifier.Classifier;
import org.cartoweb.stats.report.classifier.SimpleClassifier;
import org.cartoweb.stats.report.dimension.DimensionMetaData;
import org.cartoweb.stats.report.dimension.LayerMetaData;
import org.cartoweb.stats.report.filter.Filter;
import org.cartoweb.stats.report.result.CounterResult;
import org.cartoweb.stats.report.result.Result;
import org.cartoweb.stats.report.result.SurfaceResult;

import java.sql.SQLException;

public class ReportChangeTest extends DbTestCase {
    private Result result = new SurfaceResult();

    public ReportChangeTest(String name) {
        super(name);
    }

    public void testFailOnChange() throws SQLException, ClassNotFoundException {
        doTest(false);
    }

    public void testPassOnChange() throws SQLException, ClassNotFoundException {
        doTest(true);
    }

    public void doTest(boolean purgeOnConfigurationChange) throws SQLException, ClassNotFoundException {
        long id = 0;
        StatsRecord record = new StatsRecord();
        record.setId(id++);
        record.setImagesMainmapWidth(50);
        record.setImagesMainmapHeight(150);
        Integer layer1Id = imports.getSideTables().layer.get("1", 1);
        Integer layer2Id = imports.getSideTables().layer.get("4", 1);
        imports.getSideTables().layer.save(con);
        record.setLayers(layer1Id + "," + layer2Id);
        record.setGeneralTime(createGmtTimestamp(2007, 1, 5, 8, 21, 12));
        addRecord(record);

        computeReport(false);

        checkValue(50 * 150, "WHERE layer=" + layer1Id, "pixel");
        checkValue(50 * 150, "WHERE layer=" + layer2Id, "pixel");

        record.setId(id++);
        record.setGeneralTime(createGmtTimestamp(2007, 1, 5, 8, 21, 13));
        record.setImagesMainmapHeight(250);
        addRecord(record);

        computeReport(false);

        checkValue(50 * 150 + 50 * 250, "WHERE layer=" + layer1Id, "pixel");
        checkValue(50 * 150 + 50 * 250, "WHERE layer=" + layer2Id, "pixel");

        record.setId(id++);
        record.setGeneralTime(createGmtTimestamp(2007, 1, 5, 8, 21, 14));
        record.setImagesMainmapHeight(350);
        addRecord(record);

        LOGGER.info("before: " + createReport());
        result = new CounterResult(null, "count");
        LOGGER.info("after: " + createReport());
        try {
            computeReport(purgeOnConfigurationChange);
            if (!purgeOnConfigurationChange) {
                fail("was supposed to throw an exception");
            }
        } catch (ConfigurationChangeException ex) {
            assertEquals("Configuration changed for report '" + getName() + "'", ex.getMessage());
        }
    }

    protected Report createReport() {
        DimensionMetaData<?>[] dimensionMetaDatas = {
                new LayerMetaData()
        };
        Filter[] filters = {};
        Classifier<?> classifier = new SimpleClassifier(RESULT_TABLE, TIME_SCALES);
        Result[] results = {
                result
        };
        Report report = new Report(dimensionMetaDatas, filters, classifier, results, getName(), getName());
        return report;
    }
}