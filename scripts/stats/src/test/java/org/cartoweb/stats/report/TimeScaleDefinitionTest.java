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

import org.cartoweb.stats.BaseTestCase;

import java.sql.Timestamp;
import java.util.Calendar;

public class TimeScaleDefinitionTest extends BaseTestCase {
    public TimeScaleDefinitionTest(String name) {
        super(name);
    }

    public void testBasics() {
        Timestamp timestamp = createGmtTimestamp(2008, Calendar.FEBRUARY, 5, 19, 36, 25);

        TimeScaleDefinition hour = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.HOUR, 1, 10, "hour");
        final long hourExpected = getGmtMillis(2008, Calendar.FEBRUARY, 5, 19, 0, 0);
        assertEquals(hourExpected, hour.getStartOfPeriod(timestamp));
        assertEquals(3600 * 1000, hour.getPeriodDuration(hourExpected));
        hour.init(timestamp);
        assertEquals(getGmtMillis(2008, Calendar.FEBRUARY, 5, 10, 0, 0), hour.minTime);

        TimeScaleDefinition day = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.DAY, 1, 10, "day");
        final long dayExpected = getGmtMillis(2008, Calendar.FEBRUARY, 5, 0, 0, 0);
        assertEquals(dayExpected, day.getStartOfPeriod(timestamp));
        assertEquals(24 * 3600 * 1000, day.getPeriodDuration(dayExpected));
        day.init(timestamp);
        assertEquals(getGmtMillis(2008, Calendar.JANUARY, 27, 0, 0, 0), day.minTime);

        TimeScaleDefinition week = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.WEEK, 1, 2, "week");
        final long weekExpected = getGmtMillis(2008, Calendar.FEBRUARY, 4, 0, 0, 0);
        assertEquals(weekExpected, week.getStartOfPeriod(timestamp));
        assertEquals(7 * 24 * 3600 * 1000, week.getPeriodDuration(weekExpected));
        week.init(timestamp);
        assertEquals(getGmtMillis(2008, Calendar.JANUARY, 28, 0, 0, 0), week.minTime);

        TimeScaleDefinition month = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.MONTH, 1, 10, "month");
        final long monthExpected = getGmtMillis(2008, Calendar.FEBRUARY, 1, 0, 0, 0);
        assertEquals(monthExpected, month.getStartOfPeriod(timestamp));
        assertEquals(29L * 24 * 3600 * 1000, month.getPeriodDuration(monthExpected));
        month.init(timestamp);
        assertEquals(getGmtMillis(2007, Calendar.MAY, 1, 0, 0, 0), month.minTime);

        TimeScaleDefinition year = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.YEAR, 1, 10, "year");
        final long yearExpected = getGmtMillis(2008, Calendar.JANUARY, 1, 0, 0, 0);
        assertEquals(yearExpected, year.getStartOfPeriod(timestamp));
        assertEquals(366L * 24 * 3600 * 1000, year.getPeriodDuration(yearExpected));
        year.init(timestamp);
        assertEquals(getGmtMillis(1999, Calendar.JANUARY, 1, 0, 0, 0), year.minTime);
    }

    public void testOldBug() {
        TimeScaleDefinition ts = new TimeScaleDefinition(TimeScaleDefinition.PeriodUnit.WEEK, 1, 4, "week");
        long actual = ts.getStartOfPeriod(new Timestamp(1191188782000L));
        assertEquals(1190592000000L, actual);
    }
}
