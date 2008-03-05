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

package org.cartoweb.stats.report.filter;

import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class IntegerRangeFilter extends SQLOnlyFilter {
    private static final Pattern RANGE_PATTERN = Pattern.compile("(-?\\d+)\\s*-\\s*(-?\\d+)");
    private static final Pattern SIMPLE_PATTERN = Pattern.compile("(-?\\d+)");

    private final String fieldName;
    private final int min;
    private final int max;
    private final String type;

    public IntegerRangeFilter(String fieldName, String value, String type) {
        this.fieldName = fieldName;
        this.type = type;
        Matcher matcher = RANGE_PATTERN.matcher(value);
        if (matcher.matches()) {
            min = Integer.parseInt(matcher.group(1));
            max = Integer.parseInt(matcher.group(2));
        } else {
            matcher = SIMPLE_PATTERN.matcher(value);
            if (matcher.matches()) {
                min = max = Integer.parseInt(matcher.group(1));
            } else {
                throw new RuntimeException("Cannot parse: " + value);
            }
        }
    }

    public String getSelectWhereClause() {
        if (min != max) {
            return fieldName + ">=? and " + fieldName + "<=?";
        } else {
            return fieldName + "=?";
        }
    }

    public int setSelectWhereParams(PreparedStatement stmt, int pos) throws SQLException {
        stmt.setInt(++pos, min);
        if (min != max) {
            stmt.setInt(++pos, max);
        }
        return pos;
    }

    public void getIniFile(StringBuilder result) {
        result.append("filters.").append(type).append("=").append(min).append("-").append(max).append("\n");
    }
}
