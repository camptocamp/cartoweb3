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

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;

public interface DimensionMetaData<C extends Dimension> {
    /**
     * Used for building the SELECT query.
     */
    String getStatsFieldNames();

    /**
     * Used for building the SELECT query.
     */
    int getNbStatsFieldNames();

    /**
     * Used when reading the results of the SELECT query.
     */
    C[] buildFromStatResultSet(ResultSet rs, int pos) throws SQLException;

    /**
     * Used when reading the previous reports to merge with the new values.
     */
    C buildFromReportResultSet(ResultSet rs, int pos) throws SQLException;

    /**
     * Used for the report's CREATE TABLE.
     */
    String getFieldDefinitions();

    /**
     * Used for building the report's INSERT query.
     */
    String getReportFieldNames();

    /**
     * Used for building the report's INSERT query.
     */
    int getNbReportFields();

    /**
     * The type in the INI file
     */
    String getIniType();

    /**
     * The additional parameters in the INI file or NULL.
     */
    String getIniAdditionalParam();

    /**
     * Create the addional constraints (usually foreign keys).
     */
    void createDbStructure(Connection con, String resultTableName, String statsTableName) throws SQLException;
}
