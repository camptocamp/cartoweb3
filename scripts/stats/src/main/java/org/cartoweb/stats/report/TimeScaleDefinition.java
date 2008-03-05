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

import java.sql.Timestamp;
import java.util.Calendar;
import java.util.GregorianCalendar;
import java.util.TimeZone;

public class TimeScaleDefinition implements Comparable {
    public static final long HOUR_DURATION = 1000 * 3600;
    public static final long DAY_DURATION = HOUR_DURATION * 24;
    public static final TimeZone GMT_TIME_ZONE = TimeZone.getTimeZone("GMT");

    private final PeriodUnit periodUnit;

    /**
     * The duration of chunk of time where the stats will be aggregated.
     */
    private final int period;

    /**
     * The number of chunks to keep.
     */
    private final int nb;

    /**
     * The name of this time scale
     */
    private final String name;

    private long prevStart;
    private long prevDuration;
    private long prevDurationStart;
    protected long minTime;

    public TimeScaleDefinition(PeriodUnit periodUnit, int period, int nb, String name) {
        this.periodUnit = periodUnit;
        this.period = period;
        this.nb = nb;
        this.name = name;
    }

    public TimeScaleDefinition(String type, int nb) {
        periodUnit = PeriodUnit.valueOf(type.toUpperCase());
        if (periodUnit == null) {
            throw new RuntimeException("Unknown period type: " + type);
        }
        this.nb = nb;
        this.period = 1;
        this.name = type;
    }

    public PeriodUnit getPeriodUnit() {
        return periodUnit;
    }

    public int getPeriod() {
        return period;
    }

    public int getNb() {
        return nb;
    }

    public String getName() {
        return name;
    }

    public int compareTo(Object o) {
        if (o instanceof TimeScaleDefinition) {
            TimeScaleDefinition other = (TimeScaleDefinition) o;
            if (period != other.period) {
                return period < other.period ? -1 : 1;
            } else {
                return 0;
            }
        } else {
            throw new RuntimeException("Cannot compare apples and pears");
        }
    }

    /**
     * @return The time in milliseconds floored to the start of a period
     */
    public long getStartOfPeriod(Timestamp time) {
        if (time.getTime() >= prevStart && time.getTime() < prevStart + getPeriodDuration(prevStart)) {
            return prevStart;
        }

        switch (periodUnit) {
            case DAY:
                prevStart = (time.getTime() / DAY_DURATION) * DAY_DURATION;
                break;
            case HOUR:
                prevStart = (time.getTime() / HOUR_DURATION) * HOUR_DURATION;
                break;
            case MONTH: {
                Calendar cal = createCalendar();
                cal.setTime(time);
                cal.set(Calendar.DAY_OF_MONTH, 1);
                cal.set(Calendar.AM_PM, Calendar.AM);
                cal.set(Calendar.HOUR, 0);
                cal.set(Calendar.MINUTE, 0);
                cal.set(Calendar.SECOND, 0);
                cal.set(Calendar.MILLISECOND, 0);
                prevStart = cal.getTimeInMillis();
                break;
            }
            case WEEK: {
                Calendar cal = createCalendar();
                cal.setTime(time);
                cal.set(Calendar.DAY_OF_WEEK, Calendar.MONDAY);  //may be locale dependent?
                cal.set(Calendar.AM_PM, Calendar.AM);
                cal.set(Calendar.HOUR, 0);
                cal.set(Calendar.MINUTE, 0);
                cal.set(Calendar.SECOND, 0);
                cal.set(Calendar.MILLISECOND, 0);
                prevStart = cal.getTimeInMillis();
                if (prevStart > time.getTime()) {
                    prevStart -= 7 * DAY_DURATION;
                }
                break;
            }
            case YEAR: {
                Calendar cal = createCalendar();
                cal.setTime(time);
                cal.set(Calendar.DAY_OF_YEAR, 1);
                cal.set(Calendar.AM_PM, Calendar.AM);
                cal.set(Calendar.HOUR, 0);
                cal.set(Calendar.MINUTE, 0);
                cal.set(Calendar.SECOND, 0);
                cal.set(Calendar.MILLISECOND, 0);
                prevStart = cal.getTimeInMillis();
                break;
            }
            default:
                throw new RuntimeException("Unkown unit: " + periodUnit);
        }
        if (prevStart > time.getTime()) {
            throw new RuntimeException("Bug in here (" + new Timestamp(prevStart) + ">" + time + "): time=" + time.getTime() + " " + toString());
        }
        return prevStart;
    }

    private static GregorianCalendar createCalendar() {
        return new GregorianCalendar(GMT_TIME_ZONE);
    }

    protected long getPeriodDuration(long start) {
        if (prevDurationStart == start) {
            return prevDuration;
        }
        prevDuration = add(start, 1) - start;
        prevDurationStart = start;
        return prevDuration;
    }

    private long add(long start, int nb) {
        switch (periodUnit) {
            case HOUR:
                return start + HOUR_DURATION * nb;
            case DAY:
                return start + DAY_DURATION * nb;
            case WEEK:
                return start + DAY_DURATION * 7 * nb;
            case MONTH: {
                Calendar cal = createCalendar();
                cal.setTimeInMillis(start);
                cal.add(Calendar.MONTH, nb);
                return cal.getTimeInMillis();
            }
            case YEAR: {
                Calendar cal = createCalendar();
                cal.setTimeInMillis(start);
                cal.add(Calendar.YEAR, nb);
                return cal.getTimeInMillis();
            }
            default:
                throw new RuntimeException("Unkown unit: " + periodUnit);
        }
    }

    public void init(Timestamp lastRecordDate) {
        minTime = add(getStartOfPeriod(lastRecordDate), -nb + 1);
    }

    public String getTableName(String resultTable) {
        return new StringBuilder(resultTable).append('_').append(name).toString();
    }

    public boolean isInRange(Timestamp time) {
        return time.getTime() >= minTime;
    }

    public void getIniFile(StringBuilder result) {
        result.append("periods.").append(periodUnit.toString().toLowerCase())
                .append("=").append(nb).append("\n");
    }

    public String toString() {
        return "TimeScaleDefinition{periodUnit=" + periodUnit + " period=" + period + " nb=" + nb + " name=" + name + "}";
    }

    public enum PeriodUnit {
        HOUR,
        DAY,
        WEEK,
        MONTH,
        YEAR
    }

    public long getMinTime() {
        return minTime;
    }
}
