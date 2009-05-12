package org.cartoweb.stats.report.filter;

import java.sql.PreparedStatement;
import java.sql.SQLException;

/**
 * A filter that uses only java filtering (not SQL filtering)
 */
public abstract class SoftOnlyFilter implements Filter {
    /**
     * Updated by the method {@link #updateFromResultSet(java.sql.ResultSet, int)}.
     * Tells if the current filter passes the filter.
     */
    protected boolean ok = false;

    public String getSelectWhereClause() {
        return null;
    }

    public int setSelectWhereParams(PreparedStatement stmt, int pos) throws SQLException {
        return pos;
    }

    public boolean softCheck() {
        return ok;
    }
}
