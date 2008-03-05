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
import org.cartoweb.stats.report.classifier.GridClassifier;
import org.cartoweb.stats.report.dimension.DimensionMetaData;
import org.cartoweb.stats.report.filter.Filter;
import org.cartoweb.stats.report.result.CounterResult;
import org.cartoweb.stats.report.result.Result;

import java.sql.SQLException;

public class IncrementalGridBboxTest extends DbTestCase {
    public IncrementalGridBboxTest(String name) {
        super(name);
    }

    public void testBasic() throws SQLException, ClassNotFoundException {
        long id = 0;
        StatsRecord record = new StatsRecord();
        record.setId(id++);
        record.setBboxMinx(55);
        record.setBboxMiny(55);
        record.setBboxMaxx(155);
        record.setBboxMaxy(155);
        record.setGeneralTime(createGmtTimestamp(2007, 1, 5, 8, 21, 12));
        addRecord(record);

        computeReport(false);

        checkGridValue(0, 0, 0, "count");
        checkGridValue(0, 0, 1, "count");
        checkGridValue(0, 0, 2, "count");
        checkGridValue(0, 0, 3, "count");
        checkGridValue(0, 0, 4, "count");
        checkGridValue(0, 1, 4, "count");
        checkGridValue(0, 2, 4, "count");
        checkGridValue(0, 3, 4, "count");
        checkGridValue(0, 4, 4, "count");
        checkGridValue(0, 4, 3, "count");
        checkGridValue(0, 4, 2, "count");
        checkGridValue(0, 4, 1, "count");
        checkGridValue(0, 4, 0, "count");
        checkGridValue(0, 3, 0, "count");
        checkGridValue(0, 2, 0, "count");
        checkGridValue(0, 1, 0, "count");
        checkGridValue(1, 1, 1, "count");
        checkGridValue(1, 2, 2, "count");
        checkGridValue(1, 3, 3, "count");
        checkGridValue(1, 3, 1, "count");
        checkGridValue(1, 1, 3, "count");

        record.setId(id++);
        record.setGeneralTime(createGmtTimestamp(2007, 1, 5, 8, 21, 13));
        record.setBboxMinx(150);
        record.setBboxMiny(150);
        record.setBboxMaxx(200);
        record.setBboxMaxy(200);
        addRecord(record);

        computeReport(false);

        checkGridValue(0, 0, 0, "count");
        checkGridValue(1, 1, 1, "count");
        checkGridValue(1, 2, 2, "count");
        checkGridValue(2, 3, 3, "count");
        checkGridValue(1, 4, 4, "count");
        checkGridValue(0, 5, 5, "count");
    }

    protected Report createReport() {
        DimensionMetaData<?>[] dimensionMetaDatas = {
        };
        Filter[] filters = {};
        Classifier<?> classifier = new GridClassifier(0, 50, NB_X, 0, 50, NB_Y, true, RESULT_TABLE, TIME_SCALES);
        Result[] results = {
                new CounterResult(null, "count")
        };
        return new Report(dimensionMetaDatas, filters, classifier, results, getName(), getName());
    }
}