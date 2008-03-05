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

package org.cartoweb.stats.report.result;

import java.sql.ResultSet;
import java.sql.SQLException;

public class CounterResult implements Result {
    private final String colName;
    private final Integer exportPlugin;
    private boolean ok = false;

    public CounterResult(Integer exportPlugin, String colName) {
        this.exportPlugin = exportPlugin;
        this.colName = colName;
    }

    public String getSQLFields() {
        return "general_export_plugin";
    }

    public int updateFromResultSet(ResultSet rs, int pos) throws SQLException {
        int val = rs.getInt(++pos);
        if (exportPlugin == null) {
            ok = rs.wasNull();
        } else {
            ok = exportPlugin == val;
        }
        return pos;
    }

    public long updateResult(long value) {
        if (ok) {
            return value + 1;
        } else {
            return value;
        }
    }

    public String getType() {
        return colName;
    }
}
