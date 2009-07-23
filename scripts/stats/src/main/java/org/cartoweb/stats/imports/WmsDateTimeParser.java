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

import java.sql.Timestamp;
import java.util.Calendar;
import java.util.GregorianCalendar;
import java.util.SimpleTimeZone;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Logic to extract the MapId out of a WMS log file.
 */
public class WmsDateTimeParser extends BaseDateTimeParser {
    
    private static final Pattern TIME_PATTERN = Pattern.compile("(\\d{2})/(\\w{3})/(\\d{4}):(\\d{2}):(\\d{2}):(\\d{2}) ([+-])(\\d{2})(\\d{2})");
    
    protected Timestamp parseTime(String time) {
        
        Matcher matcher = TIME_PATTERN.matcher(time);
        if (!matcher.matches()) {
            throw new RuntimeException("Cannot parse time [" + time + "]");
        }

        int offset = (Integer.parseInt(matcher.group(8), 10) * 60 + Integer.parseInt(matcher.group(9), 10)) * 60 * 1000;
        if (matcher.group(7).equals("-")) {
            offset = -offset;
        }
        int day = Integer.parseInt(matcher.group(1));
        Integer month = MONTH.get(matcher.group(2).toLowerCase());
        int year = Integer.parseInt(matcher.group(3));
        int hour = Integer.parseInt(matcher.group(4));
        int minute = Integer.parseInt(matcher.group(5));
        int second = Integer.parseInt(matcher.group(6));

        if (month == null) {
            throw new RuntimeException("Cannot parse month in time [" + time + "]");
        }

        Calendar calendar = new GregorianCalendar(new SimpleTimeZone(offset, "tmp"));
        calendar.set(year, month, day, hour, minute, second);
        calendar.set(Calendar.MILLISECOND, 0);
        return new Timestamp(calendar.getTimeInMillis());
    }    
}
