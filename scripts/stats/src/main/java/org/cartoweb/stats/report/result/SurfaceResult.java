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

/**
 * Count the number of map pixels.
 */
public class SurfaceResult implements Result {
    private long surface;

    public String getSQLFields() {
        return "(CASE WHEN images_mainmap_width IS NULL THEN NULL ELSE greatest(least(images_mainmap_width::INT8, 99999), 0) END)*(CASE WHEN images_mainmap_height IS NULL THEN NULL ELSE greatest(least(images_mainmap_height::INT8, 99999), 0) END)";
    }

    public int updateFromResultSet(ResultSet rs, int pos) throws SQLException {
        surface = rs.getLong(++pos);
        return pos;
    }

    public long updateResult(long value) {
        return value + surface;
    }

    public String getType() {
        return "pixel";
    }
}
