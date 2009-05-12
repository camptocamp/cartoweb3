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
import java.sql.ResultSet;
import java.sql.SQLException;

/**
 * Filter specification. There are two ways to filters the records:
 * <ul>
 * <li>SQL:  adds a where clause to the SELECT query (see {@link SQLOnlyFilter})
 * <li>Soft: filter the records in Java (see {@link SoftOnlyFilter})
 * </ul>
 */
public interface Filter {
    /**
     * For the "SQL" filters the WHERE clause or NULL for the "Soft" filters.
     */
    String getSelectWhereClause();

    /**
     * Update the statement for '?' place holders.
     *
     * @param stmt
     * @param pos  The previous position in the parameter list
     * @return The current position in the parameter list
     * @throws SQLException
     */
    int setSelectWhereParams(PreparedStatement stmt, int pos) throws SQLException;

    /**
     * Used by "Soft" filters to be able to read additional fields.
     */
    String getSQLFields();

    /**
     * Used by "Soft" filters to be able to read additional fields.
     */
    int updateFromResultSet(ResultSet rs, int pos) throws SQLException;

    /**
     * Used by "Soft" filters to do their job.
     *
     * @return True if the record has to be kept.
     */
    boolean softCheck();

    void getIniFile(StringBuilder result);
}
