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

package org.cartoweb.stats.report.dimension;

import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.sql.Types;

public class IntField implements Dimension {
    private final Integer value;

    public IntField(Integer value) {
        this.value = value;
    }

    public int fillStatement(PreparedStatement s, int pos) throws SQLException {
        if (value != null) {
            s.setInt(++pos, value);
        } else {
            s.setNull(++pos, Types.INTEGER);
        }
        return pos;
    }

    public boolean equals(Object o) {
        if (this == o) return true;
        if (o == null || getClass() != o.getClass()) return false;

        IntField intField = (IntField) o;

        return !(value != null ? !value.equals(intField.value) : intField.value != null);

    }

    public int hashCode() {
        return (value != null ? value.hashCode() : 0);
    }
}
