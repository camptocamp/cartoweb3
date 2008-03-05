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

package org.cartoweb.stats.report.classifier;

import java.sql.Array;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Map;

public class ArrayAdapter implements Array {
    private final long[][][] grid;
    private final int posVal;

    public ArrayAdapter(long[][][] grid, int posVal) {
        this.grid = grid;
        this.posVal = posVal;
    }

    public String getBaseTypeName() throws SQLException {
        return "int8";
    }

    public String toString() {
        StringBuilder result = new StringBuilder("{");
        for (int i = 0; i < grid.length; ++i) {
            long[][] line = grid[i];
            for (int j = 0; j < line.length; ++j) {
                long[] cell = line[j];
                if (i > 0 || j > 0) {
                    result.append(",");
                }
                result.append(cell[posVal]);
            }
        }
        result.append("}");
        return result.toString();
    }

    public int getBaseType() throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public Object getArray() throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public Object getArray(Map<String, Class<?>> map) throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public Object getArray(long index, int count) throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public Object getArray(long index, int count, Map<String, Class<?>> map) throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public ResultSet getResultSet() throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public ResultSet getResultSet(Map<String, Class<?>> map) throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public ResultSet getResultSet(long index, int count) throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public ResultSet getResultSet(long index, int count, Map<String, Class<?>> map) throws SQLException {
        throw new RuntimeException("Not implemented");
    }

    public void free() {
        throw new RuntimeException("Not implemented");
    }
}
